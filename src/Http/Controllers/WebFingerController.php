<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers;

use DanielPetrica\LaravelActivityPub\Http\Requests\WebFingerRequest;
use DanielPetrica\LaravelActivityPub\Http\Resources\WebFingerResource;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class WebFingerController extends Controller
{
    public function __invoke(WebFingerRequest $request): JsonResponse
    {
        $resource = $request->query(key: 'resource');
        $account = substr(string: $resource, offset: 5);
        [$username, $domain] = explode(separator: '@', string: $account);

        $parsedUrl = parse_url(url: config('activitypub.domain'));
        $configuredDomain = $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $configuredDomain .= ':'.$parsedUrl['port'];
        }

        if ($domain !== $configuredDomain) {
            return response()->json(
                data: ['error' => 'Domain not found.'],
                status: 404,
            );
        }

        $actor = Actor::query()
            ->where(column: 'username', operator: '=', value: $username)
            ->first();

        if ($actor === null) {
            return response()->json(
                data: ['error' => 'User not found.'],
                status: 404,
            );
        }

        $jrd = (new WebFingerResource(resource: $actor))->toArray(request: $request);

        $headers = ['Content-Type' => 'application/jrd+json'];

        if (config('activitypub.cache.enabled', true)) {
            $headers['Cache-Control'] = 'public, max-age='.config('activitypub.cache.ttl', 86400);
        }

        return response()->json(data: $jrd, headers: $headers);
    }
}
