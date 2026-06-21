# Changelog

## [Unreleased]

### Cache headers with config toggle
- `config/activitypub.php` now has `cache.enabled` and `cache.ttl` settings
- `RespondsToAccept` trait, WebFinger, NodeInfo, and HostMeta controllers set `Cache-Control` when enabled
- Disable via `ACTIVITYPUB_CACHE_ENABLED=false`

### Actor type configurable
- New `config('activitypub.actor_type')` key (default `'Person'`)
- `ActorResource` now reads type from config instead of hardcoding

### Queue payload optimization
- `DeliverActivity` now stores only `activityModelId` and `actorId` instead of full ActivityPub JSON + Actor model
- Queue storage per job reduced from ~5-50KB to ~8 bytes (two integers + URL string)
- **BREAKING:** If you dispatch `DeliverActivity` manually, use `activityModelId:` and `actorId:` named arguments

---

## [v0.2.0] - 2026-06-21

### Architecture

#### Strategy pattern for inbox processing
- **BREAKING:** `InboxProcessor` no longer has `handleFollow()`, `handleLike()`, etc. methods. The `match` block was replaced with 10 handler classes under `Actions/Handlers/`, each implementing `ActivityHandler`:
  - `HandleFollowAction`
  - `HandleLikeAction`
  - `HandleAnnounceAction`
  - `HandleUndoAction`
  - `HandleCreateAction`
  - `HandleDeleteAction`
  - `HandleUpdateAction`
  - `HandleBlockAction`
  - `HandleAcceptAction`
  - `HandleRejectAction`
- Handlers are tagged in the container as `activitypub.activity-handlers` and injected into `InboxProcessor`
- `InboxProcessor` reduced from 342 to ~80 lines

#### Events system (6 new classes)
- `ActivityDelivered` — fired on successful outbound delivery
- `ActivityDeliveryFailed` — fired on permanent delivery failure
- `FollowReceived` — fired when remote actor follows local actor
- `FollowRemoved` — fired when remote actor unfollows local actor
- `BlockReceived` — fired when remote actor blocks local actor
- `InboxActivityReceived` — fired for every incoming activity

#### ActivityBuilder injectable
- **BREAKING:** `ActivityBuilder` methods changed from `public static` to instance methods
- New `ActivityBuilderContract` interface with all 7 method signatures
- Consumers calling `ActivityBuilder::follow()` (static) must now inject `ActivityBuilderContract`:
  ```php
  // Before
  use DanielPetrica\LaravelActivityPub\Services\ActivityBuilder;
  $activity = ActivityBuilder::follow(actor: $actor, objectUrl: $url);
  // After
  use DanielPetrica\LaravelActivityPub\Contracts\ActivityBuilderContract;
  public function __construct(private ActivityBuilderContract $activityBuilder) {}
  $activity = $this->activityBuilder->follow(actor: $actor, objectUrl: $url);
  ```
- `ActivityPubService` and `InteractController` updated to use injected `ActivityBuilderContract`

#### ActorContract segregation
- `ActorContract` now extends `ActorProfileContract` (profile methods) and `FederatedActorContract` (federation methods)
- **BREAKING:** `ActorContract` has a new method: `getPrivateKeyPem(): ?string`
- Consumers implementing `ActorContract` must add this method

#### DeliveryClient extraction
- HTTP delivery + signing logic extracted from duplicated code in `ActivityPubService` and `DeliverActivity` into `src/Services/DeliveryClient.php`
- Used by both async (queue) and sync (debug mode) delivery paths

### Protocol

#### Object fetching
- `HandleCreateAction` now fetches remote object data when `payload.object` is a bare URL string (ActivityPub spec §3.1)
- Uses `RemoteActorResolver::fetchActorData()` (SSRF-safe)

#### Shared inbox optimization
- **Migration needed:** `2026_06_21_000004_add_shared_inbox_to_remote_actors.php` adds `shared_inbox_url` column to `remote_actors`
- `RemoteActorResolver::upsertFromData()` now extracts and stores `endpoints.sharedInbox` from remote actor profiles
- All 5 delivery points (`deliverToFollowers`, `deliverSync`, `deliverToObjectAuthor`, `sendFollow`, `sendUnfollow`) now prefer `shared_inbox_url` over personal `inbox_url`
- Follower delivery grouped by unique inbox URL — one HTTP request per remote instance instead of one per follower

