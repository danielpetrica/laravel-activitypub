<?php

namespace DanielPetrica\LaravelActivityPub\Contracts;

interface ActorProfileContract
{
    public function getPreferredUsername(): string;

    public function getDisplayName(): string;

    public function getSummary(): ?string;

    public function getIconUrl(): ?string;

    public function getHeaderImageUrl(): ?string;
}
