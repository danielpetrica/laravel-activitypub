<?php

namespace DanielPetrica\LaravelActivityPub\Traits;

use DanielPetrica\LaravelActivityPub\ActivityPub;
use DanielPetrica\LaravelActivityPub\Contracts\FederatableContentContract;
use Illuminate\Database\Eloquent\Model;

trait FederatesContent
{
    public static function bootFederatesContent(): void
    {
        static::saved(callback: function (Model $model): void {
            if (! ($model instanceof FederatableContentContract)) {
                return;
            }

            if (! $model->shouldFederate()) {
                return;
            }

            if ($model->wasRecentlyCreated) {
                ActivityPub::sendCreate(content: $model);
            } else {
                ActivityPub::sendUpdate(content: $model);
            }
        });

        static::deleted(callback: function (FederatableContentContract $model): void {
            if (! $model->shouldFederate()) {
                return;
            }

            ActivityPub::sendDelete(
                objectId: $model->getActivityPubId(),
                actor: $model->activityPubActor(),
            );
        });
    }
}
