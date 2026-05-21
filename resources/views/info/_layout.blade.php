{{--
    Shared header untuk semua info pages.
    Usage: @include('info._layout', ['title' => '...', 'lead' => '...', 'updated' => '2026-05-20'])
--}}
<section class="bg-gradient-to-br from-brand-700 via-brand-600 to-brand-800 py-12 text-white">
    <div class="mx-auto max-w-4xl px-4">
        <nav class="mb-3 text-xs text-brand-100">
            <a href="{{ route('home') }}" class="hover:text-white">Beranda</a>
            <span class="mx-1">/</span>
            <span class="text-white">{{ $title }}</span>
        </nav>
        <h1 class="text-3xl font-bold leading-tight md:text-4xl">{{ $title }}</h1>
        @if(!empty($lead))
            <p class="mt-3 max-w-2xl text-brand-100">{{ $lead }}</p>
        @endif
        @if(!empty($updated))
            <p class="mt-4 text-xs text-brand-200">Terakhir diperbarui: {{ $updated }}</p>
        @endif
    </div>
</section>
