<?php

use DanielPetrica\LaravelActivityPub\Models\Actor;

it('creates an actor with valid options', function (): void {
    $this->artisan(command: 'activitypub:create-actor', parameters: [
        '--username' => 'newactor',
        '--name' => 'New Actor',
    ])->assertSuccessful();

    $actor = Actor::query()->where(column: 'username', operator: '=', value: 'newactor')->first();

    expect($actor)->not->toBeNull()
        ->and($actor->name)->toBe('New Actor')
        ->and($actor->public_key_pem)->toStartWith('-----BEGIN PUBLIC KEY-----')
        ->and($actor->private_key_pem)->toStartWith('-----BEGIN');
});

it('fails when username is already taken', function (): void {
    Actor::query()->create(attributes: [
        'username' => 'existing',
        'name' => 'Existing',
        'public_key_pem' => 'pk',
        'private_key_pem' => 'sk',
    ]);

    $this->artisan(command: 'activitypub:create-actor', parameters: [
        '--username' => 'existing',
        '--name' => 'Duplicate',
    ])->assertFailed();
});

it('fails when username contains invalid characters', function (): void {
    $this->artisan(command: 'activitypub:create-actor', parameters: [
        '--username' => 'invalid user!',
        '--name' => 'Invalid',
    ])->assertFailed();
});

it('can prune old delivered activities', function (): void {
    Actor::query()->create(attributes: [
        'username' => 'pruner',
        'name' => 'Pruner',
        'public_key_pem' => 'pk',
        'private_key_pem' => 'sk',
    ]);

    $this->artisan(command: 'activitypub:prune-activities', parameters: [
        '--days' => 30,
    ])->assertSuccessful();
});
