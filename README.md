# Laravel ActivityPub

A self-hosted ActivityPub server implementation for Laravel 13, enabling federation with the Fediverse (Mastodon, Pleroma, Misskey, Pixelfed, PeerTube, etc.).

## Features

- **WebFinger** — Actor discovery via `/.well-known/webfinger`
- **Actor profiles** — ActivityStreams JSON-LD representations
- **Inbox/Outbox** — `OrderedCollection` with pagination support
- **HTTP Signatures** — RSA-SHA256 signing and verification with Digest support
- **Federation** — Outbound delivery of `Create`, `Update`, `Delete`, `Follow`, `Accept`, `Like`, `Announce`, `Undo`, and replies
- **Follower management** — Track followers from remote servers
- **Shared inbox** — `POST /inbox` for instance-level activity delivery
- **NodeInfo** — `/.well-known/nodeinfo` and `/nodeinfo/2.0`
- **host-meta** — `/.well-known/host-meta` with WebFinger XRD template
- **Blade-based Fediverse web UI** — Dashboard, timeline, inbox, discover, profile editing
- **Artisan commands** — `activitypub:create-actor`, `activitypub:prune-activities`
- **Queue jobs** — Async delivery with retries, remote actor fetching with deduplication

## Installation

```bash
composer require danielpetrica/laravel-activitypub
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag=activitypub-config
php artisan vendor:publish --tag=activitypub-migrations
php artisan migrate
```

## Configuration

```env
ACTIVITYPUB_DOMAIN=https://your-domain.com
ACTIVITYPUB_FEDERATION_ENABLED=true   # enable outbound federation
ACTIVITYPUB_FEDIVERSE_ENABLED=true    # enable Blade-based web UI
```

## Creating an Actor

```bash
php artisan activitypub:create-actor --username=yourname --name="Your Name"
```

## Federating Content

Have your Eloquent model implement `FederatableContentContract` and use the `FederatesContent` trait:

```php
use DanielPetrica\LaravelActivityPub\Contracts\FederatableContentContract;
use DanielPetrica\LaravelActivityPub\Traits\FederatesContent;

class Post extends Model implements FederatableContentContract
{
    use FederatesContent;

    public function shouldFederate(): bool { return $this->status === 'published'; }
    public function activityPubActor(): ActorContract { /* return your actor */ }
    public function getActivityPubId(): string { return url("/posts/{$this->id}"); }
    // ... implement remaining contract methods
}
```

## Testing

```bash
vendor/bin/pest --filter=InboxProcessingTest
vendor/bin/pest --filter=ConsoleCommandsTest
```

## Postponed Features (TODO)

These items are planned for future releases:

- [ ] **Move / AlsoKnownAs** — Account migration support (see ActivityPub spec §5.4)
- [ ] **Block activity** — Blocking remote actors, filtering blocked content from timeline
- [ ] **Tailwind CSS build step** — The Fediverse web UI currently loads Tailwind via CDN. Replace with a compiled CSS build step for production.
- [ ] **Full `OrderedCollectionPage` pagination** — Currently returns `OrderedCollection` with `first`/`last` pointers. Proper page-by-page `OrderedCollectionPage` responses should be added.
- [ ] **JSON-LD compaction** — Handle compacted JSON-LD payloads from Pleroma/Akkoma.
- [ ] **`outbox_url` shared inbox** — Add `sharedInbox` to actor profiles for servers that support it.
- [ ] **Admin dashboard** — Filament-based admin panel for managing actors, viewing federation stats.

## License

MIT
