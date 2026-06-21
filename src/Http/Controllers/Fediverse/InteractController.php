<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse;

use DanielPetrica\LaravelActivityPub\Enums\ActivityObjectType;
use DanielPetrica\LaravelActivityPub\Enums\ActivityType;
use DanielPetrica\LaravelActivityPub\Jobs\DeliverActivity;
use DanielPetrica\LaravelActivityPub\Models\Activity;
use DanielPetrica\LaravelActivityPub\Models\Actor;
use DanielPetrica\LaravelActivityPub\Models\RemoteActor;
use DanielPetrica\LaravelActivityPub\Services\ActivityBuilder;
use DanielPetrica\LaravelActivityPub\Services\RemoteActorResolver;
use DanielPetrica\LaravelActivityPub\Traits\ResolvesLocalActor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

final class InteractController extends Controller
{
    use ResolvesLocalActor;

    public function follow(Request $request): RedirectResponse
    {
        $request->validate(rules: [
            'remote_actor_url' => ['required', 'string', 'max:2048'],
        ]);

        $localActor = $this->resolveLocalActor();

        $remoteActorUrl = $request->input(key: 'remote_actor_url');
        $remoteActor = $this->resolveRemoteActor(actorUrl: $remoteActorUrl, localActor: $localActor);

        if ($remoteActor === null) {
            return redirect()
                ->back()
                ->withErrors(['remote_actor_url' => 'Could not resolve the remote actor']);
        }

        $activity = ActivityBuilder::follow(actor: $localActor, objectUrl: $remoteActorUrl);

        $record = Activity::query()->create(attributes: [
            'actor_id' => $localActor->id,
            'type' => ActivityType::Follow,
            'payload' => $activity,
            'is_incoming' => false,
            'remote_actor_id' => $remoteActor->id,
        ]);

        if (config(key: 'activitypub.federation.enabled')) {
            DeliverActivity::dispatch(
                inboxUrl: $remoteActor->inbox_url,
                activity: $activity,
                actor: $localActor,
                activityId: $record->id,
            );
        }

        Log::debug(
            message: 'InteractController: Follow activity dispatched',
            context: [
                'localActor' => $localActor->username,
                'targetActor' => $remoteActorUrl,
            ],
        );

        return redirect()
            ->route(route: 'fediverse.following')
            ->with(key: 'success', value: 'Follow request sent!');
    }

    public function unfollow(Request $request): RedirectResponse
    {
        $request->validate(rules: [
            'remote_actor_url' => ['required', 'string', 'max:2048'],
        ]);

        $localActor = $this->resolveLocalActor();

        $remoteActorUrl = $request->input(key: 'remote_actor_url');

        $remoteActor = RemoteActor::query()
            ->where(column: 'actor_url', operator: '=', value: $remoteActorUrl)
            ->first();

        if ($remoteActor === null) {
            return redirect()
                ->back()
                ->withErrors(['remote_actor_url' => 'Remote actor not found']);
        }

        $undoActivity = ActivityBuilder::undoFollow(actor: $localActor, objectUrl: $remoteActorUrl);

        $undoRecord = Activity::query()->create(attributes: [
            'actor_id' => $localActor->id,
            'type' => ActivityType::Undo,
            'payload' => $undoActivity,
            'is_incoming' => false,
            'remote_actor_id' => $remoteActor->id,
        ]);

        if (config(key: 'activitypub.federation.enabled')) {
            DeliverActivity::dispatch(
                inboxUrl: $remoteActor->inbox_url,
                activity: $undoActivity,
                actor: $localActor,
                activityId: $undoRecord->id,
            );
        }

        Activity::query()
            ->where(column: 'actor_id', operator: '=', value: $localActor->id)
            ->where(column: 'type', operator: '=', value: ActivityType::Follow)
            ->where(column: 'remote_actor_id', operator: '=', value: $remoteActor->id)
            ->delete();

        return redirect()
            ->route(route: 'fediverse.following')
            ->with(key: 'success', value: 'Unfollowed successfully');
    }

