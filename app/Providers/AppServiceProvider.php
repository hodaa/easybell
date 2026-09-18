<?php

namespace App\Providers;

use App\Services\Checkout;
use App\Services\Pricing\PriceRuleFactory;
use App\Services\Pricing\PriceRuleRegistry;
use App\Services\Pricing\PricingConfiguration;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Checkout::class, fn () => new Checkout(
            (new PricingConfiguration(new PriceRuleFactory(new PriceRuleRegistry)))->resolve(config('checkout.rules')),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
