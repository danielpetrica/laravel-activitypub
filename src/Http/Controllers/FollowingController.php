<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers;

use DanielPetrica\LaravelActivityPub\Enums\FollowerStatus;
use DanielPetrica\LaravelActivityPub\Http\Controllers\Concerns\RespondsToAccept;
use DanielPetrica\LaravelActivityPub\Http\Resources\OrderedCollection;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\Following;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class FollowingController extends Controller
{
    use RespondsToAccept;

    public function __invoke(Request $request, Actor $actor): JsonResponse
    {
        if (! $this->wantsJson($request)) {
            return response()->json(
                data: ['error' => 'This endpoint serves ActivityPub JSON. Use an appropriate Accept header.'],
                status: 406,
            );
        }

        $page = max(1, (int) $request->query('page', '1'));
        $perPage = 20;

        $query = Following::with('remoteActor')
            ->where('actor_id', $actor->id)
            ->where('status', FollowerStatus::Accepted);

        $totalItems = $query->count();

        $following = $query->latest()->get();
        $items = $following->map(fn (Following $f) => $f->remoteActor->actor_url)->values()->toArray();

        $totalPages = (int) ceil($totalItems / $perPage);

        if ($totalPages <= 1) {
            $collection = OrderedCollection::make(
                id: $actor->following_url,
                items: $items,
                totalItems: $totalItems,
            );
        } else {
            $paginated = $following->forPage(page: $page, perPage: $perPage);
            $pageItems = $paginated->map(fn (Following $f) => $f->remoteActor->actor_url)->values()->toArray();

            $next = $page < $totalPages ? $actor->following_url.'?page='.($page + 1) : null;
            $prev = $page > 1 ? $actor->following_url.'?page='.($page - 1) : null;

            $collection = OrderedCollection::makePage(
                id: $actor->following_url.'?page='.$page,
                partOf: $actor->following_url,
                items: $pageItems,
                totalItems: $totalItems,
                next: $next,
                prev: $prev,
            );
        }

        return $this->activityPubResponse($request, $collection);
    }
}
