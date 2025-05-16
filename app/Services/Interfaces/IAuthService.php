<?php

namespace App\Services\Interfaces;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

interface IAuthService extends IService
{
    /** @return array{access_token: \Laravel\Sanctum\NewAccessToken, refresh_token: \Laravel\Sanctum\NewAccessToken, redirect_url: string|null} */
    public function login(array $requestArray): array;
    public function logout(): void;
    public function authorize(array $requestArray): User;
    public function refreshAccessToken(): NewAccessToken;
}
