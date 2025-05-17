<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AccessesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'email' => $this->resource->email,
            'user_name' => $this->resource->userName,
            'applications' => $this->resource->applications->select(['key', 'name', 'url']),
        ];
    }
}
