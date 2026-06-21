<?php

namespace DanielPetrica\LaravelActivityPub\Contracts;

interface FederatedActorContract
{
    public function getActorId(): string;

    public function getInboxUrl(): string;

    public function getOutboxUrl(): string;

    public function getFollowersUrl(): string;

    public function getFollowingUrl(): string;

    public function getPublicKey(): string;

    public function getKeyId(): string;

    public function getPrivateKeyPem(): ?string;
}
