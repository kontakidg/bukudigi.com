<x-mail::message>
# Pembayaran sukses — bukumu sudah siap diunduh 📦

Halo,

Terima kasih sudah membeli ebook di bukudigi.com. Pembayaran berhasil!

<x-mail::panel>
**Order:** {{ $order->order_code }}
**Buku:** {{ $order->book->title }}
**Penulis:** {{ $order->book->displayAuthor() }}
**Total:** Rp {{ number_format($order->gross_amount, 0, ',', '.') }}
**Dibayar:** {{ ($order->paid_at ?? now())->format('d M Y, H:i') }} WIB
</x-mail::panel>

PDF kamu akan otomatis ber-watermark personal (nama + email kamu + kode order di setiap halaman) saat di-download. Watermark ini bukti kepemilikan + anti-bocoran.

<x-mail::button :url="url(route('library'))" color="success">
Download di Perpustakaan
</x-mail::button>

**Catatan:**

- Link download berlaku 30 hari sejak pembelian
- Maksimal 5x re-download untuk buku yang sama
- Setelah download, file disimpan permanen di perangkat kamu — bisa dibaca offline
- Print untuk konsumsi pribadi DIPERBOLEHKAN. Tapi **jangan bagikan** PDF ke orang lain — bocoran terlacak ke akun kamu.

Ada masalah teknis (file rusak, halaman kosong, dll)? Balas email ini dalam 7 hari untuk minta refund.

Selamat membaca 📖

**Tim bukudigi.com**
</x-mail::message>
