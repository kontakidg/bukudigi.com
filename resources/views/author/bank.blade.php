<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold">💳 Data Rekening Bank</h1>
            <p class="mt-1 text-sm text-slate-500">Tempat royalti kamu ditransfer setiap tanggal 5 bulanan.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8">
        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="ml-4 list-disc">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('author.bank.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="card p-6">
                <h2 class="text-lg font-bold">Rekening Bank</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Nama pemilik rekening <strong>harus sesuai KTP</strong> dan nama daftar di akun bukudigi.
                    Beda nama = payout ditunda sampai diperbaiki.
                </p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="bank_name" class="mb-1 block text-sm font-medium text-slate-700">Nama Bank</label>
                        <select id="bank_name" name="bank_name" required class="input">
                            <option value="">Pilih bank…</option>
                            @foreach(['BCA','BNI','BRI','Mandiri','BSI','CIMB Niaga','Permata','Danamon','BTN','Mega','OCBC NISP','Maybank','Lainnya'] as $b)
                                <option value="{{ $b }}" @selected(old('bank_name', $author->bank_name)===$b)>{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="bank_account" class="mb-1 block text-sm font-medium text-slate-700">Nomor Rekening</label>
                        <input id="bank_account" name="bank_account" type="text" inputmode="numeric"
                               value="{{ old('bank_account', $author->bank_account) }}"
                               required maxlength="64" class="input"
                               placeholder="contoh: 1234567890">
                    </div>
                    <div class="md:col-span-2">
                        <label for="bank_holder" class="mb-1 block text-sm font-medium text-slate-700">Nama Pemilik Rekening</label>
                        <input id="bank_holder" name="bank_holder" type="text"
                               value="{{ old('bank_holder', $author->bank_holder ?: auth()->user()->name) }}"
                               required maxlength="120" class="input">
                        <p class="mt-1 text-xs text-slate-500">Sesuai KTP.</p>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-bold">NPWP <span class="text-sm font-normal text-slate-500">(opsional)</span></h2>
                <div class="mt-4">
                    <label for="npwp" class="mb-1 block text-sm font-medium text-slate-700">Nomor NPWP</label>
                    <input id="npwp" name="npwp" type="text"
                           value="{{ old('npwp', $author->npwp) }}"
                           maxlength="32" class="input"
                           placeholder="contoh: 12.345.678.9-012.345">
                    <p class="mt-1 text-xs text-slate-500">
                        Tanpa NPWP, PPh 23 dipotong 4% (vs 2% kalau ada).
                        Daftar NPWP gratis online di <a href="https://pajak.go.id" target="_blank" class="text-brand-600 hover:underline">pajak.go.id</a>.
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1 !py-3">Simpan Data Bank</button>
                <a href="{{ route('author.dashboard') }}" class="btn-outline !py-3">Batal</a>
            </div>
        </form>

        <p class="mt-6 text-center text-xs text-slate-500">
            Data bank disimpan terenkripsi. Cuma kamu &amp; admin keuangan yang bisa akses.
            <a href="{{ route('info.privasi') }}" class="text-brand-600 hover:underline">Kebijakan Privasi →</a>
        </p>
    </div>
</x-app-layout>
