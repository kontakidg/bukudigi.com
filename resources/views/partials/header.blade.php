<header x-data="{ open: false, userMenu: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
        <a href="{{ route('home') }}" class="flex items-center" aria-label="BukuDigi.com">
            <img src="{{ asset('logo.png') }}" alt="BukuDigi.com"
                 width="1200" height="279"
                 class="h-auto w-32 md:w-40">
        </a>

        <form action="{{ route('cari') }}" method="get" class="hidden flex-1 max-w-xl md:flex">
            <div class="flex w-full overflow-hidden rounded-lg border border-slate-300 bg-white">
                <input name="q" type="search" placeholder="Cari judul buku, penulis, kategori…"
                       value="{{ request('q') }}"
                       class="flex-1 px-3 py-2 text-sm focus:outline-none">
                <button class="bg-brand-600 px-4 text-white hover:bg-brand-700">Cari</button>
            </div>
        </form>

        <nav class="hidden items-center gap-5 text-sm font-medium md:flex">
            <a href="{{ route('books.index') }}" class="hover:text-brand-600">Semua Buku</a>
            <a href="{{ route('kategori.index') }}" class="hover:text-brand-600">Kategori</a>
            <a href="{{ route('jual') }}" class="hover:text-brand-600">Jadi Penulis</a>
            <a href="{{ route('affiliate.landing') }}" class="hover:text-brand-600">Affiliate</a>
        </nav>

        <div class="hidden items-center gap-2 md:flex">
            @auth
                {{-- User dropdown --}}
                <div class="relative inline-block" @click.away="userMenu = false">
                    <button @click="userMenu = !userMenu"
                            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm hover:border-brand-500"
                            :aria-expanded="userMenu">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden font-medium text-slate-700 lg:inline">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 18) }}</span>
                        <svg class="h-4 w-4 text-slate-400 transition" :class="userMenu && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="userMenu" x-cloak x-transition
                         class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white shadow-lg ring-1 ring-black/5">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                            @if(auth()->user()->isAdmin())
                                <span class="mt-1 inline-block rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">ADMIN</span>
                            @elseif(auth()->user()->isAuthor())
                                <span class="mt-1 inline-block rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">PENULIS</span>
                            @endif
                        </div>

                        <div class="py-1">
                            @if(auth()->user()->isAdmin())
                                <a href="/admin" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">⚙ Admin Panel</a>
                            @endif
                            @if(auth()->user()->isAuthor())
                                <a href="{{ route('author.dashboard') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">📊 Dashboard Penulis</a>
                                <a href="{{ route('author.books.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">📕 Buku Saya</a>
                                <a href="{{ route('author.pen-names.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">🖋️ Pen Name</a>
                                <a href="{{ route('author.bank.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">💳 Rekening Bank</a>
                            @endif
                            <a href="{{ route('library') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">📚 Perpustakaan</a>
                            @if(auth()->user()->affiliate)
                                <a href="{{ route('affiliate.dashboard') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">🔗 Dashboard Affiliate</a>
                            @else
                                <a href="{{ route('affiliate.landing') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">🔗 Program Affiliate</a>
                            @endif
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">⚙ Profil &amp; Password</a>
                        </div>

                        <div class="border-t border-slate-100 py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                    ↪ Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-brand-600">Masuk</a>
                <a href="{{ route('register') }}" class="btn-primary !py-1.5 !text-sm">Daftar Gratis</a>
            @endauth
        </div>

        <button @click="open = !open" class="md:hidden" aria-label="Menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <div x-show="open" x-cloak class="border-t border-slate-200 bg-white px-4 py-3 md:hidden">
        <form action="{{ route('cari') }}" method="get" class="mb-3 flex">
            <div class="flex w-full overflow-hidden rounded-lg border border-slate-300 bg-white">
                <input name="q" type="search" placeholder="Cari…" value="{{ request('q') }}"
                       class="flex-1 px-3 py-2 text-sm focus:outline-none">
                <button class="bg-brand-600 px-4 text-white">Cari</button>
            </div>
        </form>
        <nav class="grid grid-cols-2 gap-2 text-sm font-medium">
            <a href="{{ route('books.index') }}" class="rounded p-2 hover:bg-slate-100">📖 Semua Buku</a>
            <a href="{{ route('kategori.index') }}" class="rounded p-2 hover:bg-slate-100">🗂️ Kategori</a>
            <a href="{{ route('jual') }}" class="rounded p-2 hover:bg-slate-100">✍️ Jadi Penulis</a>
            <a href="{{ route('affiliate.landing') }}" class="rounded p-2 hover:bg-slate-100">🔗 Affiliate</a>
            <a href="{{ route('info.bantuan') }}" class="rounded p-2 hover:bg-slate-100">❓ Bantuan</a>
        </nav>

        @auth
            <div class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-sm">
                <p class="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ auth()->user()->name }}</p>
                @if(auth()->user()->isAdmin())
                    <a href="/admin" class="block rounded p-2 hover:bg-slate-100">⚙ Admin Panel</a>
                @endif
                @if(auth()->user()->isAuthor())
                    <a href="{{ route('author.dashboard') }}" class="block rounded p-2 hover:bg-slate-100">📊 Dashboard Penulis</a>
                    <a href="{{ route('author.books.index') }}" class="block rounded p-2 hover:bg-slate-100">📕 Buku Saya</a>
                    <a href="{{ route('author.pen-names.index') }}" class="block rounded p-2 hover:bg-slate-100">🖋️ Pen Name</a>
                @endif
                <a href="{{ route('library') }}" class="block rounded p-2 hover:bg-slate-100">📚 Perpustakaan</a>
                <a href="{{ route('profile.edit') }}" class="block rounded p-2 hover:bg-slate-100">⚙ Profil &amp; Password</a>
                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button class="block w-full rounded p-2 text-left text-red-600 hover:bg-red-50">↪ Keluar</button>
                </form>
            </div>
        @else
            <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                <a href="{{ route('login') }}" class="btn-outline flex-1 !py-2 !text-sm">Masuk</a>
                <a href="{{ route('register') }}" class="btn-primary flex-1 !py-2 !text-sm">Daftar Gratis</a>
            </div>
        @endauth
    </div>
</header>
