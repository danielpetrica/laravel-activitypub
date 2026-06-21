<?php

namespace DanielPetrica\LaravelActivityPub\Actions\Handlers;

use DanielPetrica\LaravelActivityPub\Enums\ActivityType;
use DanielPetrica\LaravelActivityPub\Events\FollowRemoved;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\Follower;
use DanielPetrica\LaravelActivityPub\Services\ActivityPubService;
use DanielPetrica\LaravelActivityPub\Services\RemoteActorResolver;

final class HandleUndoAction implements ActivityHandler
{
    public function __construct(
        private ActivityPubService $activityPubService,
        private RemoteActorResolver $remoteActorResolver,
    ) {}

    public function handles(): string
    {
        return 'Undo';
    }

    public function handle(Actor $actor, array $payload): void
    {
        $remoteActor = $this->remoteActorResolver->resolveFromPayload(payload: $payload);

        $object = $payload['object'] ?? [];

        if ($remoteActor !== null && is_array($object) && ($object['type'] ?? null) === 'Follow') {
            Follower::query()
                ->where(column: 'actor_id', operator: '=', value: $actor->id)
                ->where(column: 'remote_actor_id', operator: '=', value: $remoteActor->id)
                ->delete();

            event(new FollowRemoved(
                localActorId: $actor->id,
                remoteActorUrl: $remoteActor->actor_url,
            ));
        }

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Undo,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );
    }
}
