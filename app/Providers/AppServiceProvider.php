<?php

namespace App\Providers;

use App\Jobs\RenameHostJob;
use App\Services\LogService;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $logChannel = [
            [RenameHostJob::class, 'operations']
        ];

        foreach ($logChannel as [$class, $channel]) {
            $this->app->when(RenameHostJob::class)
                ->needs(LogService::class)
                ->give(function ($app) use ($channel) {
                    return new LogService(
                        $app->make(LogManager::class),
                        $channel
                    );
                });
        }

    }
}
