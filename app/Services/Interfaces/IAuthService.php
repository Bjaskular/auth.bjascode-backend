<?php

namespace App\Services\Interfaces;

use Laravel\Sanctum\NewAccessToken;

interface IAuthService extends IService
{
    public function login(array $requestArray): NewAccessToken;
}
