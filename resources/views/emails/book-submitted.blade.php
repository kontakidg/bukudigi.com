<x-mail::message>
# Buku kamu sudah disubmit 📖

Halo **{{ $book->displayAuthor() }}**,

Buku **"{{ $book->title }}"** sudah masuk antrian moderasi admin.

<x-mail::panel>
**Judul:** {{ $book->title }}
**Harga:** Rp {{ number_format($book->price, 0, ',', '.') }}
**Kategori:** {{ $book->category->name ?? '—' }}
**Submit:** {{ ($book->submitted_at ?? now())->format('d M Y, H:i') }} WIB
</x-mail::panel>

Estimasi review: **1-2 hari kerja**. Kami akan kabari via email + WhatsApp begitu buku approved atau ditolak.

Cek status anytime di dashboard:

<x-mail::button :url="url(route('author.books.index'))">
Lihat Buku Saya
</x-mail::button>

Terima kasih sudah jualan di bukudigi.com 🙏

**Tim bukudigi.com**
</x-mail::message>
