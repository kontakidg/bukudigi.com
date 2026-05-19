@php
    $isEdit = isset($book) && $book->exists;
    $action = $isEdit ? route('author.books.update', $book) : route('author.books.store');
    $tagsValue = $isEdit ? $book->tags->pluck('name')->implode(', ') : old('tags');
    $aiPlatformsValue = $isEdit ? collect($book->ai_platforms_used ?? [])->implode(', ') : old('ai_platforms');
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        <p class="font-semibold">Mohon perbaiki:</p>
        <ul class="ml-4 mt-1 list-disc">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if($isEdit) @method('PATCH') @endif

    <div class="card p-6">
        <h2 class="text-lg font-bold">Informasi Buku</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Judul</label>
                <input id="title" name="title" type="text" required maxlength="255"
                       value="{{ old('title', $book->title ?? '') }}" class="input">
            </div>
            <div>
                <label for="category_id" class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                <select id="category_id" name="category_id" required class="input">
                    <option value="">Pilih kategori…</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $book->category_id ?? null)==$cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="price" class="mb-1 block text-sm font-medium text-slate-700">Harga (Rp)</label>
                <input id="price" name="price" type="number" min="15000" max="500000" step="1000" required
                       value="{{ old('price', $book->price ?? '') }}" class="input">
                <p class="mt-1 text-xs text-slate-500">Min Rp 15.000 · Max Rp 500.000. Komisi platform 20%.</p>
            </div>
            <div class="md:col-span-2">
                <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                <textarea id="description" name="description" rows="5" required maxlength="5000" class="input">{{ old('description', $book->description ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Max 5000 karakter. Yang menarik untuk pembeli.</p>
            </div>
            <div class="md:col-span-2">
                <label for="table_of_contents" class="mb-1 block text-sm font-medium text-slate-700">Daftar Isi <span class="text-slate-400">(opsional)</span></label>
                <textarea id="table_of_contents" name="table_of_contents" rows="4" maxlength="3000" class="input">{{ old('table_of_contents', $book->table_of_contents ?? '') }}</textarea>
            </div>
            <div>
                <label for="page_count" class="mb-1 block text-sm font-medium text-slate-700">Jumlah Halaman <span class="text-slate-400">(opsional)</span></label>
                <input id="page_count" name="page_count" type="number" min="1" max="5000"
                       value="{{ old('page_count', $book->page_count ?? '') }}" class="input">
            </div>
            <div>
                <label for="tags" class="mb-1 block text-sm font-medium text-slate-700">Tag <span class="text-slate-400">(pisah dengan koma)</span></label>
                <input id="tags" name="tags" type="text" maxlength="255" value="{{ old('tags', $tagsValue) }}"
                       placeholder="excel, tutorial, office" class="input"
                       list="tag-options">
                <datalist id="tag-options">
                    @foreach($tagOptions as $t)<option value="{{ $t }}">@endforeach
                </datalist>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="text-lg font-bold">File</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label for="cover" class="mb-1 block text-sm font-medium text-slate-700">
                    Cover {{ $isEdit ? '(ganti, opsional)' : '' }}
                </label>
                <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" {{ $isEdit ? '' : 'required' }} class="input !py-1.5">
                <p class="mt-1 text-xs text-slate-500">Max 5 MB. JPG/PNG/WebP. Rasio 3:4 recommended (mis. 600x800).</p>
                @if($isEdit && $book->cover_path)
                    <div class="mt-2">
                        <img src="{{ \Illuminate\Support\Str::startsWith($book->cover_path, ['http://', 'https://']) ? $book->cover_path : asset('storage/'.$book->cover_path) }}" alt="cover" class="h-32 rounded border border-slate-200">
                    </div>
                @endif
            </div>
            <div>
                <label for="pdf" class="mb-1 block text-sm font-medium text-slate-700">
                    PDF Master {{ $isEdit ? '(ganti, opsional)' : '' }}
                </label>
                <input id="pdf" name="pdf" type="file" accept="application/pdf" {{ $isEdit ? '' : 'required' }} class="input !py-1.5">
                <p class="mt-1 text-xs text-slate-500">Max 50 MB. File asli — sistem akan inject watermark saat pembeli download.</p>
                @if($isEdit && $book->pdf_master_path)
                    <p class="mt-2 text-xs text-slate-600">📄 File saat ini: <code class="rounded bg-slate-100 px-1.5 py-0.5">{{ basename($book->pdf_master_path) }}</code></p>
                @endif
            </div>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="text-lg font-bold">Disclosure AI</h2>
        <p class="mt-1 text-xs text-slate-500">Wajib dijawab. Berpengaruh ke kepercayaan pembaca, tidak menentukan diapprove atau tidak.</p>
        <div class="mt-4 space-y-3">
            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="ai_disclosure" value="1" @checked(old('ai_disclosure', $book->ai_disclosure ?? false))
                       class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>Saya menggunakan bantuan AI (ChatGPT/Claude/dll) dalam pembuatan buku ini.</span>
            </label>
            <div>
                <label for="ai_platforms" class="mb-1 block text-xs font-medium text-slate-500">Platform AI yang dipakai <span class="text-slate-400">(opsional, pisah koma)</span></label>
                <input id="ai_platforms" name="ai_platforms" type="text" maxlength="255"
                       value="{{ old('ai_platforms', $aiPlatformsValue) }}"
                       placeholder="ChatGPT, Claude, Gemini" class="input">
            </div>
        </div>
    </div>

    <div class="card p-6">
        <label class="flex items-start gap-2 text-sm text-slate-700">
            <input type="checkbox" name="agree_terms" required class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            <span>Saya menyatakan konten buku ini <strong>asli karya saya</strong> atau saya berhak menjualnya secara hukum. Pelanggaran hak cipta = suspend akun + tuntutan hukum.</span>
        </label>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn-primary flex-1 !py-3">
            {{ $isEdit ? 'Simpan Perubahan' : 'Submit untuk Moderasi' }}
        </button>
        <a href="{{ route('author.books.index') }}" class="btn-outline !py-3">Batal</a>
    </div>
</form>
