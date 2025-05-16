<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BjascodeRepositoriesProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Repositories\Interfaces\IUserRepository::class, \App\Repositories\UserRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\IApplicationRepository::class, \App\Repositories\ApplicationRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