#### Block/Reject activity handling
- `Block` activities now remove follower records and fire `BlockReceived` event
- `Reject` activities now remove follower and following records
- `Accept` activities now update `Following` status to `Accepted`

#### Following model/table
- **Migration needed:** `2026_06_21_000003_create_following_table.php` creates the `following` table
- `FollowingController` API now returns real paginated data instead of an empty collection
- Fediverse UI `following.blade.php` updated to use `Following` model

#### ActorResource compliance
- Added `url`, `manuallyApprovesFollowers`, `endpoints.sharedInbox`, `type: Key` on publicKey
- `@context` now includes both `security/v1` and `security/v2`
- **BREAKING:** Actor JSON now includes these additional fields

#### OrderedCollection always includes first/last
- Single-page collections now always include `first` and `last` pointers (ActivityPub spec §5)

#### Accept header negotiation
- New `Concerns/RespondsToAccept` trait with `wantsJson()` and `activityPubResponse()` methods
- 6 controllers (Actor, Inbox GET, Outbox, Followers, Following, Featured) now check Accept header
- Browsers (Accept: text/html) receive 406 instead of raw JSON
- All ActivityPub responses include `Vary: Accept` header

#### WebFinger Content-Type
- Changed from `application/json` to `application/jrd+json` (RFC 7033)

#### NodeInfo configurable
- `version` and `openRegistrations` now read from config with env overrides

#### HostMeta scheme
- Uses actual scheme from `config('app.url')` instead of hardcoded `https://`

#### Activity IDs use UUID
- `ActivityBuilder` replaced `time()` with `Str::uuid()` for activity IDs, preventing collisions under concurrent creation

#### Announce/Undo/CreateNote protocol fixes
- `announce()` adds followers collection URL to `cc`
- `createNote()` accepts `$cc` parameter, adds `url` to Note object
- `undoFollow()` includes original Follow activity `id` in wrapped object

### Security

#### SSRF protection
- `WebFingerService` and `RemoteActorResolver` block requests to private/reserved IP ranges (127.0.0.0/8, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, ::1)
- DNS resolution before HTTP fetch to validate target isn't internal

#### Signature replay protection
- `VerifyHttpSignature` caches verified signatures with 120s TTL via `Cache::add()`

#### Digest enforcement
- Digest header required for all non-empty POST bodies to inbox
- Rejects unknown or weak digest algorithms

#### keyId validation
- `keyId` from Signature header validated for `https://` scheme
- Rejects URLs with user-info components or non-https schemes

#### Host header injection fix
- Singing string now uses canonical host from `config('activitypub.domain')` instead of `$request->getHost()`

#### Idempotency
- Inbox POST has cache-based idempotency keyed on `(keyId, signature, date)`

#### Content-Length limits
- Inbox endpoints reject payloads > 1MB with HTTP 413

#### Rate limiting per-IP
- Inbox rate limiter changed from global 60/min to per-IP 60/min
- Fediverse interaction routes throttled at 30/min

### Data layer

#### ActivityStatus enum
- **BREAKING:** `Activity::status` changed from plain string to `ActivityStatus` backed enum
- Values: `Pending`, `Delivered`, `Received`, `Failed`
- Comparisons against `$activity->status` now return `ActivityStatus` instances:
  ```php
  // Before
  if ($activity->status === 'pending') { ... }
  // After
  if ($activity->status === ActivityStatus::Pending) { ... }
  // Or compare by value
  if ($activity->status->value === 'pending') { ... }
  ```

#### Form Requests (validation extracted)
- **BREAKING:** Validation failure status codes changed from 400 to 422 for:
  - Inbox POST (`InboxRequest`): missing/invalid `type`, `actor`, `object`, `@context`
  - WebFinger GET (`WebFingerRequest`): missing `resource`, invalid `acct:` URI
  - Profile update (`ProfileUpdateRequest`): invalid `name`, `summary`, `icon_url`, `image_url`
- Clients expecting 400 for these errors must now expect 422

