<?php

namespace App\Services\Interfaces;

interface IAuthService extends IService
{
    /** @return array{access_token: \Laravel\Sanctum\NewAccessToken, refresh_token: \Laravel\Sanctum\NewAccessToken} */
    public function login(array $requestArray): array;
    public function logout(): void;
}
