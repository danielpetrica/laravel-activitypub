<?php

use DanielPetrica\LaravelActivityPub\Enums\FollowerStatus;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\Follower;
use DanielPetrica\LaravelActivityPub\Models\RemoteActor;

beforeEach(function (): void {
    $this->actor = Actor::query()->create(attributes: [
        'username' => 'testuser',
        'name' => 'Test User',
        'public_key_pem' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5jDP0FXxYWCP8uFU
-----END PUBLIC KEY-----',
        'private_key_pem' => '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA5jDP0FXxYWCP8uFU
-----END RSA PRIVATE KEY-----',
    ]);
});

it('returns 401 when no signature header is present', function (): void {
    $response = $this->postJson(
        uri: route(name: 'activitypub.actor.inbox.store', parameters: ['actor' => $this->actor->username]),
        data: ['type' => 'Create', 'actor' => 'https://example.com/users/remote'],
    );

    $response->assertStatus(status: 401);
});

it('returns 401 for an invalid signature header format', function (): void {
    $response = $this->postJson(
        uri: route(name: 'activitypub.actor.inbox.store', parameters: ['actor' => $this->actor->username]),
        data: ['type' => 'Create', 'actor' => 'https://example.com/users/remote'],
        headers: ['Signature' => 'not-a-valid-signature'],
    );

    $response->assertStatus(status: 401);
});

it('returns 202 for incoming Create activity', function (): void {
    config()->set(key: 'activitypub.http_signatures.enabled', value: false);

    $response = $this->postJson(
        uri: route(name: 'activitypub.actor.inbox.store', parameters: ['actor' => $this->actor->username]),
        data: [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Create',
            'actor' => 'https://example.com/users/remote',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello world',
            ],
        ],
    );

    $response->assertStatus(status: 202);
});

it('returns 400 when payload is missing type field', function (): void {
    config()->set(key: 'activitypub.http_signatures.enabled', value: false);

    $response = $this->postJson(
        uri: route(name: 'activitypub.actor.inbox.store', parameters: ['actor' => $this->actor->username]),
        data: ['actor' => 'https://example.com/users/remote'],
    );

    $response->assertStatus(status: 400);
});

it('returns 400 when payload is missing actor field', function (): void {
    config()->set(key: 'activitypub.http_signatures.enabled', value: false);

    $response = $this->postJson(
        uri: route(name: 'activitypub.actor.inbox.store', parameters: ['actor' => $this->actor->username]),
        data: ['type' => 'Create'],
    );

    $response->assertStatus(status: 400);
});

it('processes incoming Follow activity and creates a follower', function (): void {
    config()->set(key: 'activitypub.http_signatures.enabled', value: false);
    config()->set(key: 'activitypub.federation.enabled', value: false);

    $response = $this->postJson(
        uri: route(name: 'activitypub.actor.inbox.store', parameters: ['actor' => $this->actor->username]),
        data: [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Follow',
            'actor' => 'https://example.com/users/remote',
            'object' => $this->actor->actor_id,
            'to' => [$this->actor->actor_id],
        ],
    );

    $response->assertStatus(status: 202);

    $this->assertDatabaseHas(table: 'followers', data: [
        'actor_id' => $this->actor->id,
        'status' => 'accepted',
    ]);

    $this->assertDatabaseHas(table: 'activities', data: [
        'actor_id' => $this->actor->id,
        'type' => 'Follow',
        'is_incoming' => true,
    ]);
});

it('records incoming Like activity', function (): void {
    config()->set(key: 'activitypub.http_signatures.enabled', value: false);

    $response = $this->postJson(
        uri: route(name: 'activitypub.actor.inbox.store', parameters: ['actor' => $this->actor->username]),
        data: [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Like',
            'actor' => 'https://example.com/users/remote',
            'object' => 'https://example.com/notes/123',
            'to' => [$this->actor->actor_id],
        ],
    );

    $response->assertStatus(status: 202);

    $this->assertDatabaseHas(table: 'activities', data: [
        'actor_id' => $this->actor->id,
        'type' => 'Like',
        'is_incoming' => true,
    ]);
});

it('processes incoming Undo Follow and removes follower', function (): void {
    config()->set(key: 'activitypub.http_signatures.enabled', value: false);
    config()->set(key: 'activitypub.federation.enabled', value: false);

    $remoteActor = RemoteActor::query()->create([
        'actor_url' => 'https://example.com/users/remote',
        'inbox_url' => 'https://example.com/users/remote/inbox',
        'username' => 'remote',
        'domain' => 'example.com',
    ]);

    Follower::query()->create([
        'actor_id' => $this->actor->id,
        'remote_actor_id' => $remoteActor->id,
        'status' => FollowerStatus::Accepted,
    ]);

    $response = $this->postJson(
        uri: route(name: 'activitypub.actor.inbox.store', parameters: ['actor' => $this->actor->username]),
        data: [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Undo',
            'actor' => 'https://example.com/users/remote',
            'object' => [
                'type' => 'Follow',
                'actor' => 'https://example.com/users/remote',
                'object' => $this->actor->actor_id,
            ],
        ],
    );

    $response->assertStatus(status: 202);

    $this->assertDatabaseMissing(table: 'followers', data: [
        'actor_id' => $this->actor->id,
        'remote_actor_id' => $remoteActor->id,
    ]);
});
