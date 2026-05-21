@include('errors._layout', [
    'code' => 429,
    'emoji' => '🐢',
    'title' => 'Terlalu banyak request',
    'message' => 'Kamu melakukan request terlalu cepat. Tunggu beberapa detik lalu coba lagi.',
    'primaryLabel' => 'Coba lagi',
    'primaryUrl' => url()->current(),
])
