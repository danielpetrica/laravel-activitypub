<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers;

use DanielPetrica\LaravelActivityPub\Http\Resources\OrderedCollection;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class FollowingController extends Controller
{
    public function __invoke(Request $request, Actor $actor): JsonResponse
    {
        $collection = OrderedCollection::make(
            id: $actor->following_url,
            items: [],
            totalItems: 0,
        );

        return response()->json(
            data: $collection,
            headers: ['Content-Type' => 'application/activity+json'],
        );
    }
}
