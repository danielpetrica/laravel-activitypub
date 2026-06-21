<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse;

use DanielPetrica\LaravelActivityPub\Enums\ActivityType;
use DanielPetrica\LaravelActivityPub\Models\Activity;
use DanielPetrica\LaravelActivityPub\Traits\ResolvesLocalActor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class FollowingController extends Controller
{
    use ResolvesLocalActor;

    public function __invoke(): View
    {
        $user = auth()->user();
        $localActor = $this->resolveLocalActor();

        $activities = Activity::query()
            ->with(relations: 'remoteActor')
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'type', operator: '=', value: ActivityType::Follow)
            ->where(column: 'is_incoming', operator: '=', value: false)
            ->whereNotNull('remote_actor_id')
            ->latest()
            ->get();

        $following = $this->deduplicateByRemoteActor($activities);

        return view(view: 'activitypub::fediverse.following', data: [
            'following' => $following,
            'actor' => $user,
        ]);
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, Activity>
     */
    protected function deduplicateByRemoteActor(Collection $activities): Collection
    {
        $seen = [];

        return $activities->filter(function (Activity $activity) use (&$seen) {
            if ($activity->remote_actor_id === null) {
                return false;
            }

            if (in_array(needle: $activity->remote_actor_id, haystack: $seen, strict: true)) {
                return false;
            }

            $seen[] = $activity->remote_actor_id;

            return true;
        })->values();
    }
}
