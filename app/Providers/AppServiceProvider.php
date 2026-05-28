<?php

namespace App\Providers;

use App\Services\MidtransService;
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
        // Mode promo Early Access aktif selama Midtrans masih stub (belum live).
        // Saat stub: semua buku "gratis klaim", harga dicoret + badge promo.
        View::share('isPromoMode', app(MidtransService::class)->isStub());
    }
}
