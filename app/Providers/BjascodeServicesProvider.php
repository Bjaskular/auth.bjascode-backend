<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BjascodeServicesProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->bind(\App\Services\Interfaces\IAuthService::class, \App\Services\AuthService::class);
    }
}
