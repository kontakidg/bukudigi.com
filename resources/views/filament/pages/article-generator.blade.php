<x-filament-panels::page>

    {{-- Banner peringatan API key --}}
    @if(! $anthropicActive || ! $kieActive)
        <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
            ⚠️ Konfigurasi API belum lengkap:
            <ul class="mt-1 list-disc pl-5">
                @if(! $anthropicActive)<li><strong>ANTHROPIC_API_KEY</strong> belum diisi — teks artikel tidak bisa di-generate.</li>@endif
                @if(! $kieActive)<li><strong>KIE_API_KEY</strong> belum diisi — gambar tidak akan dibuat (artikel tetap jalan tanpa cover).</li>@endif
            </ul>
            Isi di <code>.env</code> lalu jalankan <code>php artisan optimize:clear</code>.
        </div>
    @endif

    @if($step === 1)
        {{-- STEP 1: Input --}}
        <form wire:submit="previewTitles">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled" wire:target="previewTitles">
                    <span wire:loading.remove wire:target="previewTitles">Preview Judul</span>
                    <span wire:loading wire:target="previewTitles">Membuat judul…</span>
                </x-filament::button>
                <span class="ml-3 text-xs text-gray-500">Claude akan membuat preview judul dulu — kamu setujui sebelum generate penuh.</span>
            </div>
        </form>
    @else
        {{-- STEP 2: Preview judul --}}
        <x-filament::section>
            <x-slot name="heading">Preview Judul ({{ count($titles) }})</x-slot>
            <x-slot name="description">Edit atau hapus judul sebelum generate. Artikel akan dibuat + dijadwalkan sesuai jeda yang kamu set.</x-slot>

            <div class="space-y-2">
                @foreach($titles as $i => $title)
                    <div class="flex items-center gap-2" wire:key="title-{{ $i }}">
                        <span class="w-6 text-right text-xs text-gray-400">{{ $i + 1 }}.</span>
                        <input type="text" wire:model="titles.{{ $i }}"
                               class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600">
                        <button type="button" wire:click="$set('titles.{{ $i }}', '')"
                                class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600 dark:hover:bg-gray-700" title="Kosongkan">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-filament::button color="success" icon="heroicon-o-rocket-launch" wire:click="generate"
                                    wire:loading.attr="disabled" wire:target="generate">
                    <span wire:loading.remove wire:target="generate">Generate &amp; Jadwalkan</span>
                    <span wire:loading wire:target="generate">Mengantrekan…</span>
                </x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-arrow-left" wire:click="backToInput">
                    Ulang
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section class="mt-4" collapsible collapsed>
            <x-slot name="heading">Bagaimana alurnya?</x-slot>
            <ol class="list-decimal pl-5 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <li>Setiap judul dibuat jadi 1 artikel berstatus <em>draft</em> + diantrekan ke job AI.</li>
                <li>Job menulis isi artikel (Claude) + membuat gambar (Nano Banana) ber-watermark <code>© {{ date('Y') }} Bukudigi.com</code>.</li>
                <li>Artikel ke-1 terbit di waktu "Mulai Dari"; berikutnya tiap +jeda jam.</li>
                <li>Saat terbit, artikel otomatis dipost ke Facebook Page (lengkap gambar).</li>
                <li>Pantau kolom <strong>AI</strong> di menu Artikel (Antri → Proses → Selesai).</li>
            </ol>
        </x-filament::section>
    @endif

</x-filament-panels::page>
