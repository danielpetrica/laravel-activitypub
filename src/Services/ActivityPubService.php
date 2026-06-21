<?php

namespace DanielPetrica\LaravelActivityPub\Services;

use DanielPetrica\LaravelActivityPub\Actions\InboxProcessor;
use DanielPetrica\LaravelActivityPub\Contracts\ActorContract;
use DanielPetrica\LaravelActivityPub\Contracts\FederatableContentContract;
use DanielPetrica\LaravelActivityPub\Enums\ActivityType;
use DanielPetrica\LaravelActivityPub\Enums\FollowerStatus;
use DanielPetrica\LaravelActivityPub\Jobs\DeliverActivity;
use DanielPetrica\LaravelActivityPub\Jobs\FetchRemoteActor;
use DanielPetrica\LaravelActivityPub\Models\Activity;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\Follower;
use DanielPetrica\LaravelActivityPub\Models\RemoteActor;

final class ActivityPubService
{
    public function __construct(
        private HttpSignatureService $httpSignatureService,
        private WebFingerService $webFingerService,
        private RemoteActorResolver $remoteActorResolver,
    ) {}

    public function sendCreate(FederatableContentContract $content): void
    {
        $actor = $content->activityPubActor();
        $object = $this->buildObject(content: $content);
        $activity = $this->buildActivity(type: 'Create', actor: $actor, object: $object);

        $localActor = $this->resolveLocalActor(actor: $actor);

        if ($localActor !== null) {
            $record = $this->recordActivity(
                localActor: $localActor,
                type: ActivityType::Create,
                remoteActor: null,
                payload: $activity,
            );

            $this->deliverToFollowers(actor: $actor, activity: $activity, activityId: $record->id);
        }
    }

    public function sendUpdate(FederatableContentContract $content): void
    {
        $actor = $content->activityPubActor();
        $object = $this->buildObject(content: $content);
        $activity = $this->buildActivity(type: 'Update', actor: $actor, object: $object);

        $localActor = $this->resolveLocalActor(actor: $actor);

        if ($localActor !== null) {
            $record = $this->recordActivity(
                localActor: $localActor,
                type: ActivityType::Update,
                remoteActor: null,
                payload: $activity,
            );

            $this->deliverToFollowers(actor: $actor, activity: $activity, activityId: $record->id);
        }
    }

    public function sendDelete(string $objectId, ActorContract $actor): void
    {
        $activity = ActivityBuilder::delete(actor: $actor, objectId: $objectId);

        $localActor = $this->resolveLocalActor(actor: $actor);

        if ($localActor !== null) {
            $record = $this->recordActivity(
                localActor: $localActor,
                type: ActivityType::Delete,
                remoteActor: null,
                payload: $activity,
            );

            $this->deliverToFollowers(actor: $actor, activity: $activity, activityId: $record->id);
        }
    }

    public function handleInbox(array $payload): void
    {
        app(InboxProcessor::class)->process(payload: $payload);
    }

    public function resolveRemoteActor(string $actorUri): ?RemoteActor
    {
        $existing = RemoteActor::query()
            ->where(column: 'actor_url', operator: '=', value: $actorUri)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $url = $this->webFingerService->resolve(resource: $actorUri);

        if ($url === null) {
            return null;
        }

        $data = $this->remoteActorResolver->fetchActorData(actorUri: $actorUri);

        if ($data === null) {
            return null;
        }

        FetchRemoteActor::dispatch(
            actorUri: $actorUri,
            data: $data,
        );

        return $this->remoteActorResolver->upsertFromData(actorUri: $actorUri, data: $data);
    }

    public function resolveWebFinger(string $resource): ?array
    {
        return $this->webFingerService->resolve(resource: $resource);
    }

    protected function buildObject(FederatableContentContract $content): array
    {
        $object = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $content->getActivityPubId(),
            'type' => $content->getActivityPubType(),
            'attributedTo' => $content->getActivityPubAttributedTo(),
            'name' => $content->getActivityPubName(),
            'content' => $content->getActivityPubContent(),
            'summary' => $content->getActivityPubSummary(),
            'url' => $content->getActivityPubUrl(),
            'published' => $content->getActivityPubPublishedAt(),
            'to' => [$content->getActivityPubTo()],
            'cc' => [$content->getActivityPubCc()],
        ];

        $object = array_filter($object, fn ($value) => $value !== null);

        $attachments = $content->getActivityPubAttachments();

        if ($attachments !== []) {
            $object['attachment'] = $attachments;
        }

        $tags = $content->getActivityPubTags();

        if ($tags !== []) {
            $object['tag'] = $tags;
        }

