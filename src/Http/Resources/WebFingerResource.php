<?php

namespace DanielPetrica\LaravelActivityPub\Http\Resources;

use DanielPetrica\LaravelActivityPub\Models\Actor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Actor $resource
 */
final class WebFingerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parsedUrl = parse_url(url: $this->resource->actor_id);
        $domain = $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $domain .= ':'.$parsedUrl['port'];
        }

        return [
            'subject' => 'acct:'.$this->resource->username.'@'.$domain,
            'aliases' => [
                $this->resource->actor_id,
            ],
            'links' => [
                [
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => $this->resource->actor_id,
                ],
            ],
        ];
    }
}
