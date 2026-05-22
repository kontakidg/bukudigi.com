<x-mail::message>
# Buku belum bisa kami publikasikan ⚠️

Halo **{{ $book->displayAuthor() }}**,

Mohon maaf, buku **"{{ $book->title }}"** belum bisa kami publikasikan setelah review admin.

<x-mail::panel>
**Alasan penolakan:**

{{ $reason }}
</x-mail::panel>

Kabar baiknya: kamu bisa **edit & submit ulang** dari dashboard. Setelah perbaikan, admin akan review lagi (siklus 1-2 hari kerja).

<x-mail::button :url="url(route('author.books.edit', $book->slug))">
Edit Buku
</x-mail::button>

Beberapa hal yang umum bikin buku ditolak (untuk reference):

- File PDF ter-encrypt / tidak bisa dibuka
- Cover pakai gambar berlisensi tidak jelas
- Deskripsi terlalu pendek atau copy-paste
- Indikasi plagiat / bajakan
- Harga di luar range Rp 15.000-Rp 500.000

Pakai [Panduan Upload]({{ url('/info/panduan-penulis') }}) sebagai rujukan.

Punya pertanyaan? Balas email ini.

**Tim bukudigi.com**
</x-mail::message>
