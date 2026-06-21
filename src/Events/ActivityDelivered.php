<?php

namespace DanielPetrica\LaravelActivityPub\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ActivityDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $activityId,
        public readonly string $inboxUrl,
        public readonly int $actorId,
    ) {}
}
