<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold">✍️ Daftar sebagai Penulis</h1>
        <p class="mt-1 text-sm text-slate-500">Isi data verifikasi. Admin review 1-2 hari kerja.</p>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8">
        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <p class="font-semibold">Mohon perbaiki:</p>
                <ul class="ml-4 mt-1 list-disc">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('author.register') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="card p-6">
                <h2 class="text-lg font-bold">Profil Penulis</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Nama tampil di toko</label>
                        <input id="display_name" name="display_name" type="text" value="{{ old('display_name', auth()->user()->name) }}" required maxlength="120" class="input">
                        <p class="mt-1 text-xs text-slate-500">Bisa pakai gelar, pen name, dll. Contoh: "Dr. Andi Saputra".</p>
                    </div>
                    <div>
                        <label for="bio" class="mb-1 block text-sm font-medium text-slate-700">Bio singkat <span class="text-slate-400">(opsional)</span></label>
                        <textarea id="bio" name="bio" rows="3" maxlength="1000" class="input">{{ old('bio') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">Maks 1000 karakter. Tampil di halaman buku.</p>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-bold">Data Verifikasi</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="nik" class="mb-1 block text-sm font-medium text-slate-700">NIK (16 digit)</label>
                        <input id="nik" name="nik" type="text" inputmode="numeric" value="{{ old('nik') }}" required maxlength="16" pattern="\d{16}" class="input">
                        <p class="mt-1 text-xs text-slate-500">Hanya untuk verifikasi identitas dan pelaporan pajak. Disimpan terenkripsi.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="npwp" class="mb-1 block text-sm font-medium text-slate-700">NPWP <span class="text-slate-400">(opsional)</span></label>
                        <input id="npwp" name="npwp" type="text" value="{{ old('npwp') }}" maxlength="32" class="input">
                        <p class="mt-1 text-xs text-slate-500">PPh 23 dipotong 2% (dengan NPWP) atau 4% (tanpa NPWP) dari royalti.</p>
                    </div>
                    <div>
                        <label for="ktp_image" class="mb-1 block text-sm font-medium text-slate-700">Foto KTP</label>
                        <input id="ktp_image" name="ktp_image" type="file" accept="image/*" required class="input !py-1.5">
                        <p class="mt-1 text-xs text-slate-500">Max 5 MB, JPG/PNG.</p>
                    </div>
                    <div>
                        <label for="selfie_image" class="mb-1 block text-sm font-medium text-slate-700">Selfie + KTP</label>
                        <input id="selfie_image" name="selfie_image" type="file" accept="image/*" required class="input !py-1.5">
                        <p class="mt-1 text-xs text-slate-500">Foto kamu memegang KTP.</p>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-bold">Rekening Bank</h2>
                <p class="mt-1 text-sm text-slate-500">Untuk transfer royalti bulanan.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="bank_name" class="mb-1 block text-sm font-medium text-slate-700">Nama Bank</label>
                        <select id="bank_name" name="bank_name" required class="input">
                            <option value="">Pilih bank…</option>
                            @foreach(['BCA','BNI','BRI','Mandiri','BSI','CIMB Niaga','Permata','Danamon','BTN','Mega','OCBC NISP','Maybank','Lainnya'] as $b)
                                <option value="{{ $b }}" @selected(old('bank_name')===$b)>{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="bank_account" class="mb-1 block text-sm font-medium text-slate-700">Nomor Rekening</label>
                        <input id="bank_account" name="bank_account" type="text" value="{{ old('bank_account') }}" required maxlength="64" class="input">
                    </div>
                    <div class="md:col-span-2">
                        <label for="bank_holder" class="mb-1 block text-sm font-medium text-slate-700">Nama Pemilik Rekening</label>
                        <input id="bank_holder" name="bank_holder" type="text" value="{{ old('bank_holder', auth()->user()->name) }}" required maxlength="120" class="input">
                        <p class="mt-1 text-xs text-slate-500">Harus sesuai KTP.</p>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="agree_tos" required class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>Saya menyetujui <a href="{{ route('info.syarat') }}" class="text-brand-600 hover:underline">Syarat Penulis</a> bukudigi.com dan menyatakan semua karya yang akan saya upload adalah milik saya / saya berhak menjualnya.</span>
                </label>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1 !py-3">Submit Pendaftaran</button>
                <a href="{{ route('jual') }}" class="btn-outline !py-3">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
