@extends('layouts.app')

@section('title', 'Dashboard Affiliate — bukudigi.com')

@php
    $statusBadge = [
        'pending'   => ['bg-amber-100 text-amber-700', '⏳ Menunggu Review Admin'],
        'approved'  => ['bg-emerald-100 text-emerald-700', '✅ Aktif'],
        'rejected'  => ['bg-red-100 text-red-700', '❌ Ditolak'],
        'suspended' => ['bg-red-100 text-red-700', '🚫 Suspended'],
    ];
    [$badgeClass, $badgeLabel] = $statusBadge[$affiliate->status] ?? ['bg-slate-100 text-slate-700', $affiliate->status];
    $maxCodes = \App\Models\AffiliateCode::MAX_PER_AFFILIATE;
    $canAddCode = $codes->count() < $maxCodes;
@endphp

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8">
    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="ml-4 list-disc">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">🔗 Dashboard Affiliate</h1>
            <p class="mt-1 text-sm text-slate-500">Komisi <strong>{{ rtrim(rtrim(number_format($affiliate->commission_rate, 2), '0'), '.') }}%</strong> per transaksi. Cookie 30 hari. Payout tgl 5.</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">{{ $badgeLabel }}</span>
    </header>

    @if($affiliate->status === 'pending')
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold">⏳ Aplikasi sedang di-review</p>
            <p class="mt-1">Admin akan review pendaftaran kamu dalam 1–2 hari kerja.</p>
        </div>
    @elseif($affiliate->status === 'rejected')
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">❌ Pendaftaran ditolak</p>
            <p class="mt-1">Alasan: {{ $affiliate->rejection_reason ?: 'Tidak disebutkan.' }}</p>
        </div>
    @elseif($affiliate->status === 'suspended')
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">🚫 Akun affiliate di-suspend</p>
            <p class="mt-1">Alasan: {{ $affiliate->rejection_reason ?: '—' }}</p>
        </div>
    @endif

    {{-- Stats cards --}}
    <div class="grid gap-4 md:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs text-slate-500">Saldo Tersedia</p>
            <p class="mt-1 text-xl font-bold text-emerald-700">Rp {{ number_format($affiliate->balance_available, 0, ',', '.') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Saldo Pending (cooling 7 hari)</p>
            <p class="mt-1 text-xl font-bold text-amber-700">Rp {{ number_format($affiliate->balance_pending, 0, ',', '.') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Total Earned (lifetime)</p>
            <p class="mt-1 text-xl font-bold text-slate-700">Rp {{ number_format($affiliate->total_earned, 0, ',', '.') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Konversi / Klik (lifetime)</p>
            <p class="mt-1 text-xl font-bold text-slate-700">{{ $stats['conversions_total'] }} / {{ $stats['clicks_total'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Rate: {{ $stats['conversion_rate'] }}% • 30d klik: {{ $stats['clicks_30d'] }}</p>
        </div>
    </div>

    {{-- ===== Codes management ===== --}}
    @if($affiliate->isApproved())
        <div class="mt-6 card p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold">🎟️ Kode Affiliate</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $codes->count() }} / {{ $maxCodes }} kode dipakai. Bikin kode terpisah per channel buat track performa.</p>
                </div>
                @if($canAddCode)
                    <button type="button" onclick="document.getElementById('addCodeForm').classList.toggle('hidden')"
                            class="btn-primary !py-1.5 !text-sm">+ Tambah Kode</button>
                @else
                    <span class="text-xs text-slate-500">Maksimal kode tercapai.</span>
                @endif
            </div>

            @if($canAddCode)
                <form id="addCodeForm" method="POST" action="{{ route('affiliate.codes.store') }}" class="mt-4 hidden rounded-lg border border-emerald-200 bg-emerald-50/50 p-4">
                    @csrf
                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">Label <span class="text-slate-400">(opsional)</span></label>
                            <input name="label" type="text" maxlength="60" placeholder="cth: Instagram bio" class="input">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">Kode kustom <span class="text-slate-400">(opsional)</span></label>
                            <input name="custom_code" type="text" maxlength="20" pattern="[A-Za-z0-9_-]+"
                                placeholder="Kosongkan = auto-generate" class="input font-mono uppercase">
                            <p class="mt-1 text-[10px] text-slate-500">A-Z, 0-9, _ atau -. Min 4 char.</p>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="btn-primary w-full !py-2 !text-sm">Buat Kode</button>
                        </div>
                    </div>
                </form>
            @endif

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="py-2 pr-3">Kode</th>
                            <th class="py-2 pr-3">Label</th>
                            <th class="py-2 pr-3 text-right">Klik</th>
                            <th class="py-2 pr-3 text-right">Konv.</th>
                            <th class="py-2 pr-3 text-right">Earned</th>
                            <th class="py-2 pr-3">Link</th>
                            <th class="py-2 pr-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($codes as $c)
                            @php $shareUrl = $c->shareUrl(); @endphp
                            <tr>
                                <td class="py-2 pr-3">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5 font-bold text-emerald-700">{{ $c->code }}</code>
                                    @if($c->is_default)
                                        <span class="ml-1 rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold text-blue-700">DEFAULT</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-slate-600">{{ $c->label ?: '—' }}</td>
                                <td class="py-2 pr-3 text-right">{{ $c->clicks_count }}</td>
                                <td class="py-2 pr-3 text-right">{{ $c->conversions_count }}</td>
                                <td class="py-2 pr-3 text-right">Rp {{ number_format($c->total_earned, 0, ',', '.') }}</td>
                                <td class="py-2 pr-3">
                                    <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $shareUrl }}'); this.textContent='✓ Copied'; setTimeout(()=>this.textContent='Copy link',1500)"
                                        class="text-xs text-emerald-700 hover:underline">Copy link</button>
                                </td>
                                <td class="py-2 pr-3">
                                    <div class="flex items-center gap-2">
                                        @if(! $c->is_default)
                                            <form method="POST" action="{{ route('affiliate.codes.default', $c) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs text-blue-600 hover:underline">Jadikan default</button>
                                            </form>
                                            @if($c->conversions_count === 0)
                                                <form method="POST" action="{{ route('affiliate.codes.destroy', $c) }}" class="inline"
                                                      onsubmit="return confirm('Hapus kode {{ $c->code }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:underline">Hapus</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== Generator link buku ===== --}}
    @if($affiliate->isApproved() && $codes->isNotEmpty())
        @php
            $codesJson = $codes->map(fn ($c) => ['id' => $c->id, 'code' => $c->code, 'label' => $c->label, 'is_default' => $c->is_default])->values();
            $defaultCodeId = $codes->firstWhere('is_default', true)?->id ?? $codes->first()->id;
        @endphp
        <div class="mt-6 card p-6"
             x-data="affiliateLinkGen({{ $codesJson->toJson() }}, {{ $linkableBooks->toJson() }}, {{ $defaultCodeId }})">
            <h2 class="text-lg font-bold">🔗 Generator Link Buku</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih buku + pilih kode → langsung dapat link siap share. Format: <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">https://bukudigi.com/buku/SLUG?ref=KODE</code></p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                {{-- Pilih buku --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-700">1. Cari & pilih buku</label>
                    <input x-model="search" type="search" placeholder="Ketik judul buku…"
                           class="input">
                    <div class="mt-2 max-h-56 overflow-y-auto rounded-lg border border-slate-200">
                        <template x-for="b in filteredBooks" :key="b.slug">
                            <button type="button" @click="selectedSlug = b.slug; selectedTitle = b.title; selectedUrl = b.url"
                                    class="block w-full border-b border-slate-100 px-3 py-2 text-left text-sm hover:bg-emerald-50"
                                    :class="selectedSlug === b.slug ? 'bg-emerald-100 font-semibold' : ''">
                                <span x-text="b.title" class="text-slate-800"></span>
                                <span class="text-xs text-slate-500" x-text="' · ' + b.price"></span>
                            </button>
                        </template>
                        <template x-if="filteredBooks.length === 0">
                            <p class="px-3 py-4 text-center text-xs text-slate-400">Tidak ada buku cocok.</p>
                        </template>
                    </div>
                    <p class="mt-1 text-[10px] text-slate-400">Atau ketik URL/slug manual di bawah kalau buku ga ada di list (max 60 buku top sales).</p>
                </div>

                {{-- Pilih kode + result --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-700">2. Pilih kode</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="c in codes" :key="c.id">
                            <button type="button" @click="selectedCodeId = c.id"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                                    :class="selectedCodeId === c.id ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600 hover:border-emerald-300'">
                                <span class="font-mono" x-text="c.code"></span>
                                <span x-show="c.label" class="ml-1 text-[10px] opacity-70" x-text="'(' + c.label + ')'"></span>
                            </button>
                        </template>
                    </div>

                    <label class="mt-4 mb-1 block text-xs font-medium text-slate-700">3. Atau URL kustom <span class="text-slate-400">(opsional)</span></label>
                    <input x-model="customUrl" type="text" placeholder="https://bukudigi.com/buku/slug-buku"
                           class="input font-mono text-xs">

                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                        <p class="text-[10px] font-semibold uppercase text-emerald-700">Link siap share</p>
                        <p x-show="selectedTitle && !customUrl" class="mt-0.5 text-xs text-slate-500" x-text="'Buku: ' + selectedTitle"></p>
                        <div class="mt-2 flex gap-2">
                            <input type="text" readonly :value="finalUrl"
                                   class="input flex-1 !bg-white font-mono text-xs">
                            <button type="button" @click="copy"
                                    class="btn-primary !py-1.5 !text-xs whitespace-nowrap"
                                    x-text="copied ? '✓ Copied' : 'Copy'"></button>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <a :href="finalUrl" target="_blank" rel="noopener"
                               class="text-xs text-emerald-700 hover:underline">↗ Buka di tab baru</a>
                            <button type="button" @click="shareWa"
                                    class="text-xs text-emerald-700 hover:underline">📱 Share via WhatsApp</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            function affiliateLinkGen(codes, books, defaultCodeId) {
                return {
                    codes,
                    books,
                    search: '',
                    selectedSlug: books.length ? books[0].slug : '',
                    selectedTitle: books.length ? books[0].title : '',
                    selectedUrl: books.length ? books[0].url : 'https://bukudigi.com',
                    selectedCodeId: defaultCodeId,
                    customUrl: '',
                    copied: false,
                    get filteredBooks() {
                        if (!this.search) return this.books;
                        const s = this.search.toLowerCase();
                        return this.books.filter(b => b.title.toLowerCase().includes(s));
                    },
                    get currentCode() {
                        return this.codes.find(c => c.id === this.selectedCodeId) || this.codes[0];
                    },
                    get finalUrl() {
                        const base = (this.customUrl || this.selectedUrl || 'https://bukudigi.com').trim();
                        const sep = base.includes('?') ? '&' : '?';
                        return base + sep + 'ref=' + (this.currentCode?.code || '');
                    },
                    copy() {
                        navigator.clipboard.writeText(this.finalUrl);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 1500);
                    },
                    shareWa() {
                        const text = encodeURIComponent('Cek buku ini di bukudigi.com 👉 ' + this.finalUrl);
                        window.open('https://wa.me/?text=' + text, '_blank');
                    },
                }
            }
        </script>
        @endpush
    @endif

    {{-- ===== Chart Performance ===== --}}
    @if($affiliate->isApproved())
        @php
            $rangeLabels = ['14' => '14 hari', '30' => '1 bulan', '180' => '6 bulan', '365' => '1 tahun', 'custom' => 'Custom'];
        @endphp
        <div class="mt-6 card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">📈 Performance Chart</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ \Illuminate\Support\Carbon::parse($chartRange['start'])->format('j M Y') }} – {{ \Illuminate\Support\Carbon::parse($chartRange['end'])->format('j M Y') }}
                        <span class="ml-1 text-slate-400">({{ ['daily'=>'per hari','weekly'=>'per minggu','monthly'=>'per bulan'][$chart['granularity']] ?? '' }})</span>
                    </p>
                </div>
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    @foreach(['14','30','180','365'] as $r)
                        <button name="range" value="{{ $r }}" type="submit"
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $chartRange['preset'] === $r ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600 hover:border-emerald-300' }}">
                            {{ $rangeLabels[$r] }}
                        </button>
                    @endforeach
                </form>
            </div>

            @if(array_sum($chart['clicks']) === 0 && array_sum($chart['conversions']) === 0)
                <div class="mt-6 rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                    Belum ada data klik/konversi di periode ini. Mulai share link affiliate kamu! 🚀
                </div>
            @else
                <div class="mt-4 relative h-80">
                    <canvas id="chartAffiliate"></canvas>
                </div>
            @endif
        </div>

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
            <script>
                (function () {
                    const el = document.getElementById('chartAffiliate');
                    if (!el) return;
                    const labels = @json($chart['labels']);
                    const clicks = @json($chart['clicks']);
                    const conversions = @json($chart['conversions']);
                    const earnings = @json($chart['earnings']);

                    new Chart(el, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Klik', data: clicks,
                                    borderColor: '#0ea5e9', backgroundColor: '#0ea5e922',
                                    yAxisID: 'yClicks', tension: 0.3, borderWidth: 2.5,
                                    pointRadius: 2, pointHoverRadius: 5, fill: false,
                                },
                                {
                                    label: 'Konversi', data: conversions,
                                    borderColor: '#10b981', backgroundColor: '#10b98122',
                                    yAxisID: 'yConv', tension: 0.3, borderWidth: 2.5,
                                    borderDash: [5, 4], pointRadius: 2, pointHoverRadius: 5, fill: false,
                                    pointStyle: 'rectRot',
                                },
                            ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 14, padding: 8, font: { size: 11 } } },
                                tooltip: {
                                    mode: 'index', intersect: false,
                                    callbacks: {
                                        afterBody: function (items) {
                                            const i = items[0].dataIndex;
                                            const e = earnings[i] || 0;
                                            if (e > 0) {
                                                return 'Earning: Rp ' + e.toLocaleString('id-ID');
                                            }
                                            return '';
                                        }
                                    }
                                },
                            },
                            scales: {
                                x: { ticks: { font: { size: 10 }, autoSkip: true, maxTicksLimit: 10 }, grid: { display: false } },
                                yClicks: {
                                    type: 'linear', position: 'left', beginAtZero: true,
                                    title: { display: true, text: 'Klik', font: { size: 11, weight: 'bold' }, color: '#0369a1' },
                                    ticks: { precision: 0, font: { size: 10 } }, grid: { color: '#f1f5f9' },
                                },
                                yConv: {
                                    type: 'linear', position: 'right', beginAtZero: true,
                                    title: { display: true, text: 'Konversi', font: { size: 11, weight: 'bold' }, color: '#047857' },
                                    ticks: { precision: 0, font: { size: 10 } }, grid: { drawOnChartArea: false },
                                },
                            },
                        },
                    });
                })();
            </script>
        @endpush
    @endif

    {{-- Riwayat earning --}}
    <div class="mt-6 card p-6">
        <h2 class="text-lg font-bold">Riwayat Komisi</h2>
        @if($earnings->isEmpty())
            <p class="mt-4 text-sm text-slate-500">Belum ada konversi. Share link kamu di sosmed/blog/komunitas.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="py-2 pr-3">Tanggal</th>
                            <th class="py-2 pr-3">Order</th>
                            <th class="py-2 pr-3">Buku</th>
                            <th class="py-2 pr-3">Kode</th>
                            <th class="py-2 pr-3">Komisi</th>
                            <th class="py-2 pr-3">Status</th>
                            <th class="py-2 pr-3">Available</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($earnings as $e)
                            <tr>
                                <td class="py-2 pr-3 text-slate-600">{{ $e->created_at->format('d M Y') }}</td>
                                <td class="py-2 pr-3 font-mono text-xs">{{ $e->order->order_code }}</td>
                                <td class="py-2 pr-3">{{ \Illuminate\Support\Str::limit($e->order->book->title ?? '—', 32) }}</td>
                                <td class="py-2 pr-3 text-xs">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5">{{ $e->affiliateCode->code ?? '—' }}</code>
                                </td>
                                <td class="py-2 pr-3 font-semibold text-emerald-700">Rp {{ number_format($e->amount, 0, ',', '.') }}</td>
                                <td class="py-2 pr-3">
                                    @php
                                        $sBadge = [
                                            'pending'   => 'bg-amber-100 text-amber-700',
                                            'available' => 'bg-emerald-100 text-emerald-700',
                                            'paid'      => 'bg-blue-100 text-blue-700',
                                            'cancelled' => 'bg-red-100 text-red-700',
                                        ][$e->status] ?? 'bg-slate-100 text-slate-700';
                                    @endphp
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sBadge }}">{{ $e->status }}</span>
                                </td>
                                <td class="py-2 pr-3 text-xs text-slate-500">{{ $e->available_at?->format('d M Y') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Riwayat payout --}}
    @if($payouts->isNotEmpty())
        <div class="mt-6 card p-6">
            <h2 class="text-lg font-bold">Riwayat Payout</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="py-2 pr-3">Periode</th>
                            <th class="py-2 pr-3">Gross</th>
                            <th class="py-2 pr-3">Fee</th>
                            <th class="py-2 pr-3">Net Transfer</th>
                            <th class="py-2 pr-3">Status</th>
                            <th class="py-2 pr-3">Tanggal Proses</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($payouts as $p)
                            <tr>
                                <td class="py-2 pr-3 font-medium">{{ $p->period }}</td>
                                <td class="py-2 pr-3">Rp {{ number_format($p->gross_earning, 0, ',', '.') }}</td>
                                <td class="py-2 pr-3 text-slate-500">Rp {{ number_format($p->transfer_fee, 0, ',', '.') }}</td>
                                <td class="py-2 pr-3 font-semibold text-emerald-700">Rp {{ number_format($p->net_transfer, 0, ',', '.') }}</td>
                                <td class="py-2 pr-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $p->status === 'processed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $p->status }}</span>
                                </td>
                                <td class="py-2 pr-3 text-xs text-slate-500">{{ $p->processed_at?->format('d M Y') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
