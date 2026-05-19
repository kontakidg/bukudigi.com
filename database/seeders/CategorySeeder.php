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
