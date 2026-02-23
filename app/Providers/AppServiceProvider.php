<?php

namespace App\Providers;

use App\Services\Beamdrop;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Beamdrop::class, function ($app) {
            return new Beamdrop(
                baseUrl: (string) config('services.beamdrop.url', 'http://localhost:8090'),
                accessKey: (string) config('services.beamdrop.access_key', ''),
                secretKey: (string) config('services.beamdrop.secret_key', ''),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
