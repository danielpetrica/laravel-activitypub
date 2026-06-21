<?php

namespace DanielPetrica\LaravelActivityPub\Services;

use DanielPetrica\LaravelActivityPub\Contracts\ActorContract;

final class ActivityBuilder
{
    /**
     * @return array<string, mixed>
     */
    public static function follow(ActorContract $actor, string $objectUrl): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor->getActorId().'#follow/'.time(),
            'type' => 'Follow',
            'actor' => $actor->getActorId(),
            'object' => $objectUrl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function undoFollow(ActorContract $actor, string $objectUrl): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor->getActorId().'#undo-follow/'.time(),
            'type' => 'Undo',
            'actor' => $actor->getActorId(),
            'object' => [
                'type' => 'Follow',
                'actor' => $actor->getActorId(),
                'object' => $objectUrl,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function accept(ActorContract $actor, array $originalPayload): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor->getActorId().'#accepts/'.time(),
            'type' => 'Accept',
            'actor' => $actor->getActorId(),
            'object' => $originalPayload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function like(ActorContract $actor, string $objectUrl): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor->getActorId().'#like/'.time(),
            'type' => 'Like',
            'actor' => $actor->getActorId(),
            'object' => $objectUrl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function announce(ActorContract $actor, string $objectUrl): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor->getActorId().'#announce/'.time(),
            'type' => 'Announce',
            'actor' => $actor->getActorId(),
            'object' => $objectUrl,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function delete(ActorContract $actor, string $objectId): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor->getActorId().'#delete/'.time(),
            'type' => 'Delete',
            'actor' => $actor->getActorId(),
            'object' => [
                'id' => $objectId,
                'type' => 'Tombstone',
            ],
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function createNote(ActorContract $actor, string $content, string $inReplyToUrl, array $to): array
    {
        $noteId = $actor->getActorId().'/note/'.time();

        $note = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $noteId,
            'type' => 'Note',
            'content' => $content,
            'inReplyTo' => $inReplyToUrl,
            'attributedTo' => $actor->getActorId(),
            'to' => $to,
            'published' => now()->toIso8601String(),
        ];

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor->getActorId().'#create/'.time(),
            'type' => 'Create',
            'actor' => $actor->getActorId(),
            'object' => $note,
            'to' => $to,
        ];
    }
}
