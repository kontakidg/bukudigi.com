<x-mail::message>
# Akun penulis kamu sudah aktif 🎉

Halo **{{ $author->display_name }}**,

Verifikasi pendaftaran penulis kamu **disetujui**. Sekarang kamu bisa upload buku dan mulai jualan!

**Langkah selanjutnya:**

1. Buat **Pen Name** (boleh lebih dari satu — mis. nama berbeda untuk fiksi vs non-fiksi)
2. Upload buku pertama: PDF + cover + deskripsi
3. Tunggu moderasi admin (1-2 hari kerja per buku)
4. Buku live → siap dibeli pembeli
5. Royalti masuk rekening setiap tanggal 5 (penulis 70%, affiliate 10%, platform 20%)

<x-mail::button :url="url(route('author.books.create'))">
+ Upload Buku Pertama
</x-mail::button>

Sebelum publish, baca [Panduan Upload]({{ url('/info/panduan-penulis') }}) untuk tips harga, spec PDF, dan cara nulis deskripsi yang menjual.

Selamat berkarya 🚀

**Tim bukudigi.com**
</x-mail::message>
