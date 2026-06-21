<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers;

use DanielPetrica\LaravelActivityPub\Enums\FollowerStatus;
use DanielPetrica\LaravelActivityPub\Http\Resources\OrderedCollection;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\Follower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class FollowersController extends Controller
{
    public function __invoke(Request $request, Actor $actor): JsonResponse
    {
        $followers = Follower::query()
            ->with(relations: 'remoteActor')
            ->where(column: 'actor_id', operator: '=', value: $actor->id)
            ->where(column: 'status', operator: '=', value: FollowerStatus::Accepted)
            ->get();

        $items = $followers->map(function (Follower $follower) {
            return $follower->remoteActor->actor_url;
        })->toArray();

        $collection = OrderedCollection::make(
            id: $actor->followers_url,
            items: $items,
            totalItems: count($items),
        );

        return response()->json(
            data: $collection,
            headers: ['Content-Type' => 'application/activity+json'],
        );
    }
}
