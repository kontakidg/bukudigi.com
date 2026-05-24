<?php

use App\Services\AffiliateService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pindahkan affiliate earnings pending → available kalau cooling 7 hari sudah lewat.
Artisan::command('affiliate:mature-earnings', function (AffiliateService $svc) {
    $n = $svc->maturePendingEarnings();
    $this->info("Matured {$n} earnings.");
})->purpose('Move affiliate earnings from pending to available after cooling period');

Schedule::command('affiliate:mature-earnings')->dailyAt('02:30');
