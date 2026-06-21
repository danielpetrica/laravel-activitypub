<?php

namespace DanielPetrica\LaravelActivityPub\Traits;

use DanielPetrica\LaravelActivityPub\Contracts\ActorContract;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesLocalActor
{
    protected function resolveLocalActor(): Actor
    {
        $user = auth()->user();

        if (! ($user instanceof ActorContract)) {
            throw new ModelNotFoundException(
                message: 'The authenticated user must implement '.ActorContract::class,
            );
        }

        return Actor::query()
            ->where(column: 'username', operator: '=', value: $user->getPreferredUsername())
            ->firstOrFail();
    }
}
