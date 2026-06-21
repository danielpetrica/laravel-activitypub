<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse;

use DanielPetrica\LaravelActivityPub\Enums\ActivityType;
use DanielPetrica\LaravelActivityPub\Enums\FollowerStatus;
use DanielPetrica\LaravelActivityPub\Models\Activity;
use DanielPetrica\LaravelActivityPub\Models\Follower;
use DanielPetrica\LaravelActivityPub\Traits\ResolvesLocalActor;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    use ResolvesLocalActor;

    public function __invoke(): View
    {
        $user = auth()->user();
        $localActor = $this->resolveLocalActor();

        $followerCount = Follower::query()
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'status', operator: '=', value: FollowerStatus::Accepted)
            ->count();

        $followingCount = Activity::query()
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'type', operator: '=', value: ActivityType::Follow)
            ->where(column: 'is_incoming', operator: '=', value: false)
            ->distinct()
            ->count(columns: 'remote_actor_id');

        $incomingCount = Activity::query()
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'is_incoming', operator: '=', value: true)
            ->count();

        $outgoingCount = Activity::query()
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'is_incoming', operator: '=', value: false)
            ->count();

        $recentInbox = Activity::query()
            ->with(relations: 'remoteActor')
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'is_incoming', operator: '=', value: true)
            ->latest()
            ->limit(value: 5)
            ->get();

        $recentOutbox = Activity::query()
            ->with(relations: 'remoteActor')
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'is_incoming', operator: '=', value: false)
            ->latest()
            ->limit(value: 5)
            ->get();

        $parsedUrl = parse_url(url: $localActor->actor_id);

        return view(view: 'activitypub::fediverse.dashboard', data: [
            'followerCount' => $followerCount,
            'followingCount' => $followingCount,
            'incomingCount' => $incomingCount,
            'outgoingCount' => $outgoingCount,
            'recentInbox' => $recentInbox,
            'recentOutbox' => $recentOutbox,
            'actor' => $user,
            'localActor' => $localActor,
            'actorDomain' => $parsedUrl['host'].(isset($parsedUrl['port']) ? ':'.$parsedUrl['port'] : ''),
        ]);
    }
}
