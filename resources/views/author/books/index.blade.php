<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">📕 Buku Saya</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola katalog ebook yang kamu jual.</p>
            </div>
            <a href="{{ route('author.books.create') }}" class="btn-primary !py-2 !text-sm">+ Upload Buku Baru</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-2.5 text-sm text-green-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-4 py-2.5 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        {{-- Search / Sort / Filter bar --}}
        <form method="GET" action="{{ route('author.books.index') }}" id="filter-form" class="flex flex-wrap gap-2">
            {{-- Search --}}
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/></svg>
                </span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari judul buku…"
                       class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100">
            </div>

            {{-- Filter status --}}
            <select name="status" onchange="document.getElementById('filter-form').submit()"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100">
                <option value=""         @selected($status==='')             >Semua Status</option>
                <option value="active"        @selected($status==='active')        >Live</option>
                <option value="pending_review"@selected($status==='pending_review')>Review</option>
                <option value="draft"         @selected($status==='draft')         >Draft</option>
                <option value="rejected"      @selected($status==='rejected')      >Ditolak</option>
                <option value="archived"      @selected($status==='archived')      >Archived</option>
            </select>

            {{-- Sort --}}
            <select name="sort" onchange="document.getElementById('filter-form').submit()"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100">
                <option value="latest"  @selected($sort==='latest') >Terbaru</option>
                <option value="title"   @selected($sort==='title')  >Judul A–Z</option>
                <option value="terjual"    @selected($sort==='terjual')   >Terjual ↓</option>
                <option value="pendapatan" @selected($sort==='pendapatan')>Pendapatan ↓</option>
                <option value="price"      @selected($sort==='price')     >Harga ↓</option>
                <option value="status"     @selected($sort==='status')    >Status</option>
            </select>

            <button type="submit" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cari</button>

            @if($search || $sort !== 'latest' || $status !== '')
                <a href="{{ route('author.books.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-500 hover:bg-slate-50">Reset</a>
            @endif
        </form>

        @if($books->isEmpty() && !$search && !$status)
            {{-- Belum ada buku sama sekali --}}
            <div class="mt-6 rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
                <p class="text-4xl">📕</p>
                <p class="mt-2 font-semibold">Belum ada buku.</p>
                <p class="mt-1 text-sm text-slate-500">Upload buku pertama kamu sekarang.</p>
                <a href="{{ route('author.books.create') }}" class="btn-primary mt-6 inline-block">+ Upload Buku Baru</a>
            </div>
        @elseif($books->isEmpty())
            {{-- Hasil filter kosong --}}
            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                Tidak ada buku yang cocok dengan filter ini.
                <a href="{{ route('author.books.index') }}" class="ml-1 text-brand-600 hover:underline">Reset filter</a>
            </div>
        @else
            {{-- Bulk action form --}}
            <form method="POST" action="{{ route('author.books.bulk') }}" id="bulk-form" class="mt-4">
                @csrf

                {{-- Sticky bulk toolbar — muncul saat ada yang dicentang --}}
                <div id="bulk-bar"
                     class="hidden sticky top-0 z-20 mb-3 flex flex-wrap items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 shadow-sm">
                    <span class="text-sm font-medium text-brand-800">
                        <span id="bulk-count">0</span> buku dipilih
                    </span>
                    <div class="ml-auto flex flex-wrap gap-2">
                        <button type="submit" name="action" value="submit"
                                class="rounded-lg bg-amber-500 px-4 py-1.5 text-xs font-semibold text-white hover:bg-amber-600"
                                onclick="return confirmBulk('submit')">
                            ↑ Submit Ulang ke Moderasi
                        </button>
                        <button type="submit" name="action" value="archive"
                                class="rounded-lg bg-slate-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-slate-700"
                                onclick="return confirmBulk('archive')">
                            Archive
                        </button>
                        <button type="button" onclick="clearSelection()"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-1.5 text-xs text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                {{-- Select All --}}
                                <th class="w-10 px-4 py-3">
                                    <input type="checkbox" id="select-all"
                                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer"
                                           title="Pilih semua">
                                </th>
                                <th class="px-4 py-3">
                                    <a href="{{ route('author.books.index', array_merge(request()->except(['sort','page']), ['sort'=>'title'])) }}"
                                       class="hover:text-slate-800 {{ $sort==='title' ? 'text-brand-600 font-bold' : '' }}">
                                        Buku @if($sort==='title')<span class="ml-0.5">↑</span>@endif
                                    </a>
                                </th>
                                <th class="px-4 py-3">
                                    <a href="{{ route('author.books.index', array_merge(request()->except(['sort','page']), ['sort'=>'status'])) }}"
                                       class="hover:text-slate-800 {{ $sort==='status' ? 'text-brand-600 font-bold' : '' }}">
                                        Status @if($sort==='status')<span class="ml-0.5">↑</span>@endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <a href="{{ route('author.books.index', array_merge(request()->except(['sort','page']), ['sort'=>'price'])) }}"
                                       class="hover:text-slate-800 {{ $sort==='price' ? 'text-brand-600 font-bold' : '' }}">
                                        Harga @if($sort==='price')<span class="ml-0.5">↓</span>@endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <a href="{{ route('author.books.index', array_merge(request()->except(['sort','page']), ['sort'=>'terjual'])) }}"
                                       class="hover:text-slate-800 {{ $sort==='terjual' ? 'text-brand-600 font-bold' : '' }}">
                                        Terjual @if($sort==='terjual')<span class="ml-0.5">↓</span>@endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <a href="{{ route('author.books.index', array_merge(request()->except(['sort','page']), ['sort'=>'pendapatan'])) }}"
                                       class="hover:text-slate-800 {{ $sort==='pendapatan' ? 'text-brand-600 font-bold' : '' }}">
                                        Pendapatan @if($sort==='pendapatan')<span class="ml-0.5">↓</span>@endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($books as $book)
                                <tr class="book-row border-t border-slate-100 hover:bg-slate-50 transition-colors"
                                    data-status="{{ $book->status }}">
                                    {{-- Checkbox --}}
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="ids[]" value="{{ $book->id }}"
                                               class="book-checkbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                    </td>

                                    {{-- Buku --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-14 w-10 flex-shrink-0 overflow-hidden rounded bg-slate-100">
                                                @if($book->cover_path)
                                                    <img src="{{ \Illuminate\Support\Str::startsWith($book->cover_path, ['http://','https://']) ? $book->cover_path : asset('storage/'.$book->cover_path) }}"
                                                         alt="" class="h-full w-full object-cover">
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-900 leading-snug">{{ $book->title }}</p>
                                                <p class="mt-0.5 text-xs text-slate-400">{{ $book->category->name ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-3">
                                        @switch($book->status)
                                            @case('active')
                                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">✓ Live</span>@break
                                            @case('pending_review')
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">⏳ Review</span>@break
                                            @case('draft')
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Draft</span>@break
                                            @case('rejected')
                                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700"
                                                      title="{{ $book->rejection_reason }}">✕ Ditolak</span>@break
                                            @case('archived')
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">Archived</span>@break
                                        @endswitch
                                    </td>

                                    {{-- Harga --}}
                                    <td class="px-4 py-3 text-right text-slate-700">{{ $book->formattedPrice() }}</td>

                                    {{-- Terjual --}}
                                    <td class="px-4 py-3 text-right font-semibold {{ $book->sales_count > 0 ? 'text-green-700' : 'text-slate-400' }}">
                                        {{ number_format($book->sales_count) }}
                                    </td>

                                    {{-- Pendapatan --}}
                                    @php $earned = (int) ($book->author_earned ?? 0); @endphp
                                    <td class="px-4 py-3 text-right font-semibold {{ $earned > 0 ? 'text-green-700' : 'text-slate-400' }}">
                                        @if($earned > 0)
                                            Rp {{ number_format($earned, 0, ',', '.') }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2 text-xs">
                                            @if(in_array($book->status, ['draft','rejected','active']))
                                                <a href="{{ route('author.books.edit', $book) }}"
                                                   class="rounded px-2 py-1 text-brand-600 hover:bg-brand-50">Edit</a>
                                            @endif
                                            @if(in_array($book->status, ['draft','rejected']))
                                                <form method="POST" action="{{ route('author.books.submit', $book) }}" class="inline">
                                                    @csrf
                                                    <button class="rounded px-2 py-1 text-amber-600 hover:bg-amber-50">Submit ulang</button>
                                                </form>
                                            @endif
                                            @if($book->status !== 'archived')
                                                <form method="POST" action="{{ route('author.books.archive', $book) }}" class="inline"
                                                      onsubmit="return confirm('Archive buku ini? Buku tidak muncul di katalog, tapi pembeli lama tetap bisa download.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded px-2 py-1 text-slate-500 hover:bg-slate-100">Archive</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @if($book->status === 'rejected' && $book->rejection_reason)
                                    <tr class="border-t border-red-100 bg-red-50">
                                        <td colspan="7" class="px-4 py-2 text-xs text-red-700">
                                            <strong>Alasan ditolak:</strong> {{ $book->rejection_reason }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>{{-- end bulk-form --}}

            @if($books->hasPages())
                <div class="mt-4">{{ $books->links() }}</div>
            @endif
        @endif
    </div>

    @push('scripts')
    <script>
    (function () {
        const selectAll   = document.getElementById('select-all');
        const bulkBar     = document.getElementById('bulk-bar');
        const bulkCount   = document.getElementById('bulk-count');
        const checkboxes  = () => document.querySelectorAll('.book-checkbox');

        function updateBar() {
            const checked = [...checkboxes()].filter(c => c.checked);
            const n = checked.length;
            bulkCount.textContent = n;
            bulkBar.classList.toggle('hidden', n === 0);
            bulkBar.classList.toggle('flex', n > 0);
            selectAll.indeterminate = n > 0 && n < checkboxes().length;
            selectAll.checked = n > 0 && n === checkboxes().length;
        }

        selectAll?.addEventListener('change', () => {
            checkboxes().forEach(c => { c.checked = selectAll.checked; });
            updateBar();
        });

        document.addEventListener('change', e => {
            if (e.target.classList.contains('book-checkbox')) updateBar();
        });

        // Klik row selain link/button juga toggle checkbox
        document.querySelectorAll('.book-row').forEach(row => {
            row.addEventListener('click', e => {
                if (e.target.closest('a,button,form,input')) return;
                const cb = row.querySelector('.book-checkbox');
                if (cb) { cb.checked = !cb.checked; updateBar(); }
            });
        });

        window.clearSelection = () => {
            checkboxes().forEach(c => { c.checked = false; });
            if (selectAll) selectAll.checked = false;
            updateBar();
        };

        window.confirmBulk = (action) => {
            const n = [...checkboxes()].filter(c => c.checked).length;
            if (n === 0) { alert('Pilih minimal 1 buku dulu.'); return false; }
            const label = action === 'archive'
                ? `Archive ${n} buku? Buku tidak akan muncul di katalog lagi.`
                : `Submit ulang ${n} buku ke moderasi?`;
            return confirm(label);
        };
    })();
    </script>
    @endpush
</x-app-layout>
