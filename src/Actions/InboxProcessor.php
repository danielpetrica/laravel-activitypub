<?php

namespace DanielPetrica\LaravelActivityPub\Actions;

use DanielPetrica\LaravelActivityPub\Enums\ActivityType;
use DanielPetrica\LaravelActivityPub\Enums\FollowerStatus;
use DanielPetrica\LaravelActivityPub\Jobs\DeliverActivity;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\Follower;
use DanielPetrica\LaravelActivityPub\Models\RemoteActor;
use DanielPetrica\LaravelActivityPub\Services\ActivityBuilder;
use DanielPetrica\LaravelActivityPub\Services\ActivityPubService;
use DanielPetrica\LaravelActivityPub\Services\RemoteActorResolver;
use Illuminate\Support\Facades\Log;

final class InboxProcessor
{
    public function __construct(
        private ActivityPubService $activityPubService,
    ) {}

    public function process(array $payload): void
    {
        $type = $payload['type'] ?? null;

        $actor = $this->resolveLocalActor(payload: $payload);

        if ($actor === null) {
            Log::debug(
                message: 'InboxProcessor: Could not resolve local actor from payload',
                context: ['payload' => $payload],
            );

            return;
        }

        match ($type) {
            'Follow' => $this->handleFollow(actor: $actor, payload: $payload),
            'Like' => $this->handleLike(actor: $actor, payload: $payload),
            'Announce' => $this->handleAnnounce(actor: $actor, payload: $payload),
            'Undo' => $this->handleUndo(actor: $actor, payload: $payload),
            'Create' => $this->handleCreate(actor: $actor, payload: $payload),
            'Delete' => $this->handleDelete(actor: $actor, payload: $payload),
            'Update' => $this->handleUpdate(actor: $actor, payload: $payload),
            default => Log::debug(
                message: 'InboxProcessor: Unknown activity type',
                context: ['type' => $type],
            ),
        };
    }

    protected function resolveLocalActor(array $payload): ?Actor
    {
        $object = $payload['object'] ?? null;

        if (is_string($object)) {
            $found = Actor::query()
                ->where(column: 'username', operator: '=', value: basename($object))
                ->first();

            if ($found !== null) {
                return $found;
            }
        }

        if (is_array($object)) {
            $candidates = array_filter([
                $object['object'] ?? null,
                $object['id'] ?? null,
            ]);

            foreach ($candidates as $candidate) {
                if (is_string($candidate)) {
                    $actor = Actor::query()
                        ->where(column: 'username', operator: '=', value: basename($candidate))
                        ->first();

                    if ($actor !== null) {
                        return $actor;
                    }
                }
            }
        }

        $all = array_merge(
            (array) ($payload['to'] ?? []),
            (array) ($payload['cc'] ?? []),
        );

        foreach ($all as $url) {
            if (is_string($url)) {
                $actor = Actor::query()
                    ->where(column: 'username', operator: '=', value: basename($url))
                    ->first();

                if ($actor !== null) {
                    return $actor;
                }
            }
        }

        return null;
    }

    protected function resolveRemoteActorFromPayload(array $payload): ?RemoteActor
    {
        return app(RemoteActorResolver::class)->resolveFromPayload(payload: $payload);
    }

    protected function handleFollow(Actor $actor, array $payload): void
    {
        $remoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);

        if ($remoteActor === null) {
            return;
        }

        Follower::query()->updateOrCreate(
            attributes: [
                'actor_id' => $actor->id,
                'remote_actor_id' => $remoteActor->id,
            ],
            values: [
                'status' => FollowerStatus::Accepted,
            ],
        );

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Follow,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );

        $acceptActivity = ActivityBuilder::accept(
            actor: $actor,
            originalPayload: $payload,
        );

        $acceptRecord = $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Accept,
            remoteActor: $remoteActor,
            payload: $acceptActivity,
        );

        if (config(key: 'activitypub.federation.enabled')) {
            DeliverActivity::dispatch(
                inboxUrl: $remoteActor->inbox_url,
                activity: $acceptActivity,
                actor: $actor,
                activityId: $acceptRecord->id,
            );
        }
    }

    protected function handleLike(Actor $actor, array $payload): void
    {
        $remoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Like,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );
    }

    protected function handleAnnounce(Actor $actor, array $payload): void
    {
        $remoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Announce,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );
    }

    protected function handleUndo(Actor $actor, array $payload): void
    {
        $object = $payload['object'] ?? [];

        if (is_array($object) && ($object['type'] ?? null) === 'Follow') {
            $remoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);

            if ($remoteActor !== null) {
                Follower::query()
                    ->where(column: 'actor_id', operator: '=', value: $actor->id)
                    ->where(column: 'remote_actor_id', operator: '=', value: $remoteActor->id)
                    ->delete();
            }
        }

        $remoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Undo,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );
    }

    protected function handleCreate(Actor $actor, array $payload): void
    {
        $remoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Create,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );
    }

    protected function handleDelete(Actor $actor, array $payload): void
    {
        $remoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Delete,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );
    }

    protected function handleUpdate(Actor $actor, array $payload): void
    {
        $remoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);

        $this->activityPubService->recordActivity(
            localActor: $actor,
            type: ActivityType::Update,
            remoteActor: $remoteActor,
            payload: $payload,
            isIncoming: true,
        );
    }
}
