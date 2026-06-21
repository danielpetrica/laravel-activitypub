<?php

namespace DanielPetrica\LaravelActivityPub\Actions\Handlers;

use DanielPetrica\LaravelActivityPub\Enums\FollowerStatus;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\Following;
use DanielPetrica\LaravelActivityPub\Services\RemoteActorResolver;

final class HandleAcceptAction implements ActivityHandler
{
    public function __construct(
        private RemoteActorResolver $remoteActorResolver,
    ) {}

    public function handles(): string
    {
        return 'Accept';
    }

    public function handle(Actor $actor, array $payload): void
    {
        $object = $payload['object'] ?? [];

        if (is_array($object) && ($object['type'] ?? null) === 'Follow') {
            $remoteActor = $this->remoteActorResolver->resolveFromPayload(payload: $payload);

            if ($remoteActor !== null) {
                Following::query()
                    ->where('actor_id', '=', $actor->id)
                    ->where('remote_actor_id', '=', $remoteActor->id)
                    ->update(['status' => FollowerStatus::Accepted]);
            }
        }
    }
}
