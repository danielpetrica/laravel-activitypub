<?php

namespace DanielPetrica\LaravelActivityPub;

use DanielPetrica\LaravelActivityPub\Services\ActivityPubService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void sendCreate(\DanielPetrica\LaravelActivityPub\Contracts\FederatableContentContract $content)
 * @method static void sendUpdate(\DanielPetrica\LaravelActivityPub\Contracts\FederatableContentContract $content)
 * @method static void sendDelete(string $objectId, \DanielPetrica\LaravelActivityPub\Contracts\ActorContract $actor)
 * @method static void handleInbox(array $payload)
 * @method static ?\DanielPetrica\LaravelActivityPub\Models\RemoteActor resolveRemoteActor(string $actorUri)
 * @method static ?array resolveWebFinger(string $resource)
 *
 * @see ActivityPubService
 */
final class ActivityPub extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'activitypub';
    }
}
