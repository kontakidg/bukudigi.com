@include('errors._layout', [
    'code' => 403,
    'emoji' => '🔒',
    'title' => 'Akses ditolak',
    'message' => 'Kamu tidak punya izin untuk membuka halaman ini. Coba <a href="' . route('login') . '" class="text-brand-600 underline">login dengan akun yang sesuai</a>, atau hubungi support kalau menurut kamu ini salah.',
    'showBack' => true,
])
