<?php

namespace DanielPetrica\LaravelActivityPub\Actions;

use DanielPetrica\LaravelActivityPub\Actions\Handlers\ActivityHandler;
use DanielPetrica\LaravelActivityPub\Events\InboxActivityReceived;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\RemoteActor;
use DanielPetrica\LaravelActivityPub\Services\RemoteActorResolver;
use Illuminate\Support\Facades\Log;

final class InboxProcessor
{
    private array $localActorCache = [];

    public function __construct(
        /** @var ActivityHandler[] */
        private array $handlers,
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

        $inboxRemoteActor = $this->resolveRemoteActorFromPayload(payload: $payload);
        event(new InboxActivityReceived(
            activityType: $type,
            localActorId: $actor->id,
            remoteActorUrl: $inboxRemoteActor?->actor_url,
        ));

        foreach ($this->handlers as $handler) {
            if ($handler->handles() === $type) {
                $handler->handle(actor: $actor, payload: $payload);

                return;
            }
        }

        Log::debug(
            message: 'InboxProcessor: Unknown activity type',
            context: ['type' => $type],
        );
    }

    protected function resolveLocalActor(array $payload): ?Actor
    {
        $object = $payload['object'] ?? null;

        $candidates = [];

        if (is_string($object)) {
            $candidates[] = basename($object);
        }

        if (is_array($object)) {
            $candidates = array_merge($candidates, array_filter([
                is_string($object['object'] ?? null) ? basename($object['object']) : null,
                is_string($object['id'] ?? null) ? basename($object['id']) : null,
            ]));
        }

        $urls = array_merge(
            (array) ($payload['to'] ?? []),
            (array) ($payload['cc'] ?? []),
        );

        foreach ($urls as $url) {
            if (is_string($url)) {
                $candidates[] = basename($url);
            }
        }

        $candidates = array_unique(array_filter($candidates));

        foreach ($candidates as $basename) {
            if (isset($this->localActorCache[$basename])) {
                return $this->localActorCache[$basename];
            }
        }

        $actor = Actor::query()
            ->whereIn(column: 'username', values: $candidates)
            ->first();

        if ($actor !== null) {
            $this->localActorCache[$actor->username] = $actor;

            return $actor;
        }

        return null;
    }

    protected function resolveRemoteActorFromPayload(array $payload): ?RemoteActor
    {
        return app(RemoteActorResolver::class)->resolveFromPayload(payload: $payload);
    }
}
