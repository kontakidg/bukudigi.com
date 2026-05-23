<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">📊 Dashboard Penulis</h1>
                <p class="mt-1 text-sm text-slate-500">Halo, {{ $author->display_name }}!</p>
            </div>
            <div class="flex items-center gap-2">
                @if($author->status === 'pending')
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">⏳ Menunggu Verifikasi</span>
                @elseif($author->status === 'verified')
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">✓ Terverifikasi</span>
                @else
                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">⛔ Suspended</span>
                @endif
                @if($author->status === 'verified')
                    <a href="{{ route('author.pen-names.index') }}" class="btn-outline !py-1.5 !text-sm">🖋️ Pen Name</a>
                    <a href="{{ route('author.books.index') }}" class="btn-outline !py-1.5 !text-sm">📕 Buku</a>
                    <a href="{{ route('author.books.create') }}" class="btn-primary !py-1.5 !text-sm">+ Upload</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        @if($author->status === 'pending')
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="font-semibold">⏳ Pendaftaran kamu sedang di-review</p>
                <p class="mt-1">Admin akan verifikasi pendaftaran kamu dalam 1-2 hari kerja. Kamu akan dapat notifikasi WhatsApp begitu disetujui.</p>
            </div>
        @endif

        {{-- ===== Performance Chart ===== --}}
        @if($allBooks->isNotEmpty())
            @php
                $rangeLabels = [
                    '14' => '14 hari',
                    '30' => '1 bulan',
                    '180' => '6 bulan',
                    '365' => '1 tahun',
                    'custom' => 'Custom',
                ];
                $activeRangeLabel = $rangeLabels[$chartRange['preset']] ?? '1 bulan';
            @endphp
            <div class="mb-8 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                 x-data="{ pickerOpen: false, customOpen: {{ $chartRange['preset'] === 'custom' ? 'true' : 'false' }} }">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">📈 Performa Buku <span class="text-slate-400">({{ $activeRangeLabel }})</span></h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $selectedBooks->count() }} dari {{ $allBooks->count() }} buku ·
                            {{ \Illuminate\Support\Carbon::parse($chartRange['start'])->format('j M Y') }} – {{ \Illuminate\Support\Carbon::parse($chartRange['end'])->format('j M Y') }}
                            <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">
                                @switch($chart['granularity'] ?? 'daily')
                                    @case('monthly') per-bulan @break
                                    @case('weekly')  per-minggu @break
                                    @default        per-hari
                                @endswitch
                            </span>
                        </p>
                    </div>

                    @if($allBooks->count() > 5)
                        <button type="button" @click="pickerOpen = !pickerOpen"
                                class="btn-outline !py-1.5 !text-xs">
                            ⚙️ Pilih buku (max 5)
                        </button>
                    @endif
                </div>

                {{-- Range selector --}}
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @foreach(['14' => '14 hari', '30' => '1 bulan', '180' => '6 bulan', '365' => '1 tahun'] as $r => $label)
                        @php
                            $params = array_filter([
                                'range' => $r,
                                'books' => $selectedBooks->pluck('id')->all() ?: null,
                            ]);
                            $url = route('author.dashboard', $params);
                            $isActive = $chartRange['preset'] === $r;
                        @endphp
                        <a href="{{ $url }}"
                           class="rounded-lg px-3 py-1.5 text-xs font-semibold transition
                                  {{ $isActive
                                      ? 'bg-brand-600 text-white shadow-sm'
                                      : 'border border-slate-200 bg-white text-slate-700 hover:border-brand-400 hover:text-brand-600' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                    <button type="button" @click="customOpen = !customOpen"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition
                                   {{ $chartRange['preset'] === 'custom'
                                       ? 'bg-brand-600 text-white shadow-sm'
                                       : 'border border-slate-200 bg-white text-slate-700 hover:border-brand-400 hover:text-brand-600' }}">
                        📅 Custom
                    </button>
                </div>

                {{-- Custom date range form --}}
                <form method="GET" x-show="customOpen" x-cloak x-transition
                      class="mt-3 flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <input type="hidden" name="range" value="custom">
                    @foreach($selectedBooks as $b)
                        <input type="hidden" name="books[]" value="{{ $b->id }}">
                    @endforeach
                    <div>
                        <label class="block text-[10px] font-semibold uppercase text-slate-600">Mulai</label>
                        <input type="date" name="start"
                               value="{{ $chartRange['preset'] === 'custom' ? $chartRange['start'] : '' }}"
                               max="{{ \Illuminate\Support\Carbon::today()->format('Y-m-d') }}"
                               required
                               class="input !py-1.5 !text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase text-slate-600">Sampai</label>
                        <input type="date" name="end"
                               value="{{ $chartRange['preset'] === 'custom' ? $chartRange['end'] : '' }}"
                               max="{{ \Illuminate\Support\Carbon::today()->format('Y-m-d') }}"
                               required
                               class="input !py-1.5 !text-xs">
                    </div>
                    <button type="submit" class="btn-primary !py-1.5 !text-xs">Terapkan</button>
                    <span class="ml-auto text-[10px] text-slate-500">Maks 2 tahun ke belakang</span>
                </form>

                {{-- Book picker (kalau >5 buku) --}}
                @if($allBooks->count() > 5)
                    <form method="GET" x-show="pickerOpen" x-cloak x-transition
                          class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        {{-- Preserve range selection saat ganti buku --}}
                        <input type="hidden" name="range" value="{{ $chartRange['preset'] }}">
                        @if($chartRange['preset'] === 'custom')
                            <input type="hidden" name="start" value="{{ $chartRange['start'] }}">
                            <input type="hidden" name="end" value="{{ $chartRange['end'] }}">
                        @endif
                        <p class="mb-2 text-xs font-semibold text-slate-700">Pilih maksimal 5 buku:</p>
                        <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3"
                             x-data="{ count: {{ $selectedBooks->count() }} }">
                            @foreach($allBooks as $b)
                                <label class="flex items-center gap-2 rounded border border-slate-200 bg-white px-3 py-2 text-sm hover:border-brand-400">
                                    <input type="checkbox" name="books[]" value="{{ $b->id }}"
                                           @checked($selectedBooks->contains('id', $b->id))
                                           @change="
                                               if ($el.checked) { count++ } else { count-- }
                                               // Disable unchecked kalau sudah 5
                                               $root.querySelectorAll('input[name=\'books[]\']:not(:checked)').forEach(i => i.disabled = count >= 5);
                                           "
                                           class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    <span class="flex-1 truncate" title="{{ $b->title }}">{{ $b->title }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $b->sales_count }}×</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            <button type="submit" class="btn-primary !py-1.5 !text-xs">Terapkan</button>
                            @php
                                $resetParams = array_filter([
                                    'range' => $chartRange['preset'] !== '30' ? $chartRange['preset'] : null,
                                    'start' => $chartRange['preset'] === 'custom' ? $chartRange['start'] : null,
                                    'end' => $chartRange['preset'] === 'custom' ? $chartRange['end'] : null,
                                ]);
                            @endphp
                            <a href="{{ route('author.dashboard', $resetParams) }}" class="btn-ghost !py-1.5 !text-xs">Reset ke Top 5</a>
                            <span class="ml-auto text-[10px] text-slate-500" x-text="`${count}/5 buku dipilih`"></span>
                        </div>
                    </form>
                @endif

                @if($selectedBooks->isEmpty())
                    <p class="mt-6 rounded-lg bg-slate-50 px-3 py-6 text-center text-sm text-slate-500">
                        Belum ada buku untuk ditampilkan di chart.
                    </p>
                @else
                    <div class="mt-5">
                        <div class="mb-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-600">
                            <span class="inline-flex items-center gap-1.5">
                                <svg width="22" height="2" viewBox="0 0 22 2"><line x1="0" y1="1" x2="22" y2="1" stroke="#475569" stroke-width="2"/></svg>
                                <span>Garis solid = 👁 Views</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg width="22" height="2" viewBox="0 0 22 2"><line x1="0" y1="1" x2="22" y2="1" stroke="#475569" stroke-width="2" stroke-dasharray="4 3"/></svg>
                                <span>Garis putus = 🛒 Sales</span>
                            </span>
                            <span class="ml-auto text-slate-400">Warna sama = buku yang sama</span>
                        </div>
                        <div class="relative h-80">
                            <canvas id="chart-performance"></canvas>
                        </div>
                    </div>
                @endif
            </div>

            @push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
                <script>
                    (function () {
                        const labels = @json($chart['labels']);
                        const viewsData = @json($chart['views']);
                        const salesData = @json($chart['sales']);
                        // Palette 5 warna kontras (1 warna = 1 buku, dipakai untuk views + sales line)
                        const palette = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444'];

                        // Build datasets: 2 line per buku (views solid kiri-axis, sales dashed kanan-axis)
                        const datasets = [];
                        viewsData.forEach((s, i) => {
                            const color = palette[i % palette.length];
                            datasets.push({
                                label: s.label + ' — Views',
                                data: s.data,
                                borderColor: color,
                                backgroundColor: color + '22',
                                tension: 0.3,
                                borderWidth: 2.5,
                                pointRadius: 2,
                                pointHoverRadius: 5,
                                fill: false,
                                yAxisID: 'yViews',
                            });
                            datasets.push({
                                label: s.label + ' — Sales',
                                data: (salesData[i] || { data: [] }).data,
                                borderColor: color,
                                backgroundColor: color + '22',
                                borderDash: [5, 4],
                                tension: 0.3,
                                borderWidth: 2,
                                pointRadius: 2,
                                pointHoverRadius: 5,
                                pointStyle: 'rectRot',
                                fill: false,
                                yAxisID: 'ySales',
                            });
                        });

                        const opts = {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 14, padding: 8, font: { size: 11 }, usePointStyle: false }
                                },
                                tooltip: { mode: 'index', intersect: false }
                            },
                            scales: {
                                x: {
                                    ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
                                    grid: { display: false }
                                },
                                yViews: {
                                    type: 'linear', position: 'left',
                                    beginAtZero: true,
                                    title: { display: true, text: 'Views', font: { size: 11, weight: 'bold' }, color: '#475569' },
                                    ticks: { precision: 0, font: { size: 10 } },
                                    grid: { color: '#f1f5f9' }
                                },
                                ySales: {
                                    type: 'linear', position: 'right',
                                    beginAtZero: true,
                                    title: { display: true, text: 'Sales', font: { size: 11, weight: 'bold' }, color: '#475569' },
                                    ticks: { precision: 0, font: { size: 10 } },
                                    grid: { drawOnChartArea: false }  // jangan double grid
                                }
                            }
                        };

                        const el = document.getElementById('chart-performance');
                        if (el) new Chart(el, { type: 'line', data: { labels, datasets }, options: opts });
                    })();
                </script>
            @endpush
        @endif

        @if(! empty($showBankReminder))
            <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">
                <p class="font-semibold">💳 Lengkapi data rekening bank</p>
                <p class="mt-1">Kamu sudah punya saldo / penjualan, tapi data rekening belum lengkap. Royalti tidak bisa di-transfer sampai data bank diisi.</p>
                <a href="{{ route('author.bank.edit') }}" class="mt-2 inline-block font-semibold text-orange-900 hover:underline">Isi data bank di profile →</a>
            </div>
        @elseif(! empty($needsBankInfo) && $author->status === 'verified')
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-semibold">📝 Data bank belum diisi</p>
                <p class="mt-1">Tidak wajib sekarang — bisa diisi nanti saat siap menerima payout royalti. <a href="{{ route('author.bank.edit') }}" class="font-semibold text-brand-600 hover:underline">Lengkapi →</a></p>
            </div>
        @endif

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Saldo Tersedia</p>
                <p class="mt-1 text-xl font-bold text-brand-600">Rp {{ number_format($author->balance_available, 0, ',', '.') }}</p>
                <p class="mt-1 text-[10px] text-slate-400">Akan ditransfer tgl 5</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Saldo Menahan</p>
                <p class="mt-1 text-xl font-bold text-slate-700">Rp {{ number_format($author->balance_pending, 0, ',', '.') }}</p>
                <p class="mt-1 text-[10px] text-slate-400">Cooling 7 hari</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Total Pendapatan</p>
                <p class="mt-1 text-xl font-bold text-slate-700">Rp {{ number_format($author->total_earned, 0, ',', '.') }}</p>
                <p class="mt-1 text-[10px] text-slate-400">Sejak bergabung</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Total Penjualan</p>
                <p class="mt-1 text-xl font-bold text-slate-700">{{ number_format($stats['total_sales']) }}</p>
                <p class="mt-1 text-[10px] text-slate-400">Buku terjual</p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <div class="card p-5">
                <p class="text-xs text-slate-500">Buku Live</p>
                <p class="text-2xl font-bold">{{ $stats['active_books'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs text-slate-500">Menunggu Moderasi</p>
                <p class="text-2xl font-bold">{{ $stats['pending_books'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs text-slate-500">Total Buku</p>
                <p class="text-2xl font-bold">{{ $stats['total_books'] }}</p>
            </div>
        </div>

        {{-- Books list --}}
        <div class="mt-8">
            <h2 class="text-lg font-bold">Buku Saya</h2>
            @if($books->isEmpty())
                <div class="mt-3 rounded-xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="text-4xl">📕</p>
                    <p class="mt-2 font-semibold">Belum ada buku.</p>
                    <p class="mt-1 text-sm text-slate-500">@if($author->status === 'verified')Mulai upload buku pertama kamu.@else Tunggu verifikasi admin dulu sebelum upload.@endif</p>
                </div>
            @else
                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                            <tr><th class="px-4 py-2">Judul</th><th class="px-4 py-2">Status</th><th class="px-4 py-2 text-right">Harga</th><th class="px-4 py-2 text-right">Terjual</th></tr>
                        </thead>
                        <tbody>
                            @foreach($books as $book)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-3">{{ $book->title }}</td>
                                    <td class="px-4 py-3">
                                        @switch($book->status)
                                            @case('active')<span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Live</span>@break
                                            @case('pending_review')<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Review</span>@break
                                            @case('rejected')<span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">Ditolak</span>@break
                                            @case('archived')<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Archive</span>@break
                                            @default<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $book->status }}</span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ $book->formattedPrice() }}</td>
                                    <td class="px-4 py-3 text-right">{{ $book->sales_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
