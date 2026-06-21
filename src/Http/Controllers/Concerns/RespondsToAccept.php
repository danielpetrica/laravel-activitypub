<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait RespondsToAccept
{
    protected function wantsJson(Request $request): bool
    {
        return $request->accepts(['application/activity+json', 'application/ld+json']);
    }

    protected function activityPubResponse(Request $request, array $data, int $status = 200)
    {
        $headers = [
            'Content-Type' => 'application/activity+json',
            'Vary' => 'Accept',
        ];

        if (config('activitypub.cache.enabled', true)) {
            $headers['Cache-Control'] = 'public, max-age='.config('activitypub.cache.ttl', 86400);
        }

        return response()->json(
            data: $data,
            status: $status,
            headers: $headers,
        );
    }
}
