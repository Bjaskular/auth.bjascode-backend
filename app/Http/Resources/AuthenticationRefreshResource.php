<?php

namespace App\Http\Resources;

use App\Enums\AuthCookieName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticationRefreshResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => AuthCookieName::API_ACCESS->value,
            'value' => $this->resource->plainTextToken,
            'ttl' => config('sanctum.access_expiration') * 60,
            'expired_at' => now()->addMinutes(config('sanctum.access_expiration'))->unix(),
        ];
    }
}
