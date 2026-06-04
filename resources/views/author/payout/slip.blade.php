<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Payout Royalti — {{ $payout->period }} — bukudigi.com</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; padding: 32px; max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #6c47ff; padding-bottom: 16px; margin-bottom: 20px; }
        .logo { font-size: 22px; font-weight: 800; color: #6c47ff; }
        .logo span { color: #1e293b; }
        .doc-title { text-align: right; }
        .doc-title h2 { font-size: 16px; font-weight: 700; color: #1e293b; }
        .doc-title p { font-size: 11px; color: #64748b; margin-top: 2px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        .info-box h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 8px; }
        .info-box p { font-size: 13px; color: #1e293b; margin-bottom: 2px; }
        .info-box .mono { font-family: monospace; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: #64748b; padding: 8px 10px; text-align: left; }
        th.right, td.right { text-align: right; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        tr:last-child td { border-bottom: none; }
        .summary-table td { padding: 6px 10px; }
        .summary-total { background: #f0edff; font-weight: 700; font-size: 14px; }
        .summary-total td { color: #6c47ff; padding: 10px; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .status-processed { background: #dcfce7; color: #15803d; }
        .status-requested { background: #fef9c3; color: #a16207; }
        .status-processing { background: #dbeafe; color: #1d4ed8; }
        .footer { margin-top: 32px; border-top: 1px solid #e2e8f0; padding-top: 16px; display: flex; justify-content: space-between; font-size: 11px; color: #94a3b8; }
        .sign-area { text-align: right; }
        .sign-area .box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 24px; display: inline-block; text-align: center; }
        .sign-area .box p { font-size: 10px; color: #94a3b8; }
        .sign-area .box .name { margin-top: 36px; font-size: 12px; font-weight: 600; color: #334155; border-top: 1px solid #e2e8f0; padding-top: 4px; }
        @media print {
            body { padding: 16px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    {{-- Print button --}}
    <div class="no-print" style="margin-bottom:16px; display:flex; gap:8px; align-items:center;">
        <button onclick="window.print()" style="background:#6c47ff;color:#fff;border:none;padding:8px 20px;border-radius:6px;font-size:13px;cursor:pointer;font-weight:600;">🖨 Cetak / Simpan PDF</button>
        <a href="{{ route('author.payout.index') }}" style="color:#64748b;text-decoration:none;font-size:13px;">← Kembali</a>
    </div>

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="logo">buku<span>digi</span>.com</div>
            <p style="font-size:11px;color:#64748b;margin-top:2px;">Marketplace Ebook Indonesia</p>
        </div>
        <div class="doc-title">
            <h2>SLIP PAYOUT ROYALTI</h2>
            <p>No. Ref: <strong style="font-family:monospace;">#{{ str_pad($payout->id, 6, '0', STR_PAD_LEFT) }}</strong></p>
            <p>Diterbitkan: {{ now()->timezone('Asia/Jakarta')->format('d M Y') }}</p>
        </div>
    </div>

    {{-- Info grid --}}
    <div class="info-grid">
        <div class="info-box">
            <h3>Penulis</h3>
            <p style="font-weight:600;">{{ $payout->author->display_name }}</p>
            <p>{{ $payout->author->user->email }}</p>
            @if($payout->npwp)<p>NPWP: <span class="mono">{{ $payout->npwp }}</span></p>@else<p style="color:#f59e0b;">Tanpa NPWP</p>@endif
        </div>
        <div class="info-box">
            <h3>Rekening Tujuan Transfer</h3>
            <p style="font-weight:600;">{{ $payout->bank_name }}</p>
            <p class="mono">{{ $payout->bank_account }}</p>
            <p>a.n. {{ $payout->bank_holder }}</p>
        </div>
        <div class="info-box">
            <h3>Detail Payout</h3>
            <p>Periode: <strong>{{ $payout->period }}</strong></p>
            <p>Tanggal Request: {{ $payout->requested_at?->format('d M Y') }}</p>
            @if($payout->processed_at)<p>Tanggal Transfer: <strong>{{ $payout->processed_at->format('d M Y') }}</strong></p>@endif
            @if($payout->bank_ref)<p>No. Ref Bank: <strong class="mono">{{ $payout->bank_ref }}</strong></p>@endif
        </div>
        <div class="info-box" style="display:flex;align-items:center;justify-content:center;flex-direction:column;">
            <p style="font-size:11px;color:#64748b;margin-bottom:6px;">Status</p>
            <span class="status-badge status-{{ $payout->status }}" style="font-size:13px;padding:4px 16px;">
                {{ $payout->statusLabel() }}
            </span>
        </div>
    </div>

    {{-- Rincian Earning per Transaksi --}}
    @if($earnings->count())
    <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:8px;">
        Rincian Transaksi Periode {{ $payout->period }} ({{ $earnings->count() }} transaksi)
    </h3>
    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Judul Buku</th>
                <th>Metode</th>
                <th class="right">Harga</th>
                <th class="right">Royalti (70%)</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($earnings as $order)
            <tr>
                <td style="font-family:monospace;font-size:11px;">{{ $order->order_code }}</td>
                <td>{{ $order->book->title ?? '—' }}</td>
                <td style="text-transform:uppercase;font-size:11px;">{{ $order->payment_gateway ?? '—' }}</td>
                <td class="right">Rp {{ number_format($order->net_amount ?: $order->gross_amount, 0, ',', '.') }}</td>
                <td class="right" style="font-weight:600;color:#6c47ff;">Rp {{ number_format($order->author_earning, 0, ',', '.') }}</td>
                <td style="font-size:11px;color:#64748b;">{{ $order->paid_at?->format('d M H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Ringkasan Keuangan --}}
    <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:8px;">Ringkasan Pembayaran</h3>
    <table class="summary-table">
        <tbody>
            <tr>
                <td>Total Royalti Kotor</td>
                <td class="right">Rp {{ number_format($payout->gross_earning, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>
                    PPh Pasal 23 ({{ $payout->pph23_rate }}%
                    @if($payout->npwp) — dengan NPWP @else — tanpa NPWP @endif)
                </td>
                <td class="right" style="color:#ef4444;">− Rp {{ number_format($payout->pph23_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Biaya Transfer Bank (ditanggung 50:50 penulis &amp; platform)</td>
                <td class="right" style="color:#ef4444;">− Rp {{ number_format($payout->transfer_fee, 0, ',', '.') }}</td>
            </tr>
            <tr class="summary-total">
                <td>JUMLAH DITRANSFER KE REKENING</td>
                <td class="right">Rp {{ number_format($payout->net_transfer, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if($payout->admin_note)
    <div style="background:#fefce8;border:1px solid #fde047;border-radius:6px;padding:10px 14px;font-size:12px;color:#854d0e;margin-top:8px;">
        <strong>Catatan Admin:</strong> {{ $payout->admin_note }}
    </div>
    @endif

    {{-- Watermark DRAFT jika belum processed --}}
    @if($payout->status !== 'processed')
    <div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:80px;font-weight:900;color:rgba(239,68,68,0.12);pointer-events:none;white-space:nowrap;z-index:0;">
        BELUM DIPROSES
    </div>
    <div style="background:#fef2f2;border:2px solid #fca5a5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991b1b;margin-top:16px;text-align:center;">
        ⚠️ Slip ini belum resmi — transfer belum diproses oleh admin. Simpan setelah status berubah jadi <strong>Sudah Ditransfer</strong>.
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div>
            <p><strong>bukudigi.com</strong></p>
            <p>support@bukudigi.com</p>
            <p style="margin-top:8px;font-size:10px;">Dokumen ini diterbitkan secara otomatis oleh sistem bukudigi.com.<br>Bukti potong PPh 23 akan dikirim terpisah setiap akhir tahun pajak.</p>
        </div>
        <div class="sign-area">
            @if($payout->status === 'processed')
            {{-- Stamp LUNAS --}}
            <div style="position:relative;display:inline-block;margin-right:20px;vertical-align:top;margin-top:8px;">
                <div style="border:3px solid #16a34a;border-radius:50%;width:80px;height:80px;display:flex;align-items:center;justify-content:center;transform:rotate(-15deg);opacity:0.85;">
                    <div style="text-align:center;">
                        <div style="font-size:16px;font-weight:900;color:#16a34a;letter-spacing:1px;">LUNAS</div>
                        <div style="font-size:9px;color:#16a34a;font-weight:700;">{{ $payout->processed_at?->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>
            @endif
            <div class="box">
                <p>Disetujui oleh</p>
                @if($payout->status === 'processed')
                    <p style="font-size:10px;color:#64748b;margin-top:4px;">{{ $payout->processed_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
                @endif
                <div class="name">Tim bukudigi.com</div>
            </div>
        </div>
    </div>
</body>
</html>
