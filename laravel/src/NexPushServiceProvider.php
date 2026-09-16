<?php

namespace NexDeve\NexPush;

use Illuminate\Support\ServiceProvider;
use NexDeve\NexPush\Services\NexPushService;

class NexPushServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nexpush.php', 'nexpush');

        $this->app->singleton(NexPushService::class);
        $this->app->alias(NexPushService::class, 'nexpush');
    }

    public function boot(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../config/nexpush.php' => config_path('nexpush.php'),
        ], 'nexpush-config');

        // Migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'nexpush-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}
