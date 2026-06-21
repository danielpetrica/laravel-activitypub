<?php

use DanielPetrica\LaravelActivityPub\Models\Actor;

it('returns 404 for a non-existent actor', function (): void {
    $response = $this->getJson(
        uri: route(name: 'activitypub.actor', parameters: ['actor' => 'nonexistent']),
        headers: ['Accept' => 'application/activity+json'],
    );

    $response->assertStatus(status: 404);
});

it('returns actor JSON-LD with Accept header', function (): void {
    $domain = parse_url(url: config('app.url'), component: PHP_URL_HOST);

    $actor = Actor::query()->create(attributes: [
        'username' => 'jane',
        'name' => 'Jane Doe',
        'summary' => '<p>A test actor.</p>',
        'icon_url' => 'https://example.com/avatar.jpg',
        'image_url' => 'https://example.com/banner.jpg',
        'public_key_pem' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5jDP0FXxYWCP8uFU
-----END PUBLIC KEY-----',
        'private_key_pem' => '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA5jDP0FXxYWCP8uFU
-----END RSA PRIVATE KEY-----',
    ]);

    $response = $this->getJson(
        uri: route(name: 'activitypub.actor', parameters: ['actor' => 'jane']),
        headers: ['Accept' => 'application/activity+json'],
    );

    $response->assertStatus(status: 200);
    $response->assertJsonStructure([
        '@context',
        'id',
        'type',
        'preferredUsername',
        'name',
        'inbox',
        'outbox',
        'followers',
        'following',
        'publicKey' => [
            'id',
            'owner',
            'publicKeyPem',
        ],
    ]);

    $baseUrl = config('app.url');

    $response->assertJson([
        'type' => 'Person',
        'preferredUsername' => 'jane',
        'name' => 'Jane Doe',
        'summary' => '<p>A test actor.</p>',
        'id' => $baseUrl.'/users/jane',
    ]);

    // Verify URL structure
    $response->assertJson([
        'inbox' => $baseUrl.'/users/jane/inbox',
        'outbox' => $baseUrl.'/users/jane/outbox',
        'followers' => $baseUrl.'/users/jane/followers',
        'following' => $baseUrl.'/users/jane/following',
        'publicKey' => [
            'id' => $baseUrl.'/users/jane#main-key',
            'owner' => $baseUrl.'/users/jane',
        ],
    ]);
});

it('returns actor JSON-LD without explicit Accept header', function (): void {
    $actor = Actor::query()->create(attributes: [
        'username' => 'bob',
        'name' => 'Bob',
        'public_key_pem' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5jDP0FXxYWCP8uFU
-----END PUBLIC KEY-----',
        'private_key_pem' => '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA5jDP0FXxYWCP8uFU
-----END RSA PRIVATE KEY-----',
    ]);

    $response = $this->getJson(
        uri: route(name: 'activitypub.actor', parameters: ['actor' => 'bob']),
    );

    $response->assertStatus(status: 200);
    $response->assertJson([
        'type' => 'Person',
        'preferredUsername' => 'bob',
    ]);
});

it('returns actor JSON-LD without Accept header returns correct Content-Type', function (): void {
    Actor::query()->create(attributes: [
        'username' => 'carol',
        'name' => 'Carol',
        'public_key_pem' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5jDP0FXxYWCP8uFU
-----END PUBLIC KEY-----',
        'private_key_pem' => '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA5jDP0FXxYWCP8uFU
-----END RSA PRIVATE KEY-----',
    ]);

    $response = $this->getJson(
        uri: route(name: 'activitypub.actor', parameters: ['actor' => 'carol']),
    );

    $response->assertStatus(status: 200);
    $response->assertHeader(headerName: 'Content-Type', value: 'application/activity+json');
});

it('omits optional fields when not set', function (): void {
    $actor = Actor::query()->create(attributes: [
        'username' => 'minimal',
        'name' => 'Minimal',
        'public_key_pem' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5jDP0FXxYWCP8uFU
-----END PUBLIC KEY-----',
        'private_key_pem' => '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA5jDP0FXxYWCP8uFU
-----END RSA PRIVATE KEY-----',
    ]);

    $response = $this->getJson(
        uri: route(name: 'activitypub.actor', parameters: ['actor' => 'minimal']),
        headers: ['Accept' => 'application/activity+json'],
    );

    $response->assertStatus(status: 200);
    $response->assertJsonMissingPath(path: 'summary');
    $response->assertJsonMissingPath(path: 'icon');
    $response->assertJsonMissingPath(path: 'image');
});
