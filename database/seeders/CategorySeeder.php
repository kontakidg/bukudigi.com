<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Bisnis & Investasi', 'icon' => 'briefcase', 'sort_order' => 1],
            ['name' => 'Edukasi & Akademik', 'icon' => 'academic-cap', 'sort_order' => 2],
            ['name' => 'Tutorial & Skill', 'icon' => 'sparkles', 'sort_order' => 3],
            ['name' => 'Fiksi & Sastra', 'icon' => 'book-open', 'sort_order' => 4],
            ['name' => 'Resep & Kuliner', 'icon' => 'cake', 'sort_order' => 5],
            ['name' => 'Kesehatan', 'icon' => 'heart', 'sort_order' => 6],
            ['name' => 'Religi', 'icon' => 'sun', 'sort_order' => 7],
            ['name' => 'Self-Help', 'icon' => 'light-bulb', 'sort_order' => 8],
            ['name' => 'Anak', 'icon' => 'face-smile', 'sort_order' => 9],
            ['name' => 'hiburan', 'icon' => 'film', 'sort_order' => 10],
            ['name' => 'Sejarah', 'icon' => 'building-library', 'sort_order' => 11],
            ['name' => 'Teknologi & IT', 'icon' => 'computer-desktop', 'sort_order' => 12],
            ['name' => 'Seni & Desain', 'icon' => 'paint-brush', 'sort_order' => 13],
            ['name' => 'Olahraga & Fitness', 'icon' => 'bolt', 'sort_order' => 14],
            ['name' => 'Travel & Wisata', 'icon' => 'paper-airplane', 'sort_order' => 15],
            ['name' => 'Parenting', 'icon' => 'users', 'sort_order' => 16],
            ['name' => 'Keuangan Pribadi', 'icon' => 'banknotes', 'sort_order' => 17],
            ['name' => 'Marketing & Media Sosial', 'icon' => 'megaphone', 'sort_order' => 18],
            ['name' => 'Penulisan & Jurnalistik', 'icon' => 'pencil', 'sort_order' => 19],
            ['name' => 'Non-fiksi & Sains', 'icon' => 'beaker', 'sort_order' => 20],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'icon' => $data['icon'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                    'description' => 'Kategori ebook ' . $data['name'] . ' — kumpulan buku digital dari penulis Indonesia.',
                ]
            );
        }
    }
}
