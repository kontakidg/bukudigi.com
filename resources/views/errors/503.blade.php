@include('errors._layout', [
    'code' => 503,
    'emoji' => '🔧',
    'title' => 'Sedang maintenance',
    'message' => 'Kami sedang update sistem untuk peningkatan layanan. Akan segera kembali — biasanya 5-15 menit.<br><br>Pembelian yang sudah sukses tidak terdampak. Cek email kamu untuk link download.',
    'primaryLabel' => 'Refresh halaman',
    'primaryUrl' => url()->current(),
    'showBack' => false,
])
