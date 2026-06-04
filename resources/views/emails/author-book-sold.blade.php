<x-mail::message>
# Buku Kamu Terjual! 🎉

Halo **{{ $order->book->author->display_name }}**,

Ada pembeli baru untuk bukumu di **bukudigi.com**. Berikut rincian lengkapnya:

---

## Detail Penjualan

<x-mail::panel>
**Kode Order:** `{{ $order->order_code }}`
**Buku:** {{ $order->book->title }}
**Waktu Beli:** {{ ($order->paid_at ?? now())->format('d M Y, H:i') }} WIB
</x-mail::panel>

## Rincian Pendapatan

<x-mail::panel>
| Keterangan | Jumlah |
|:-----------|-------:|
| Harga jual | Rp {{ number_format($order->gross_amount, 0, ',', '.') }} |
@if($order->voucher_discount > 0)
| Diskon voucher | − Rp {{ number_format($order->voucher_discount, 0, ',', '.') }} |
| Harga bersih | Rp {{ number_format($order->net_amount, 0, ',', '.') }} |
@endif
@if($order->affiliate_commission > 0)
| Komisi affiliate | − Rp {{ number_format($order->affiliate_commission, 0, ',', '.') }} |
@endif
| Komisi platform ({{ config('services.platform_commission_percent', 20) }}%) | − Rp {{ number_format(($order->commission ?? 0) - ($order->affiliate_commission ?? 0), 0, ',', '.') }} |
| **Royalti kamu** | **Rp {{ number_format($order->author_earning, 0, ',', '.') }}** |
</x-mail::panel>

**Status royalti:** Masuk ke **Saldo Menahan** selama 7 hari masa cooling, lalu otomatis pindah ke **Saldo Tersedia** dan dibayarkan setiap tanggal 5.

<x-mail::button :url="url(route('author.dashboard'))" color="success">
Lihat Dashboard
</x-mail::button>

---

Terus semangat menulis — setiap buku yang terjual adalah dampak nyata! ✍️

**Tim bukudigi.com**
*[bukudigi.com]({{ url('/') }}) — Marketplace Ebook Indonesia*
</x-mail::message>
