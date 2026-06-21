<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers;

use DanielPetrica\LaravelActivityPub\Http\Resources\ActorResource;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class ActorController extends Controller
{
    public function __invoke(Request $request, Actor $actor): JsonResponse
    {
        $actorData = (new ActorResource(resource: $actor))->toArray(request: $request);

        return response()->json(
            data: $actorData,
            headers: ['Content-Type' => 'application/activity+json'],
        );
    }
}
