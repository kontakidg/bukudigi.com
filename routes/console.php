<?php

use App\Services\AffiliateService;
use App\Services\AuthorService;
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

// Pindahkan author earnings pending → available kalau cooling 7 hari sudah lewat.
Artisan::command('author:mature-earnings', function (AuthorService $svc) {
    $n = $svc->maturePendingEarnings();
    $this->info("Matured {$n} author earnings.");
})->purpose('Move author earnings from pending to available after cooling period');

Schedule::command('author:mature-earnings')->dailyAt('02:35');
