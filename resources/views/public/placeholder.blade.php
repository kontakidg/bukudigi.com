@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-3xl px-4 py-20 text-center">
    <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-3xl">🚧</span>
    <h1 class="mt-4 text-2xl font-bold md:text-3xl">Halaman dalam pembangunan</h1>
    <p class="mt-2 text-slate-600">Halaman <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm">{{ $slug }}</code> belum tersedia. Akan dibangun di iterasi berikutnya.</p>
    <a href="{{ route('home') }}" class="btn-primary mt-6">← Kembali ke beranda</a>
</section>
@endsection
