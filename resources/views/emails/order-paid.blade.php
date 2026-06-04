<x-mail::message>
# Pembayaran Berhasil 🎉

Halo **{{ $order->user->name }}**,

Terima kasih sudah berbelanja di **bukudigi.com**. Pembayaranmu sudah kami terima dan bukumu siap diunduh!

---

## Detail Transaksi

<x-mail::panel>
**Kode Order:** `{{ $order->order_code }}`
**Buku:** {{ $order->book->title }}
**Penulis:** {{ $order->book->displayAuthor() }}
**Waktu Bayar:** {{ ($order->paid_at ?? now())->format('d M Y, H:i') }} WIB
</x-mail::panel>

## Rincian Pembayaran

<x-mail::panel>
@if($order->voucher_discount > 0)
| Keterangan | Jumlah |
|:-----------|-------:|
| Harga buku | Rp {{ number_format($order->gross_amount, 0, ',', '.') }} |
| Diskon voucher **{{ $order->voucher?->code }}** | − Rp {{ number_format($order->voucher_discount, 0, ',', '.') }} |
| **Total dibayar** | **Rp {{ number_format($order->net_amount, 0, ',', '.') }}** |
@else
| Keterangan | Jumlah |
|:-----------|-------:|
| Harga buku | Rp {{ number_format($order->gross_amount, 0, ',', '.') }} |
| **Total dibayar** | **Rp {{ number_format($order->gross_amount, 0, ',', '.') }}** |
@endif

@php
    $gateway = $order->payment_gateway ?? $order->payment_method ?? null;
    $methodLabel = match(true) {
        $gateway === 'paypal' => 'PayPal (USD $'.number_format($order->paypal_amount_usd ?? 0, 2).')',
        str_contains((string)$gateway, 'qris') => 'QRIS',
        str_contains((string)$gateway, 'gopay') => 'GoPay',
        str_contains((string)$gateway, 'ovo') => 'OVO',
        str_contains((string)$gateway, 'dana') => 'DANA',
        str_contains((string)$gateway, 'shopeepay') => 'ShopeePay',
        str_contains((string)($order->payment_method ?? ''), 'bank_transfer') => 'Transfer Bank',
        str_contains((string)($order->payment_method ?? ''), 'credit_card') => 'Kartu Kredit',
        default => ucfirst($gateway ?? 'Online Payment'),
    };
@endphp
**Metode Bayar:** {{ $methodLabel }}
</x-mail::panel>

## Cara Download

Buku sudah tersedia di **Perpustakaanmu**. Klik tombol di bawah untuk langsung download:

<x-mail::button :url="url(route('library'))" color="success">
📚 Download di Perpustakaan
</x-mail::button>

**Yang perlu kamu tahu:**

- File PDF akan ber-**watermark personal** (nama + email + kode order di setiap halaman) sebagai bukti kepemilikan
- **Batas download:** 5x dalam 30 hari sejak pembelian
- Setelah diunduh, file tersimpan permanen di perangkatmu — bisa dibaca offline kapan saja
- Mencetak untuk konsumsi pribadi **diperbolehkan**
- Jangan bagikan file ke orang lain — watermark terlacak ke akunmu

---

**Ada masalah?**

Jika file rusak, halaman kosong, atau ada kendala teknis lainnya, balas email ini dalam **7 hari** dan kami akan bantu atau proses refund.

Selamat membaca! 📖

**Tim bukudigi.com**
*[bukudigi.com]({{ url('/') }}) — Marketplace Ebook Indonesia*
</x-mail::message>
