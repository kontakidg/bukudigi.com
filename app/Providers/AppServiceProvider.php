<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
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
        // Promo Early Access aktif kalau payment gateway = stub (belum ada gateway live).
        // PAYMENT_GATEWAY=paypal atau midtrans → promo OFF, harga normal.
        View::share('isPromoMode', config('services.payment_gateway', 'stub') === 'stub');
    }
}
