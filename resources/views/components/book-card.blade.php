@props(['book'])

<a href="{{ route('books.show', $book) }}" class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-brand-500 hover:shadow-md">
    <div class="relative aspect-[3/4] overflow-hidden bg-slate-100">
        @if($book->cover_path)
            <img src="{{ \Illuminate\Support\Str::startsWith($book->cover_path, ['http://', 'https://']) ? $book->cover_path : asset('storage/'.$book->cover_path) }}"
                 alt="{{ $book->title }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-100 to-brand-300 text-brand-700">
                <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
            </div>
        @endif
        @if($book->category)
            <span class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-slate-700 shadow">
                {{ $book->category->name }}
            </span>
        @endif
        @if($book->epub_master_path)
            <span class="absolute right-2 top-2 rounded-full bg-indigo-600/95 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow" title="Tersedia format PDF + EPUB">
                📖 EPUB
            </span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-3">
        <h3 class="line-clamp-2 text-sm font-semibold text-slate-900 group-hover:text-brand-600">
            {{ $book->title }}
        </h3>
        <p class="mt-1 text-xs text-slate-500">{{ $book->displayAuthor() }}</p>
        <div class="mt-auto pt-3">
            <span class="text-base font-bold text-brand-600">{{ $book->formattedPrice() }}</span>
        </div>
    </div>
</a>
