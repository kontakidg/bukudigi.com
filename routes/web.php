<?php

use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\WhatsAppOtpController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\Author\BookController as AuthorBookController;
use App\Http\Controllers\Author\PenNameController as AuthorPenNameController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// === SEO ===
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// === Public ===
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/cari', [HomeController::class, 'search'])->name('cari');

Route::get('/buku', [BookController::class, 'index'])->name('books.index');
Route::get('/buku/{book:slug}', [BookController::class, 'show'])->name('books.show');

Route::get('/kategori', [CategoryController::class, 'index'])->name('kategori.index');
Route::get('/kategori/{category:slug}', [CategoryController::class, 'show'])->name('kategori.show');

// === Author landing (public) ===
Route::get('/jual', [AuthorController::class, 'landing'])->name('jual');

// === Affiliate landing (public) ===
Route::get('/affiliate', [AffiliateController::class, 'landing'])->name('affiliate.landing');

// === Affiliate short redirect (public): /r/{code} atau /r/{code}/{book} ===
Route::get('/r/{code}/{book?}', [AffiliateController::class, 'shortRedirect'])
    ->where('code', '[A-Za-z0-9_-]{3,32}')
    ->where('book', '[A-Za-z0-9_\-]+')
    ->name('affiliate.short');

// === Info pages ===
Route::view('/info/tentang', 'info.tentang')->name('info.tentang');
Route::view('/info/bantuan', 'info.bantuan')->name('info.bantuan');
Route::view('/info/syarat', 'info.syarat')->name('info.syarat');
Route::view('/info/privasi', 'info.privasi')->name('info.privasi');
Route::view('/info/komisi', 'info.komisi')->name('info.komisi');
Route::view('/info/panduan-penulis', 'info.panduan-penulis')->name('info.panduan-penulis');
Route::view('/info/faq', 'info.faq')->name('info.faq');

// === Social auth ===
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

// === Authenticated user ===
Route::middleware('auth')->group(function () {
    Route::get('/library', [LibraryController::class, 'index'])->name('library');
    Route::get('/dashboard', fn () => redirect()->route('library'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // WhatsApp OTP verification
    Route::get('/wa/verify', [WhatsAppOtpController::class, 'show'])->name('wa.verify.show');
    Route::post('/wa/verify/send', [WhatsAppOtpController::class, 'send'])->name('wa.verify.send');
    Route::post('/wa/verify', [WhatsAppOtpController::class, 'verify'])->name('wa.verify.confirm');

    // Author onboarding + dashboard
    Route::get('/author/register', [AuthorController::class, 'showRegister'])->name('author.register.show');
    Route::post('/author/register', [AuthorController::class, 'register'])->name('author.register');
    Route::get('/author/dashboard', [AuthorController::class, 'dashboard'])->name('author.dashboard');
    Route::get('/author/bank', [AuthorController::class, 'showBank'])->name('author.bank.edit');
    Route::patch('/author/bank', [AuthorController::class, 'updateBank'])->name('author.bank.update');

    // Author pen names CRUD
    Route::prefix('author/pen-names')->name('author.pen-names.')->group(function () {
        Route::get('/', [AuthorPenNameController::class, 'index'])->name('index');
        Route::get('/create', [AuthorPenNameController::class, 'create'])->name('create');
        Route::post('/', [AuthorPenNameController::class, 'store'])->name('store');
        Route::get('/{penName:slug}/edit', [AuthorPenNameController::class, 'edit'])->name('edit');
        Route::patch('/{penName:slug}', [AuthorPenNameController::class, 'update'])->name('update');
        Route::delete('/{penName:slug}', [AuthorPenNameController::class, 'destroy'])->name('destroy');
    });

    // Affiliate onboarding + dashboard
    Route::get('/affiliate/register', [AffiliateController::class, 'showRegister'])->name('affiliate.register.show');
    Route::post('/affiliate/register', [AffiliateController::class, 'register'])->name('affiliate.register');
    Route::get('/affiliate/dashboard', [AffiliateController::class, 'dashboard'])->name('affiliate.dashboard');
    Route::post('/affiliate/codes', [AffiliateController::class, 'storeCode'])->name('affiliate.codes.store');
    Route::delete('/affiliate/codes/{code}', [AffiliateController::class, 'destroyCode'])->name('affiliate.codes.destroy');
    Route::post('/affiliate/codes/{code}/default', [AffiliateController::class, 'setDefaultCode'])->name('affiliate.codes.default');

    // Author book CRUD
    Route::prefix('author/books')->name('author.books.')->group(function () {
        Route::get('/', [AuthorBookController::class, 'index'])->name('index');
        Route::get('/create', [AuthorBookController::class, 'create'])->name('create');
        Route::post('/', [AuthorBookController::class, 'store'])->name('store');
        Route::get('/{book:slug}/edit', [AuthorBookController::class, 'edit'])->name('edit');
        Route::patch('/{book:slug}', [AuthorBookController::class, 'update'])->name('update');
        Route::post('/{book:slug}/submit', [AuthorBookController::class, 'submit'])->name('submit');
        Route::delete('/{book:slug}', [AuthorBookController::class, 'archive'])->name('archive');
    });

    // Review buku
    Route::post('/buku/{book:slug}/review', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/review/{review}', [\App\Http\Controllers\ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/review/{review}/reply', [\App\Http\Controllers\ReviewController::class, 'reply'])->name('reviews.reply');

    // Checkout & Download
    Route::get('/checkout/buku/{book:slug}', [CheckoutController::class, 'start'])->name('checkout.start');
    Route::post('/checkout/voucher-preview/{book:slug}', [CheckoutController::class, 'previewVoucher'])->name('checkout.voucher.preview');
    Route::get('/checkout/stub/{orderCode}/pay', [CheckoutController::class, 'stubPay'])->name('checkout.stub.pay');
    Route::get('/download/{orderCode}', [DownloadController::class, 'show'])->name('download.show');
    Route::get('/download/{orderCode}/epub', [DownloadController::class, 'epub'])->name('download.epub');
    Route::get('/read/{orderCode}', [DownloadController::class, 'readEpub'])->name('download.epub.read');
    Route::get('/read/{orderCode}/stream', [DownloadController::class, 'streamEpub'])->name('download.epub.stream');
});

// Midtrans webhook (POST, no CSRF, no auth) — Midtrans hit this URL untuk notifikasi
Route::post('/api/midtrans/notify', [\App\Http\Controllers\MidtransWebhookController::class, 'handle'])
    ->name('midtrans.notify');

require __DIR__.'/auth.php';
