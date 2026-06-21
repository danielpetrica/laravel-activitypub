<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers;

use DanielPetrica\LaravelActivityPub\Models\Actor;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class NodeInfoController extends Controller
{
    public function discovery(): JsonResponse
    {
        return $this->cachedJsonResponse(data: [
            'links' => [
                [
                    'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                    'href' => route(name: 'activitypub.nodeinfo'),
                ],
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        return $this->cachedJsonResponse(data: [
            'version' => '2.0',
            'software' => [
                'name' => 'laravel-activitypub',
                'version' => config('activitypub.version', '1.0.0'),
            ],
            'protocols' => ['activitypub'],
            'services' => [
                'inbound' => [],
                'outbound' => [],
            ],
            'usage' => [
                'users' => [
                    'total' => Actor::query()->count(),
                ],
            ],
            'openRegistrations' => config('activitypub.open_registrations', false),
            'metadata' => [],
        ]);
    }

    protected function cachedJsonResponse(array $data, int $status = 200): JsonResponse
    {
        $headers = [];

        if (config('activitypub.cache.enabled', true)) {
            $headers['Cache-Control'] = 'public, max-age='.config('activitypub.cache.ttl', 86400);
        }

        return response()->json(data: $data, status: $status, headers: $headers);
    }
}
