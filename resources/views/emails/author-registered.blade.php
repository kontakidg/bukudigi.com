<x-mail::message>
# Pendaftaran penulis diterima ⏳

Halo **{{ $author->display_name }}**,

Pendaftaran kamu sebagai penulis di bukudigi.com sudah masuk dan **sedang di-review admin**.

Estimasi waktu verifikasi: **1-2 hari kerja**. Kami akan kirim email lagi (dan WhatsApp kalau nomor sudah terverifikasi) saat akun penulis kamu sudah aktif.

Sambil menunggu, kamu bisa:

- Lengkapi profil & pen name di dashboard
- Siapkan file PDF + cover buku yang akan kamu jual
- Baca [Panduan Upload]({{ url('/info/panduan-penulis') }})

<x-mail::button :url="url(route('author.dashboard'))">
Buka Dashboard Penulis
</x-mail::button>

Terima kasih sudah memilih bukudigi.com sebagai tempat jualan karyamu 🙏

**Tim bukudigi.com**
</x-mail::message>
