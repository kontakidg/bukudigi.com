<x-mail::message>
# Selamat datang di bukudigi.com, {{ $user->name }}! 📚

Akun kamu sudah aktif. Sekarang kamu bisa:

- 🛒 Beli ebook PDF dari penulis Indonesia favoritmu
- 📖 Akses preview gratis 5 halaman sebelum beli
- 💾 Download permanen dengan watermark personal
- ✍️ (Opsional) Jadi penulis dan jual karyamu sendiri

<x-mail::button :url="url('/buku')">
Jelajahi Katalog Ebook
</x-mail::button>

Atau mulai jadi penulis:

<x-mail::button :url="url('/jual')" color="success">
Daftar Penulis
</x-mail::button>

Punya pertanyaan? Balas email ini atau buka [Pusat Bantuan]({{ url('/info/bantuan') }}).

Terima kasih sudah bergabung 🎉
**Tim bukudigi.com**
</x-mail::message>
