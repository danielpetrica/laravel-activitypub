<?php

namespace DanielPetrica\LaravelActivityPub\Jobs;

use DanielPetrica\LaravelActivityPub\Models\Activity;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Services\HttpSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DeliverActivity implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 3600;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $activity
     */
    public function __construct(
        public string $inboxUrl,
        public array $activity,
        public Actor $actor,
        public ?int $activityId = null,
    ) {}

    public function uniqueId(): string
    {
        return md5(string: $this->inboxUrl.'|'.($this->activity['id'] ?? ''));
    }

    public function handle(): void
    {
        $body = json_encode(value: $this->activity);

        if ($body === false) {
            Log::debug('DeliverActivity: failed to encode activity JSON', [
                'inboxUrl' => $this->inboxUrl,
            ]);

            return;
        }

        $date = gmdate(format: 'D, d M Y H:i:s T');

        $digest = 'SHA-256='.base64_encode(string: hash(
            algo: 'sha256',
            data: $body,
            binary: true,
        ));

        $headers = [
            'Content-Type' => 'application/activity+json',
            'Date' => $date,
            'Host' => parse_url(url: $this->inboxUrl, component: PHP_URL_HOST),
            'Digest' => $digest,
        ];

        $signatureService = app(HttpSignatureService::class);
        $signedHeaders = $signatureService->sign(
            method: 'POST',
            url: $this->inboxUrl,
            headers: $headers,
            actor: $this->actor,
        );

        $response = Http::withHeaders(headers: $signedHeaders)
            ->timeout(seconds: config('activitypub.federation.delivery_timeout', 10))
            ->withBody(content: $body, contentType: 'application/activity+json')
            ->post(url: $this->inboxUrl);

        if ($response->successful()) {
            if ($this->activityId !== null) {
                Activity::query()
                    ->where(column: 'id', operator: '=', value: $this->activityId)
                    ->update(values: [
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
            }

            Log::debug('DeliverActivity: delivered successfully', [
                'inboxUrl' => $this->inboxUrl,
            ]);
        } else {
            Log::debug('DeliverActivity: delivery failed', [
                'inboxUrl' => $this->inboxUrl,
                'status' => $response->status(),
            ]);

            $this->release(delay: 60);
        }
    }
}
