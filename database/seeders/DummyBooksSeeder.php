<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\DummyPdfGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DummyBooksSeeder extends Seeder
{
    public function run(): void
    {
        $authorsData = [
            ['email' => 'rina.author@bukudigi.test', 'name' => 'Rina Pertiwi',  'display' => 'Rina Pertiwi',      'bio' => 'Penulis fiksi & sastra. Suka kopi & senja.'],
            ['email' => 'budi.author@bukudigi.test', 'name' => 'Budi Hartono',  'display' => 'Budi Hartono, S.E.',  'bio' => 'Dosen ekonomi, penulis buku bisnis untuk UMKM.'],
            ['email' => 'sari.author@bukudigi.test', 'name' => 'Sari Maharani', 'display' => 'Chef Sari Maharani',  'bio' => 'Food blogger 8 tahun. Resep masakan rumahan & catering.'],
        ];

        $authors = [];
        foreach ($authorsData as $a) {
            $user = User::firstOrCreate(
                ['email' => $a['email']],
                [
                    'name' => $a['name'],
                    'role' => 'author',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $authors[$a['email']] = Author::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $a['display'],
                    'bio' => $a['bio'],
                    'status' => 'verified',
                    'verified_at' => now()->subMonths(rand(1, 6)),
                    'bank_name' => 'BCA',
                    'bank_account' => '12345' . rand(10000, 99999),
                    'bank_holder' => $a['name'],
                ]
            );
        }

        $cats = Category::pluck('id', 'slug');

        $books = [
            ['author' => 'budi.author@bukudigi.test', 'cat' => 'bisnis-investasi', 'title' => 'Saham untuk Pemula: 100 Halaman Cukup',
             'desc' => "Buku panduan investasi saham untuk pemula.\n\nMembahas: cara buka rekening efek, analisa fundamental, manajemen risiko.",
             'toc' => "Bab 1: Kenapa Saham?\nBab 2: Buka Rekening Efek\nBab 3: Manajemen Risiko",
             'price' => 75000, 'pages' => 102, 'tags' => ['investasi','saham','pemula','uang'], 'sales' => 42, 'color' => '4f46e5'],
            ['author' => 'budi.author@bukudigi.test', 'cat' => 'bisnis-investasi', 'title' => 'Mindset Founder UMKM Indonesia',
             'desc' => 'Studi kasus 30 founder UMKM Indonesia yang scale dari nol.',
             'price' => 99000, 'pages' => 168, 'tags' => ['umkm','startup','bisnis'], 'sales' => 28, 'color' => '4338ca'],
            ['author' => 'budi.author@bukudigi.test', 'cat' => 'edukasi-akademik', 'title' => 'Belajar Statistik Dasar Tanpa Pusing',
             'desc' => 'Modul statistik dasar dengan latihan soal dan studi kasus realistis.',
             'price' => 55000, 'pages' => 145, 'tags' => ['statistik','kuliah','matematika'], 'sales' => 67, 'color' => '6366f1'],
            ['author' => 'budi.author@bukudigi.test', 'cat' => 'tutorial-skill', 'title' => 'Mahir Excel dalam 30 Hari',
             'desc' => 'Roadmap Excel dari nol sampai dashboard. 30 menit/hari.',
             'toc' => "Hari 1-7: Formula dasar\nHari 8-14: Fungsi advanced\nHari 15-21: PivotTable\nHari 22-30: Dashboard",
             'price' => 65000, 'pages' => 210, 'tags' => ['excel','tutorial','office','skill'], 'sales' => 124, 'color' => '7c3aed'],
            ['author' => 'rina.author@bukudigi.test', 'cat' => 'fiksi-sastra', 'title' => 'Senja di Pelabuhan Kecil',
             'desc' => 'Novel pendek tentang nelayan tua dan janji yang disimpan 40 tahun.',
             'price' => 35000, 'pages' => 124, 'tags' => ['novel','fiksi','sastra-indonesia'], 'sales' => 89, 'color' => 'be185d'],
            ['author' => 'rina.author@bukudigi.test', 'cat' => 'self-help', 'title' => 'Berdamai dengan Diri Sendiri',
             'desc' => '30 esai pendek tentang menerima diri dan menemukan ketenangan.',
             'price' => 45000, 'pages' => 156, 'tags' => ['self-help','mental-health','esai'], 'sales' => 95, 'color' => '0891b2'],
            ['author' => 'sari.author@bukudigi.test', 'cat' => 'resep-kuliner', 'title' => '100 Resep Masakan Rumahan Anti Gagal',
             'desc' => '100 resep masakan Indonesia, di-test minimal 3 kali per resep.',
             'toc' => "Sayur & Sup (25)\nLauk Ayam & Daging (30)\nIkan & Seafood (20)\nTahu Tempe (15)\nSambal (10)",
             'price' => 89000, 'pages' => 240, 'tags' => ['resep','masakan-rumahan','indonesia'], 'sales' => 178, 'color' => 'ea580c'],
            ['author' => 'sari.author@bukudigi.test', 'cat' => 'religi', 'title' => 'Tafsir Ringkas Juz Amma',
             'desc' => 'Tafsir Juz 30 Al-Qur\'an dengan bahasa Indonesia kontemporer.',
             'price' => 49000, 'pages' => 188, 'tags' => ['tafsir','al-quran','islam'], 'sales' => 56, 'color' => '047857'],
        ];

        $pdfGen = app(DummyPdfGenerator::class);

        foreach ($books as $b) {
            $author = $authors[$b['author']] ?? null;
            if (! $author) continue;

            $slug = Str::slug($b['title']);
            $cover = sprintf('https://placehold.co/600x800/%s/ffffff/png?text=%s',
                $b['color'], rawurlencode(Str::limit($b['title'], 40, '')));

            // Real PDF master file
            $pdfRel = "books/{$slug}/master.pdf";
            $pdfAbs = Storage::disk('local')->path($pdfRel);
            if (! is_file($pdfAbs)) {
                $pdfGen->generate($pdfAbs, $b['title'], $author->display_name);
            }
            $fileSize = filesize($pdfAbs) ?: rand(2, 12) * 1024 * 1024;

            $book = Book::updateOrCreate(
                ['slug' => $slug],
                [
                    'author_id' => $author->id,
                    'category_id' => $cats[$b['cat']] ?? null,
                    'title' => $b['title'],
                    'description' => $b['desc'],
                    'table_of_contents' => $b['toc'] ?? null,
                    'cover_path' => $cover,
                    'pdf_master_path' => $pdfRel,
                    'price' => $b['price'],
                    'page_count' => $b['pages'],
                    'file_size_bytes' => $fileSize,
                    'status' => 'active',
                    'ai_disclosure' => false,
                    'submitted_at' => now()->subDays(rand(20, 90)),
                    'approved_at' => now()->subDays(rand(1, 20)),
                    'sales_count' => $b['sales'],
                    'total_revenue' => $b['sales'] * $b['price'],
                ]
            );

            $tagIds = [];
            foreach ($b['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['slug' => Str::slug($tagName)], ['name' => ucwords(str_replace('-', ' ', $tagName))]);
                $tagIds[] = $tag->id;
            }
            $book->tags()->sync($tagIds);
        }
    }
}
