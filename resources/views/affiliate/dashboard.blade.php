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
    $shareUrl = $affiliate->isApproved() ? $affiliate->shareUrl() : null;
@endphp

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8">
    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">🔗 Dashboard Affiliate</h1>
            <p class="mt-1 text-sm text-slate-500">Kode kamu: <code class="rounded bg-slate-100 px-1.5 py-0.5 text-emerald-700">{{ $affiliate->code }}</code></p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">{{ $badgeLabel }}</span>
    </header>

    @if($affiliate->status === 'pending')
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold">⏳ Aplikasi sedang di-review</p>
            <p class="mt-1">Admin akan review pendaftaran kamu dalam 1–2 hari kerja. Kamu akan mendapat notifikasi WhatsApp/email saat status berubah.</p>
        </div>
    @elseif($affiliate->status === 'rejected')
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">❌ Pendaftaran ditolak</p>
            <p class="mt-1">Alasan: {{ $affiliate->rejection_reason ?: 'Tidak disebutkan. Hubungi support.' }}</p>
        </div>
    @elseif($affiliate->status === 'suspended')
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">🚫 Akun affiliate kamu di-suspend</p>
            <p class="mt-1">Alasan: {{ $affiliate->rejection_reason ?: '—' }}. Hubungi support@bukudigi.com kalau merasa ini kesalahan.</p>
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

    {{-- Share link --}}
    @if($shareUrl)
        <div class="mt-6 card p-6">
            <h2 class="text-lg font-bold">🔗 Link affiliate kamu</h2>
            <p class="mt-1 text-sm text-slate-500">Share link ini. Tiap klik akan di-track 30 hari — pembeli yang beli dalam window itu kasih kamu 10% komisi.</p>

            <div class="mt-4 space-y-2">
                <label class="text-xs font-medium text-slate-600">Link homepage (umum)</label>
                <div class="flex gap-2">
                    <input type="text" readonly value="{{ $shareUrl }}" id="affLink" class="input flex-1 font-mono text-xs">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('affLink').value); this.textContent='✓ Copied'" class="btn-outline whitespace-nowrap">Copy</button>
                </div>

                <label class="mt-3 text-xs font-medium text-slate-600">Atau, append <code>?ref={{ $affiliate->code }}</code> ke URL buku spesifik:</label>
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3 text-xs">
                    <code>{{ url('/buku/contoh-judul-buku') }}?ref={{ $affiliate->code }}</code>
                </div>
            </div>
        </div>
    @endif

    {{-- Riwayat earning --}}
    <div class="mt-6 card p-6">
        <h2 class="text-lg font-bold">Riwayat Komisi</h2>
        @if($earnings->isEmpty())
            <p class="mt-4 text-sm text-slate-500">Belum ada konversi. Mulai share link kamu di sosmed/blog/komunitas.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="py-2 pr-3">Tanggal</th>
                            <th class="py-2 pr-3">Order</th>
                            <th class="py-2 pr-3">Buku</th>
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
                                <td class="py-2 pr-3">{{ \Illuminate\Support\Str::limit($e->order->book->title ?? '—', 40) }}</td>
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
