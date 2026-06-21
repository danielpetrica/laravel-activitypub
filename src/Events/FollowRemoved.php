<?php

namespace DanielPetrica\LaravelActivityPub\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FollowRemoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $localActorId,
        public readonly string $remoteActorUrl,
    ) {}
}
