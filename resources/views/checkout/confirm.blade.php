@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-2xl px-4 py-10">
    <h1 class="text-2xl font-bold">Konfirmasi Pembelian</h1>
    <p class="mt-1 text-sm text-slate-500">Cek detail order sebelum bayar.</p>

    <div class="mt-6 card overflow-hidden">
        <div class="flex gap-4 p-4">
            <div class="h-32 w-24 flex-shrink-0 overflow-hidden rounded bg-slate-100">
                @if($order->book->cover_path)
                    <img src="{{ \Illuminate\Support\Str::startsWith($order->book->cover_path, ['http://', 'https://']) ? $order->book->cover_path : asset('storage/'.$order->book->cover_path) }}"
                         alt="{{ $order->book->title }}" class="h-full w-full object-cover">
                @endif
            </div>
            <div class="flex-1">
                <h2 class="font-bold">{{ $order->book->title }}</h2>
                <p class="mt-1 text-xs text-slate-500">oleh {{ $order->book->displayAuthor() }}</p>
                @if($order->voucher_discount > 0)
                    <p class="mt-3 text-sm text-slate-500"><s>Rp {{ number_format($order->gross_amount, 0, ',', '.') }}</s></p>
                    <p class="text-xl font-bold text-brand-600">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</p>
                @else
                    <p class="mt-3 text-xl font-bold text-brand-600">Rp {{ number_format($order->gross_amount, 0, ',', '.') }}</p>
                @endif
            </div>
        </div>

        @if($order->voucher_id)
            <div class="border-t border-slate-100 bg-green-50 px-4 py-3 text-xs text-green-800">
                <div class="flex items-center justify-between">
                    <span>🎟️ Voucher <strong>{{ $order->voucher->code }}</strong> — {{ $order->voucher->name }}</span>
                    <span class="font-bold">−Rp {{ number_format($order->voucher_discount, 0, ',', '.') }}</span>
                </div>
            </div>
        @endif

        <div class="border-t border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-600">
            Order: <span class="font-mono font-semibold">{{ $order->order_code }}</span>
        </div>
    </div>

    @if(($snap['mode'] ?? null) === 'stub')
        <div class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold">🛈 Mode Demo</p>
            <p class="mt-1">Midtrans belum dikonfigurasi. Klik tombol di bawah untuk simulasi pembayaran sukses.</p>
        </div>
        <div class="mt-6 flex gap-3">
            <a href="{{ $snap['redirect_url'] }}" class="btn-primary flex-1 !py-3">Simulasi Bayar (STUB)</a>
            <a href="{{ route('books.show', $order->book) }}" class="btn-outline !py-3">Batal</a>
        </div>
    @elseif(($snap['mode'] ?? null) === 'live')
        <div class="mt-6 rounded-lg bg-brand-50 p-4 text-sm text-slate-700">
            <p class="font-semibold">💳 Pilihan Pembayaran</p>
            <p class="mt-1">QRIS · Virtual Account (BCA/BNI/Mandiri/Permata/CIMB) · GoPay · OVO · DANA · ShopeePay · Kartu Kredit. Pilih metode di popup berikutnya.</p>
        </div>
        <div class="mt-6 flex gap-3">
            <button id="pay-button" class="btn-primary flex-1 !py-3">Bayar Sekarang</button>
            <a href="{{ route('books.show', $order->book) }}" class="btn-outline !py-3">Batal</a>
        </div>
        @push('scripts')
            <script src="{{ $snap['snap_js_url'] }}" data-client-key="{{ $snap['client_key'] }}"></script>
            <script>
                document.getElementById('pay-button')?.addEventListener('click', function () {
                    if (! window.snap) {
                        alert('Snap.js belum ke-load. Refresh halaman ini.');
                        return;
                    }
                    snap.pay(@json($snap['token']), {
                        onSuccess: function (result) {
                            window.location.href = '{{ route('library') }}?paid={{ $order->order_code }}';
                        },
                        onPending: function (result) {
                            window.location.href = '{{ route('library') }}?pending={{ $order->order_code }}';
                        },
                        onError: function (result) {
                            alert('Pembayaran gagal. Coba lagi atau pilih metode lain.');
                        },
                        onClose: function () {
                            // User close popup tanpa bayar — biarkan, status order tetap pending
                        }
                    });
                });
            </script>
        @endpush
    @else
        <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">⚠️ Gagal generate token pembayaran</p>
            <p class="mt-1">{{ $snap['message'] ?? 'Error tidak diketahui. Coba lagi atau hubungi support.' }}</p>
        </div>
        <div class="mt-6 flex gap-3">
            <a href="{{ route('books.show', $order->book) }}" class="btn-outline flex-1 !py-3">Kembali</a>
        </div>
    @endif

    <p class="mt-6 text-center text-xs text-slate-500">
        Dengan melanjutkan kamu setuju dengan <a href="{{ route('info.syarat') }}" class="text-brand-600 hover:underline">syarat layanan</a>.
    </p>
</section>
@endsection
