<?php

namespace DanielPetrica\LaravelActivityPub\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $actor_url
 * @property string $inbox_url
 * @property string|null $public_key_pem
 * @property string $username
 * @property string $domain
 * @property string|null $name
 * @property string|null $icon_url
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Collection<int, Follower> $followers
 */
final class RemoteActor extends Model
{
    protected $table = 'remote_actors';

    protected $fillable = [
        'actor_url',
        'inbox_url',
        'public_key_pem',
        'username',
        'domain',
        'name',
        'icon_url',
    ];

    protected $casts = [
        'public_key_pem' => 'encrypted',
    ];

    public function followers(): HasMany
    {
        return $this->hasMany(related: Follower::class);
    }
}