#### Performance indexes
- **Migration needed:** `2026_06_21_000002_add_performance_indexes.php` adds:
  - Composite `(actor_id, is_incoming, created_at)` on activities
  - Composite `(remote_actor_id, is_incoming, type, created_at)` on activities (for feed queries)
  - Composite `(remote_actor_id, actor_id, status)` on followers

#### Federation indexes
- **Migration needed:** `2026_06_21_000001_add_federation_indexes.php` adds:
  - Index on `remote_actors.domain`
  - Composite `(actor_id, status)` on followers
  - Composite `(status, created_at)` on activities

#### N+1 query fixes
- Fediverse inbox/outbox controllers now eager-load `actor` relation alongside `remoteActor`
- Timeline uses subquery instead of `pluck` + `whereIn`

### Performance

#### Chunked follower delivery
- `deliverToFollowers()` and `deliverSync()` use `->chunk(200)` instead of `->get()`
- Prevents OOM for actors with 10K+ followers (was ~500MB peak memory, now ~8MB)

#### Memoized actor resolution
- `ActivityPubService::resolveLocalActor()` memoizes per-request (avoiding redundant `actors` table queries)
- `RemoteActorResolver::resolveFromPayload()` memoizes per-request (avoiding redundant `remote_actors` queries)
- `InboxProcessor::resolveLocalActor()` now does single `whereIn()` query instead of up to 5 sequential queries

#### Pagination optimization
- Inbox/outbox API controllers skip the data query when `page > 1` (only execute COUNT)
- Followers endpoint now paginated (was loading all followers into memory)
- Page parameter bounded to prevent absurd OFFSET values

### Configuration

#### New env vars
- `ACTIVITYPUB_CACHE_ENABLED` (default `true`) — toggle cache headers
- `ACTIVITYPUB_FEDIVERSE_ENABLED` (default `true`) — toggle web UI
- **BREAKING:** Previous env var name `ACTIVITYPUB_FEDERIVERSE_ENABLED` (typo) is no longer read. Use `ACTIVITYPUB_FEDIVERSE_ENABLED` instead.

#### New config keys
```
'version' => '1.0.0'
'open_registrations' => false
'actor_type' => 'Person'
'cache' => ['enabled' => true, 'ttl' => 86400]
```

### ActivityBuilder protocol fixes
- `createNote()` now accepts `$cc` parameter; adds `url` property to Note object
- `announce()` includes `$actor->getFollowersUrl()` in `cc` field
- `undoFollow()` includes original Follow activity `id` in the nested object

### Delivery job resilience
- `DeliverActivity` backoff: [30s, 120s, 600s] instead of fixed 60s
- `failed()` method marks activity status as `failed` and logs warning
- `PruneOldActivities` uses `chunkById(1000)` for safe bulk deletion

### Rate limiting
- Inbox: per-IP, 60 requests/min (was global)
- Fediverse POST routes: 30 requests/min (new)
- `fediverse-interact` rate limiter registered in service provider

### Sync delivery type hints
- `sendCreateForActorSync()` returns typed array `array{record: Activity, results: list<array{actor_url, inbox_url, response_code}>}`

### Config publish
- Run `php artisan vendor:publish --tag=activitypub-config` to get the updated config with new keys
- Run `php artisan vendor:publish --tag=activitypub-migrations` to get the new migration files

---

## [v0.1.0] - 2026-06-21

### Initial release
- ActivityPub actor profiles (Person type)
- WebFinger discovery (`/.well-known/webfinger`)
- Inbox/Outbox as OrderedCollection with pagination
- HTTP Signatures (RSA-SHA256) for incoming verification and outbound signing
- Outbound federation: Create, Update, Delete, Follow, Accept, Like, Announce, Undo, replies
- Follower management (track followers from remote servers)
- Shared inbox (`POST /inbox` for instance-level delivery)
- NodeInfo 2.0 (`/.well-known/nodeinfo` and `/nodeinfo/2.0`)
- host-meta (`/.well-known/host-meta`)
- Blade-based Fediverse web UI (dashboard, timeline, inbox, discover, profile editing)
- Artisan commands: `activitypub:create-actor`, `activitypub:prune-activities`
- Queue jobs: async delivery with retries, remote actor fetching with deduplication
