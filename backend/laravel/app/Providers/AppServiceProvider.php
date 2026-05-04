<?php

namespace App\Providers;

use App\Contracts\ShippingProviderInterface;
use App\Services\EasyPostService;
use EasyPost\EasyPostClient;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EasyPostClient::class, function () {
            $apiKey = config('easypost.api_key');

            if (empty($apiKey)) {
                throw new RuntimeException(
                    'EasyPost API key is not set. Add EASYPOST_API_KEY to docker-compose.yml.'
                );
            }

            return new EasyPostClient($apiKey);
        });

        // Swap this binding to change providers without touching business logic
        $this->app->bind(ShippingProviderInterface::class, EasyPostService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
