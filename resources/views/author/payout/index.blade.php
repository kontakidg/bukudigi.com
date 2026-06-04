<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">💸 Payout Royalti</h1>
                <p class="mt-1 text-sm text-slate-500">Minta pembayaran royalti & lihat riwayat payout kamu.</p>
            </div>
            <a href="{{ route('author.dashboard') }}" class="btn-outline !py-2 !text-sm">← Dashboard</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 space-y-8">

        @if(session('status'))
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        {{-- Saldo & Request --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Saldo Tersedia</p>
                <p class="mt-1 text-2xl font-bold text-brand-600">Rp {{ number_format($author->balance_available, 0, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-slate-400">Bisa di-request sekarang</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Saldo Menahan</p>
                <p class="mt-1 text-2xl font-bold text-slate-600">Rp {{ number_format($author->balance_pending, 0, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-slate-400">Cooling 7 hari</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium text-slate-500">Total Diterima</p>
                <p class="mt-1 text-2xl font-bold text-slate-600">Rp {{ number_format($author->total_earned, 0, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-slate-400">Sejak bergabung</p>
            </div>
        </div>

        {{-- Form Request Payout --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                <h2 class="font-semibold">Minta Payout</h2>
            </div>
            <div class="p-5">
                @if(!$calc['can_payout'])
                    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                        ⚠️ Saldo tersedia <strong>Rp {{ number_format($author->balance_available, 0, ',', '.') }}</strong> belum mencapai
                        minimum <strong>Rp {{ number_format($calc['threshold'], 0, ',', '.') }}</strong>.
                        Tunggu sampai lebih banyak penjualan masuk.
                    </div>
                @elseif(empty($author->bank_account))
                    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                        ⚠️ Data rekening bank belum diisi.
                        <a href="{{ route('author.bank.edit') }}" class="ml-1 font-semibold underline">Isi sekarang →</a>
                    </div>
                @else
                    {{-- Rincian payout --}}
                    <div class="overflow-hidden rounded-lg border border-slate-200 text-sm">
                        <table class="w-full">
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="px-4 py-2.5 text-slate-600">Total royalti tersedia</td>
                                    <td class="px-4 py-2.5 text-right font-medium">Rp {{ number_format($calc['gross'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2.5 text-slate-600">
                                        PPh 23 ({{ $calc['pph23_rate'] }}%
                                        @if($author->npwp)
                                            — dengan NPWP <span class="text-green-600">✓</span>
                                        @else
                                            — <span class="text-amber-600">tanpa NPWP, rate lebih tinggi</span>
                                        @endif
                                        )
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-red-600">− Rp {{ number_format($calc['pph23_amount'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2.5 text-slate-600">Biaya transfer bank (50:50)</td>
                                    <td class="px-4 py-2.5 text-right text-red-600">− Rp {{ number_format($calc['transfer_fee'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-brand-50">
                                    <td class="px-4 py-2.5 font-semibold text-brand-900">Kamu terima ke rekening</td>
                                    <td class="px-4 py-2.5 text-right text-xl font-bold text-brand-700">Rp {{ number_format($calc['net'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700">
                        <p class="font-medium">Rekening tujuan:</p>
                        <p class="mt-1">{{ $author->bank_name }} · {{ $author->bank_account }} · a.n. {{ $author->bank_holder }}</p>
                        @if(!$author->npwp)
                            <p class="mt-2 text-amber-700 text-xs">💡 Punya NPWP? <a href="{{ route('author.bank.edit') }}" class="underline">Update di sini</a> untuk dapat rate PPh 23 lebih rendah (2% vs 4%).</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('author.payout.request') }}" class="mt-4"
                          onsubmit="return confirm('Request payout Rp {{ number_format($calc['net'], 0, ',', '.') }} ke {{ $author->bank_name }} {{ $author->bank_account }}? Admin akan memproses dalam 1-3 hari kerja.')">
                        @csrf
                        <button type="submit" class="btn-primary w-full !py-3">
                            💸 Minta Payout Sekarang
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Riwayat Payout --}}
        <div>
            <h2 class="text-lg font-bold mb-3">Riwayat Payout</h2>
            @if($payouts->isEmpty())
                <div class="rounded-xl border-2 border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                    Belum ada riwayat payout.
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Periode</th>
                                <th class="px-4 py-3 text-right">Gross</th>
                                <th class="px-4 py-3 text-right">PPh 23</th>
                                <th class="px-4 py-3 text-right">Diterima</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Tanggal</th>
                                <th class="px-4 py-3 text-right">Slip</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payouts as $payout)
                                <tr class="border-t border-slate-100 hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono font-medium">{{ $payout->period }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">Rp {{ number_format($payout->gross_earning, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-red-500 text-xs">− Rp {{ number_format($payout->pph23_amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $payout->status === 'processed' ? 'text-green-700' : 'text-slate-700' }}">
                                        Rp {{ number_format($payout->net_transfer, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php $colors = ['requested'=>'amber','processing'=>'blue','processed'=>'green','failed'=>'red']; $c = $colors[$payout->status] ?? 'gray'; @endphp
                                        <span class="rounded-full bg-{{ $c }}-100 px-2 py-0.5 text-[10px] font-semibold text-{{ $c }}-700">
                                            {{ $payout->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500">
                                        {{ ($payout->processed_at ?? $payout->requested_at)?->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('author.payout.slip', $payout) }}" target="_blank"
                                           class="text-xs text-brand-600 hover:underline">🖨 Cetak</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $payouts->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
