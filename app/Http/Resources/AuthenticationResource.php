<?php

namespace App\Http\Resources;

use App\Enums\AuthCookieName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => [
                'name' => AuthCookieName::API_ACCESS->value,
                'value' => $this->resource['access_token']->plainTextToken,
                'ttl' => config('sanctum.access_expiration') * 60,
                'expired_at' => now()->addMinutes(config('sanctum.access_expiration'))->unix(),
            ],
            'refresh_token' => [
                'name' => AuthCookieName::REFRESH->value,
                'value' => $this->resource['refresh_token']->plainTextToken,
                'ttl' => config('sanctum.refresh_expiration') * 60,
                'expired_at' => now()->addMinutes(config('sanctum.refresh_expiration'))->unix(),
            ],
            'redirect_url' => $this->resource['redirect_url']
        ];
    }
}
