@include('errors._layout', [
    'code' => 419,
    'emoji' => '⏱️',
    'title' => 'Sesi kamu sudah kadaluarsa',
    'message' => 'Karena kelamaan idle, form yang kamu submit tidak bisa diproses lagi. Refresh halaman dan submit ulang.',
    'primaryLabel' => 'Refresh halaman',
    'primaryUrl' => url()->current(),
    'showBack' => true,
])