    public function like(Request $request): RedirectResponse
    {
        $request->validate(rules: [
            'remote_object_url' => ['required', 'string', 'max:2048'],
            'remote_actor_url' => ['required', 'string', 'max:2048'],
        ]);

        $localActor = $this->resolveLocalActor();

        $remoteObjectUrl = $request->input(key: 'remote_object_url');
        $remoteActorUrl = $request->input(key: 'remote_actor_url');

        $remoteActor = RemoteActor::query()
            ->where(column: 'actor_url', operator: '=', value: $remoteActorUrl)
            ->first();

        $likeActivity = ActivityBuilder::like(actor: $localActor, objectUrl: $remoteObjectUrl);

        $likeRecord = Activity::query()->create(attributes: [
            'actor_id' => $localActor->id,
            'type' => ActivityType::Like,
            'payload' => $likeActivity,
            'is_incoming' => false,
            'remote_actor_id' => $remoteActor?->id,
        ]);

        if ($remoteActor !== null && config(key: 'activitypub.federation.enabled')) {
            DeliverActivity::dispatch(
                inboxUrl: $remoteActor->inbox_url,
                activity: $likeActivity,
                actor: $localActor,
                activityId: $likeRecord->id,
            );
        }

        return redirect()
            ->back()
            ->with(key: 'success', value: 'Liked!');
    }

    public function boost(Request $request): RedirectResponse
    {
        $request->validate(rules: [
            'remote_object_url' => ['required', 'string', 'max:2048'],
            'remote_actor_url' => ['required', 'string', 'max:2048'],
        ]);

        $localActor = $this->resolveLocalActor();

        $remoteObjectUrl = $request->input(key: 'remote_object_url');
        $remoteActorUrl = $request->input(key: 'remote_actor_url');

        $remoteActor = RemoteActor::query()
            ->where(column: 'actor_url', operator: '=', value: $remoteActorUrl)
            ->first();

        $announceActivity = ActivityBuilder::announce(actor: $localActor, objectUrl: $remoteObjectUrl);

        $announceRecord = Activity::query()->create(attributes: [
            'actor_id' => $localActor->id,
            'type' => ActivityType::Announce,
            'payload' => $announceActivity,
            'is_incoming' => false,
            'remote_actor_id' => $remoteActor?->id,
        ]);

        if ($remoteActor !== null && config(key: 'activitypub.federation.enabled')) {
            DeliverActivity::dispatch(
                inboxUrl: $remoteActor->inbox_url,
                activity: $announceActivity,
                actor: $localActor,
                activityId: $announceRecord->id,
            );
        }

        return redirect()
            ->back()
            ->with(key: 'success', value: 'Boosted!');
    }

    public function reply(Request $request): RedirectResponse
    {
        $request->validate(rules: [
            'remote_object_url' => ['required', 'string', 'max:2048'],
            'remote_actor_url' => ['required', 'string', 'max:2048'],
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $localActor = $this->resolveLocalActor();

        $remoteObjectUrl = $request->input(key: 'remote_object_url');
        $remoteActorUrl = $request->input(key: 'remote_actor_url');
        $content = $request->input(key: 'content');

        $remoteActor = RemoteActor::query()
            ->where(column: 'actor_url', operator: '=', value: $remoteActorUrl)
            ->first();

        $createActivity = ActivityBuilder::createNote(
            actor: $localActor,
            content: $content,
            inReplyToUrl: $remoteObjectUrl,
            to: [$remoteActorUrl],
        );

        $createRecord = Activity::query()->create(attributes: [
            'actor_id' => $localActor->id,
            'type' => ActivityType::Create,
            'object_type' => ActivityObjectType::Note->value,
            'payload' => $createActivity,
            'is_incoming' => false,
            'remote_actor_id' => $remoteActor?->id,
        ]);

        if ($remoteActor !== null && config(key: 'activitypub.federation.enabled')) {
            DeliverActivity::dispatch(
                inboxUrl: $remoteActor->inbox_url,
                activity: $createActivity,
                actor: $localActor,
                activityId: $createRecord->id,
            );
        }

        return redirect()
            ->back()
            ->with(key: 'success', value: 'Reply sent!');
    }

    protected function resolveRemoteActor(string $actorUrl, Actor $localActor): ?RemoteActor
    {
        return app(RemoteActorResolver::class)->resolve(actorUri: $actorUrl);
    }
}
