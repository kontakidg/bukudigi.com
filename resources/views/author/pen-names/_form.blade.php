@php
    $isEdit = isset($penName) && $penName->exists;
    $action = $isEdit ? route('author.pen-names.update', $penName) : route('author.pen-names.store');
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        <ul class="ml-4 list-disc">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($isEdit) @method('PATCH') @endif

    <div class="card p-6">
        <h2 class="text-lg font-bold">Pen Name</h2>
        <div class="mt-4 space-y-4">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama yang tampil di publik</label>
                <input id="name" name="name" type="text" required maxlength="120"
                       value="{{ old('name', $penName->name ?? '') }}"
                       placeholder="Contoh: Andi Saputra atau A. S. Permata"
                       class="input">
                <p class="mt-1 text-xs text-slate-500">Bisa nama asli, gelar, atau nama samaran. Akan jadi URL: <code class="rounded bg-slate-100 px-1 text-[10px]">/penulis/[slug-otomatis]</code></p>
            </div>

            <div>
                <label for="bio" class="mb-1 block text-sm font-medium text-slate-700">Bio singkat <span class="text-slate-400">(opsional)</span></label>
                <textarea id="bio" name="bio" rows="4" maxlength="1000" class="input">{{ old('bio', $penName->bio ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Tampil di halaman buku & profil pen name. Maks 1000 karakter.</p>
            </div>

            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_default" value="1"
                       @checked(old('is_default', $penName->is_default ?? false))
                       class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>
                    Jadikan default —
                    <span class="text-slate-500">otomatis dipakai saat upload buku baru kalau tidak pilih pen name lain.</span>
                </span>
            </label>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn-primary flex-1 !py-3">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Pen Name' }}</button>
        <a href="{{ route('author.pen-names.index') }}" class="btn-outline !py-3">Batal</a>
    </div>
</form>
