<?php

namespace DanielPetrica\LaravelActivityPub\Actions\Handlers;

use DanielPetrica\LaravelActivityPub\Enums\ActivityType;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Services\ActivityPubService;
use DanielPetrica\LaravelActivityPub\Services\RemoteActorResolver;

final class HandleLikeAction implements ActivityHandler
{
    public function __construct(
        private ActivityPubService $activityPubService,
        private RemoteActorResolver $remoteActorResolver,
    ) {}

    public function handles(): string
    {
        return 'Like';
    }

    public function handle(Actor $actor, array $payload): void
    {
        $remoteActor = $this->remoteActorResolver->resolveFromPayload(payload: $payload);

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Like,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );
    }
}
