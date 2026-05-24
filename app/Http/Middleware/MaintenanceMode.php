<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft maintenance mode — bisa di-toggle dari Site Settings tanpa restart server.
 *
 * Bypass rules:
 *   1. Admin yang sudah login → tetap bisa akses semua
 *   2. Path /admin/* → boleh akses (biar admin bisa toggle off)
 *   3. Path login/logout → boleh (biar admin bisa login dulu)
 *   4. Path /up (health check) → boleh
 *   5. Path /api/midtrans/notify → boleh (jangan blokir webhook)
 */
class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::get('maintenance_enabled')) {
            return $next($request);
        }

        // Whitelist paths
        $allowedPaths = ['admin', 'admin/*', 'login', 'logout', 'register', 'forgot-password', 'reset-password', 'up', 'api/midtrans/notify', 'auth/google', 'auth/google/callback'];
        foreach ($allowedPaths as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Admin user yang sudah login boleh akses semua
        $user = $request->user();
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        // Render maintenance page
        return response()->view('errors.maintenance', [
            'title' => Setting::get('maintenance_title', 'Lagi rapi-rapi rak buku 📚'),
            'message' => Setting::get('maintenance_message', 'Halo! bukudigi.com sedang upgrade biar makin nyaman dibaca. Tenang, semua buku kamu di Perpustakaan aman tersimpan. Sebentar ya, kami segera kembali!'),
            'estimatedEnd' => Setting::get('maintenance_estimated_end'),
            'showContact' => (bool) Setting::get('maintenance_show_contact', true),
        ], 503);
    }
}
