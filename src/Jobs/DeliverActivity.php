<?php

namespace DanielPetrica\LaravelActivityPub\Jobs;

use DanielPetrica\LaravelActivityPub\Events\ActivityDelivered;
use DanielPetrica\LaravelActivityPub\Events\ActivityDeliveryFailed;
use DanielPetrica\LaravelActivityPub\Models\Activity;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Services\DeliveryClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class DeliverActivity implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 3600;

    public array $backoff = [30, 120, 600];

    public int $tries = 3;

    public int $maxExceptions = 3;

    public function __construct(
        public string $inboxUrl,
        public int $activityModelId,
        public int $actorId,
    ) {}

    public function uniqueId(): string
    {
        return sha1($this->inboxUrl.'|'.$this->activityModelId);
    }

    public function handle(DeliveryClient $deliveryClient): void
    {
        $activityModel = Activity::findOrFail($this->activityModelId);
        $actor = Actor::findOrFail($this->actorId);
        $activity = $activityModel->payload;

        $responseCode = $deliveryClient->deliver(
            inboxUrl: $this->inboxUrl,
            activity: $activity,
            actor: $actor,
        );

        if ($responseCode === null) {
            Log::debug('DeliverActivity: failed to encode activity JSON', [
                'inboxUrl' => $this->inboxUrl,
            ]);

            return;
        }

        if ($responseCode >= 200 && $responseCode < 300) {
            Activity::query()
                ->where(column: 'id', operator: '=', value: $this->activityModelId)
                ->update(values: [
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);

            event(new ActivityDelivered(
                activityId: $this->activityModelId,
                inboxUrl: $this->inboxUrl,
                actorId: $this->actorId,
            ));

            Log::debug('DeliverActivity: delivered successfully', [
                'inboxUrl' => $this->inboxUrl,
            ]);
        } else {
            Log::debug('DeliverActivity: delivery failed', [
                'inboxUrl' => $this->inboxUrl,
                'status' => $responseCode,
            ]);

            $this->release(delay: 60);
        }
    }

    public function failed(\Throwable $e): void
    {
        Activity::query()
            ->where('id', '=', $this->activityModelId)
            ->update(['status' => 'failed']);

        event(new ActivityDeliveryFailed(
            activityId: $this->activityModelId,
            inboxUrl: $this->inboxUrl,
            actorId: $this->actorId,
            error: $e->getMessage(),
        ));

        Log::warning('DeliverActivity: permanently failed', [
            'inboxUrl' => $this->inboxUrl,
            'activityModelId' => $this->activityModelId,
            'error' => $e->getMessage(),
        ]);
    }
}
