<x-mail::message>
# Bukumu terjual! 🎉

Halo **{{ $order->book->author->display_name }}**,

Ada pembeli baru untuk bukumu di bukudigi.com.

<x-mail::panel>
**Buku:** {{ $order->book->title }}
**Order:** {{ $order->order_code }}
**Harga:** Rp {{ number_format($order->gross_amount, 0, ',', '.') }}
**Royalti kamu:** Rp {{ number_format($order->author_earning, 0, ',', '.') }}
**Waktu:** {{ ($order->paid_at ?? now())->format('d M Y, H:i') }} WIB
</x-mail::panel>

Royalti akan masuk ke **Saldo Menahan** selama 7 hari masa cooling, lalu otomatis pindah ke **Saldo Tersedia** dan dibayarkan setiap tanggal 5.

<x-mail::button :url="url(route('author.dashboard'))" color="success">
Lihat Dashboard
</x-mail::button>

Terus semangat menulis! 📖

**Tim bukudigi.com**
</x-mail::message>
