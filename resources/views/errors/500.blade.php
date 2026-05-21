@include('errors._layout', [
    'code' => 500,
    'emoji' => '🛠️',
    'title' => 'Ada masalah di server',
    'message' => 'Server kami sedang error. Tim teknis sudah dapat notifikasi. Coba lagi beberapa menit lagi.<br><br>Kalau urgent (mis. baru bayar tapi PDF belum muncul), hubungi <a href="mailto:support@bukudigi.com" class="text-brand-600 underline">support@bukudigi.com</a> dengan kode order kamu.',
    'showBack' => true,
])