        return $object;
    }

    protected function buildActivity(string $type, ActorContract $actor, array $object): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor->getActorId().'#'.$type.'/'.time(),
            'type' => $type,
            'actor' => $actor->getActorId(),
            'object' => $object,
            'to' => [$object['to'] ?? 'https://www.w3.org/ns/activitystreams#Public'],
            'cc' => [$object['cc'] ?? $actor->getFollowersUrl()],
        ];
    }

    protected function deliverToFollowers(ActorContract $actor, array $activity, ?int $activityId = null): void
    {
        if (! config('activitypub.federation.enabled')) {
            return;
        }

        $localActor = $this->resolveLocalActor(actor: $actor);

        if ($localActor === null) {
            return;
        }

        $followers = Follower::query()
            ->with(relations: 'remoteActor')
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'status', operator: '=', value: FollowerStatus::Accepted)
            ->get();

        foreach ($followers as $follower) {
            DeliverActivity::dispatch(
                inboxUrl: $follower->remoteActor->inbox_url,
                activity: $activity,
                actor: $localActor,
                activityId: $activityId,
            );
        }
    }

    public function recordActivity(
        Actor $localActor,
        ActivityType $type,
        ?RemoteActor $remoteActor,
        array $payload,
        bool $isIncoming = false,
    ): Activity {
        $object = $payload['object'] ?? [];
        $objectId = is_string($object) ? $object : ($object['id'] ?? null);
        $objectType = is_array($object) ? ($object['type'] ?? null) : null;

        return Activity::query()->create(attributes: [
            'actor_id' => $localActor->id,
            'type' => $type,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'payload' => $payload,
            'status' => $isIncoming ? 'received' : 'pending',
            'delivered_at' => $isIncoming ? now() : null,
            'remote_actor_id' => $remoteActor?->id,
            'is_incoming' => $isIncoming,
        ]);
    }

    public function sendFollow(ActorContract $actor, RemoteActor $target): void
    {
        $localActor = $this->resolveLocalActor(actor: $actor);

        if ($localActor === null) {
            return;
        }

        $activity = ActivityBuilder::follow(actor: $actor, objectUrl: $target->actor_url);

        $record = $this->recordActivity(
            localActor: $localActor,
            type: ActivityType::Follow,
            remoteActor: $target,
            payload: $activity,
        );

        DeliverActivity::dispatch(
            inboxUrl: $target->inbox_url,
            activity: $activity,
            actor: $localActor,
            activityId: $record->id,
        );
    }

    public function sendUnfollow(ActorContract $actor, Follower $follower): void
    {
        $localActor = $this->resolveLocalActor(actor: $actor);
        $target = $follower->remoteActor;

        if ($localActor === null || $target === null) {
            return;
        }

        $activity = ActivityBuilder::undoFollow(actor: $actor, objectUrl: $target->actor_url);

        $record = $this->recordActivity(
            localActor: $localActor,
            type: ActivityType::Undo,
            remoteActor: $target,
            payload: $activity,
        );

        DeliverActivity::dispatch(
            inboxUrl: $target->inbox_url,
            activity: $activity,
            actor: $localActor,
            activityId: $record->id,
        );

        $follower->delete();
    }

    public function sendLike(ActorContract $actor, string $objectUrl): void
    {
        $localActor = $this->resolveLocalActor(actor: $actor);

        if ($localActor === null) {
            return;
        }

        $activity = ActivityBuilder::like(actor: $actor, objectUrl: $objectUrl);

        $record = $this->recordActivity(
            localActor: $localActor,
            type: ActivityType::Like,
            remoteActor: null,
            payload: $activity,
        );

        $this->deliverToObjectAuthor(
            objectUrl: $objectUrl,
            activity: $activity,
            localActor: $localActor,
            activityId: $record->id,
        );
    }

    public function sendAnnounce(ActorContract $actor, string $objectUrl): void
    {
        $localActor = $this->resolveLocalActor(actor: $actor);

        if ($localActor === null) {
            return;
        }

        $activity = ActivityBuilder::announce(actor: $actor, objectUrl: $objectUrl);

        $record = $this->recordActivity(
            localActor: $localActor,
            type: ActivityType::Announce,
            remoteActor: null,
            payload: $activity,
        );

        $this->deliverToFollowers(actor: $actor, activity: $activity, activityId: $record->id);
    }

    public function sendNote(ActorContract $actor, string $content, string $inReplyToUrl): void
    {
        $localActor = $this->resolveLocalActor(actor: $actor);

        if ($localActor === null) {
            return;
        }

        $activity = ActivityBuilder::createNote(
            actor: $actor,
            content: $content,
            inReplyToUrl: $inReplyToUrl,
            to: ['https://www.w3.org/ns/activitystreams#Public'],
        );

        $record = $this->recordActivity(
            localActor: $localActor,
            type: ActivityType::Create,
            remoteActor: null,
            payload: $activity,
        );

        $this->deliverToObjectAuthor(
            objectUrl: $inReplyToUrl,
            activity: $activity,
            localActor: $localActor,
            activityId: $record->id,
        );
    }

    protected function deliverToObjectAuthor(string $objectUrl, array $activity, Actor $localActor, ?int $activityId = null): void
    {
        $objectParts = parse_url(url: $objectUrl);
        $objectDomain = $objectParts['host'] ?? null;

        if ($objectDomain === null) {
            return;
        }

        $remoteActor = RemoteActor::query()
            ->where(column: 'domain', operator: '=', value: $objectDomain)
            ->first();

        if ($remoteActor === null) {
            return;
        }

        DeliverActivity::dispatch(
            inboxUrl: $remoteActor->inbox_url,
            activity: $activity,
            actor: $localActor,
            activityId: $activityId,
        );
    }

    protected function resolveLocalActor(ActorContract $actor): ?Actor
    {
        return Actor::query()
            ->where(column: 'username', operator: '=', value: $actor->getPreferredUsername())
            ->first();
    }
}
