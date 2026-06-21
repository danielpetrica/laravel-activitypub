<?php

namespace DanielPetrica\LaravelActivityPub\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WebFingerService
{
    public function resolve(string $resource): ?array
    {
        if (str_starts_with(haystack: $resource, needle: 'acct:')) {
            $parts = explode(separator: '@', string: substr(string: $resource, offset: 5));
            $domain = $parts[1] ?? null;

            if ($domain === null) {
                return null;
            }

            $url = 'https://'.$domain.'/.well-known/webfinger?resource='.urlencode(string: $resource);

            return $this->fetch(url: $url);
        }

        $parsed = parse_url(url: $resource);

        if (! isset($parsed['host'])) {
            return null;
        }

        $domain = $parsed['host'];
        $url = 'https://'.$domain.'/.well-known/webfinger?resource='.urlencode(string: $resource);

        return $this->fetch(url: $url);
    }

    protected function fetch(string $url): ?array
    {
        try {
            $response = Http::timeout(
                seconds: config('activitypub.federation.delivery_timeout', 10),
            )->get(url: $url);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if ($data === null || ! isset($data['links'])) {
                return null;
            }

            foreach ($data['links'] as $link) {
                if (isset($link['type'], $link['href'])
                    && $link['type'] === 'application/activity+json') {
                    return $link;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::debug(
                message: 'WebFingerService: Request failed',
                context: [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ],
            );

            return null;
        }
    }
}
