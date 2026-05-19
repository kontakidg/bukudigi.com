<footer class="mt-16 bg-slate-900 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-4">
        <div class="md:col-span-1">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2" aria-label="bukudigi.com">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                </span>
                <span class="text-lg font-bold tracking-tight text-white">bukudigi<span class="text-brand-400">.com</span></span>
            </a>
            <p class="mt-3 text-sm text-slate-400">
                Marketplace ebook PDF dari penulis Indonesia. Beli, baca, dan dukung karya lokal — bayar pakai QRIS, instan download.
            </p>
        </div>
        <div>
            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-white">Jelajahi</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('books.index') }}" class="hover:text-brand-400">Semua Buku</a></li>
                <li><a href="{{ route('kategori.index') }}" class="hover:text-brand-400">Kategori</a></li>
                <li><a href="{{ route('books.index', ['sort' => 'popular']) }}" class="hover:text-brand-400">Buku Populer</a></li>
                <li><a href="{{ route('books.index', ['sort' => 'newest']) }}" class="hover:text-brand-400">Buku Terbaru</a></li>
            </ul>
        </div>
        <div>
            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-white">Untuk Penulis</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('jual') }}" class="hover:text-brand-400">Jadi Penulis</a></li>
                <li><a href="{{ route('info.komisi') }}" class="hover:text-brand-400">Komisi & Royalti</a></li>
                <li><a href="{{ route('info.panduan-penulis') }}" class="hover:text-brand-400">Panduan Upload</a></li>
                <li><a href="{{ route('info.faq') }}" class="hover:text-brand-400">FAQ</a></li>
            </ul>
        </div>
        <div>
            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-white">Tentang &amp; Legal</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('info.tentang') }}" class="hover:text-brand-400">Tentang Kami</a></li>
                <li><a href="{{ route('info.bantuan') }}" class="hover:text-brand-400">Pusat Bantuan</a></li>
                <li><a href="{{ route('info.syarat') }}" class="hover:text-brand-400">Syarat &amp; Ketentuan</a></li>
                <li><a href="{{ route('info.privasi') }}" class="hover:text-brand-400">Kebijakan Privasi</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-800">
        <div class="mx-auto max-w-7xl px-4 py-4 text-center text-xs text-slate-500">
            © {{ date('Y') }} bukudigi.com. Untuk komunitas pembaca &amp; penulis Indonesia 📚
        </div>
    </div>
</footer>
