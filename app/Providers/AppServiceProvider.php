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
                baseUrl: config('services.beamdrop.url'),
                accessKey: config('services.beamdrop.access_key'),
                secretKey: config('services.beamdrop.secret_key'),
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
