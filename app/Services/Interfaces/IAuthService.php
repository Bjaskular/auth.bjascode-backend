<?php

namespace App\Services\Interfaces;

use Laravel\Sanctum\NewAccessToken;

interface IAuthService extends IService
{
    /** @return array{access_token: \Laravel\Sanctum\NewAccessToken, refresh_token: \Laravel\Sanctum\NewAccessToken} */
    public function login(array $requestArray): array;
    public function logout(): void;
    public function refreshAccessToken(): NewAccessToken;
}
