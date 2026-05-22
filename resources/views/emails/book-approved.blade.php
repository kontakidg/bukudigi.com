<x-mail::message>
# Bukumu sudah LIVE di bukudigi.com 🎉

Halo **{{ $book->displayAuthor() }}**,

Selamat! Buku **"{{ $book->title }}"** sudah disetujui admin dan sekarang **bisa dibeli pembeli**.

<x-mail::panel>
**Judul:** {{ $book->title }}
**Harga:** Rp {{ number_format($book->price, 0, ',', '.') }}
**Kategori:** {{ $book->category->name ?? '—' }}
**Pen Name:** {{ $book->penName->name ?? $book->author->display_name }}
**URL Publik:** {{ url(route('books.show', $book->slug)) }}
</x-mail::panel>

<x-mail::button :url="url(route('books.show', $book->slug))" color="success">
Lihat di Toko
</x-mail::button>

**Tips supaya cepat laku:**

- Share link di atas ke audiens kamu (Instagram, Twitter, WhatsApp, blog)
- Cek dashboard rutin untuk track statistik penjualan
- Royalti akan masuk rekening setiap tanggal 5 (komisi platform 20% flat)

Pastikan data rekening bank sudah lengkap supaya payout royalti lancar:

<x-mail::button :url="url(route('author.bank.edit'))" color="primary">
Cek/Update Rekening
</x-mail::button>

Terima kasih sudah berkarya di bukudigi.com 🙏

**Tim bukudigi.com**
</x-mail::message>
