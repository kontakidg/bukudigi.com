@extends('layouts.app')

@section('title', 'Daftar Affiliate — bukudigi.com')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8">
    <header class="mb-6">
        <h1 class="text-2xl font-bold">🔗 Daftar Program Affiliate</h1>
        <p class="mt-1 text-sm text-slate-500">Isi form di bawah. Admin akan review dalam 1–2 hari kerja.</p>
    </header>

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <p class="font-semibold">Mohon perbaiki:</p>
            <ul class="ml-4 mt-1 list-disc">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('affiliate.register') }}" class="space-y-5">
        @csrf

        <div class="card p-6">
            <h2 class="text-lg font-bold">Channel promosi</h2>
            <p class="mt-1 text-sm text-slate-500">Sebutkan platform yang akan kamu pakai promosi. Minimal 1.</p>
            <div class="mt-4">
                <label for="promo_channels" class="mb-1 block text-sm font-medium text-slate-700">Daftar channel</label>
                <input id="promo_channels" name="promo_channels" type="text" required maxlength="500"
                    value="{{ old('promo_channels') }}"
                    placeholder="Contoh: Instagram @bookreviewer, TikTok @bacaaja, blog kupasbuku.id"
                    class="input">
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-lg font-bold">Motivasi</h2>
            <p class="mt-1 text-sm text-slate-500">Ceritakan singkat — kenapa kamu mau jadi affiliate? Siapa audience kamu? (Min 30 karakter)</p>
            <div class="mt-4">
                <textarea id="motivation" name="motivation" rows="5" required minlength="30" maxlength="1500"
                    placeholder="Contoh: Saya content creator review buku di Instagram dengan 5rb followers, niche self-improvement. Sering dapat pertanyaan rekomendasi ebook lokal, dan bukudigi cocok karena harga terjangkau."
                    class="input">{{ old('motivation') }}</textarea>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-lg font-bold">Komitmen affiliate</h2>
            <p class="mt-1 text-sm text-slate-500">Wajib disepakati. Pelanggaran = suspend.</p>
            <div class="mt-4 space-y-2 text-sm text-slate-700">
                <p>• Promosi <strong>jujur</strong>, tidak menjanjikan klaim palsu.</p>
                <p>• <strong>Tidak spam</strong> ke grup/komentar tidak relevan.</p>
                <p>• <strong>Tidak self-refer</strong> dan <strong>tidak fake order</strong>.</p>
                <p>• Tidak <strong>cookie stuffing</strong> atau inject iframe di situs orang.</p>
                <p>• Bersedia di-review ulang kapan saja oleh admin.</p>
            </div>
            <label class="mt-4 flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="commitment_agreed" required class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>Saya berkomitmen mematuhi semua poin di atas.</span>
            </label>
        </div>

        <div class="card p-6">
            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="agree_tos" required class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>Saya menyetujui <a href="{{ route('info.syarat') }}" class="text-emerald-600 hover:underline">Syarat &amp; Ketentuan</a> bukudigi.com termasuk ketentuan khusus program affiliate.</span>
            </label>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary flex-1 !py-3">Submit Pendaftaran</button>
            <a href="{{ route('affiliate.landing') }}" class="btn-outline !py-3">Batal</a>
        </div>
    </form>
</div>
@endsection
