<x-filament-panels::page>
    <form wire:submit="import">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">
                Import Sekarang
            </x-filament::button>

            <x-filament::button
                type="button"
                color="gray"
                icon="heroicon-o-arrow-down-tray"
                wire:click="downloadTemplate">
                Download Template CSV
            </x-filament::button>
        </div>
    </form>

    <x-filament::section class="mt-8">
        <x-slot name="heading">Format CSV</x-slot>
        <x-slot name="description">Kolom yang didukung (header wajib ada "title"):</x-slot>

        <div class="prose prose-sm max-w-none dark:prose-invert">
            <table>
                <thead>
                    <tr><th>Kolom</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>title</code></td><td><strong>Wajib.</strong> Judul artikel.</td></tr>
                    <tr><td><code>excerpt</code></td><td>Ringkasan singkat (untuk kartu & meta).</td></tr>
                    <tr><td><code>content</code></td><td>Isi artikel (boleh HTML).</td></tr>
                    <tr><td><code>category</code></td><td>Kategori teks bebas.</td></tr>
                    <tr><td><code>author_name</code></td><td>Nama penulis. Default "Tim bukudigi.com".</td></tr>
                    <tr><td><code>cover_filename</code></td><td>Nama file gambar di dalam ZIP (mis. <code>cover1.jpg</code>).</td></tr>
                    <tr><td><code>status</code></td><td><code>draft</code> / <code>scheduled</code> / <code>published</code> / <code>archived</code>. Default draft.</td></tr>
                    <tr><td><code>published_at</code></td><td>Waktu terbit (mis. <code>2026-06-05 09:00</code>). Untuk scheduled.</td></tr>
                    <tr><td><code>meta_title</code></td><td>SEO title (opsional).</td></tr>
                    <tr><td><code>meta_description</code></td><td>SEO description (opsional).</td></tr>
                </tbody>
            </table>
            <p class="text-sm text-gray-500">
                💡 Artikel ber-status <code>published</code> akan otomatis dipost ke Facebook Page (jika FB aktif).
                Gambar di ZIP dicocokkan berdasarkan nama file dengan kolom <code>cover_filename</code>.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
