<x-mail::message>
# Royalti Kamu Sudah Ditransfer! 💸

Halo **{{ $payout->author->display_name }}**,

Pembayaran royalti periode **{{ $payout->period }}** sudah berhasil ditransfer ke rekening kamu.

<x-mail::panel>
**Rekening Tujuan**
{{ $payout->bank_name }} · {{ $payout->bank_account }}
a.n. {{ $payout->bank_holder }}

---

| Keterangan | Jumlah |
|:-----------|-------:|
| Total royalti kotor | Rp {{ number_format($payout->gross_earning, 0, ',', '.') }} |
| PPh 23 ({{ $payout->pph23_rate }}%{{ $payout->npwp ? ' — dengan NPWP' : ' — tanpa NPWP' }}) | − Rp {{ number_format($payout->pph23_amount, 0, ',', '.') }} |
| Biaya transfer (50:50) | − Rp {{ number_format($payout->transfer_fee, 0, ',', '.') }} |
| **Jumlah ditransfer** | **Rp {{ number_format($payout->net_transfer, 0, ',', '.') }}** |

@if($payout->bank_ref)
**No. Referensi Transfer:** `{{ $payout->bank_ref }}`
@endif
**Tanggal Transfer:** {{ $payout->processed_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
</x-mail::panel>

Dana sudah dikirim dan akan masuk ke rekening kamu dalam 1×24 jam (tergantung bank masing-masing).

Bukti potong PPh 23 akan diterbitkan secara elektronik dan dikirim ke email ini setiap akhir tahun untuk keperluan SPT pribadi kamu.

<x-mail::button :url="url(route('author.dashboard'))" color="success">
Lihat Dashboard
</x-mail::button>

Ada pertanyaan? Balas email ini atau hubungi **support@bukudigi.com**.

**Tim bukudigi.com**
</x-mail::message>
