<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        // Homepage + main hubs
        $urls[] = ['loc' => route('home'),            'priority' => '1.0', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('books.index'),     'priority' => '0.9', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('kategori.index'),  'priority' => '0.8', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => route('jual'),            'priority' => '0.7', 'changefreq' => 'monthly'];

        // Categories
        foreach (Category::where('is_active', true)->whereNull('parent_id')->get() as $cat) {
            $urls[] = [
                'loc' => route('kategori.show', $cat),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $cat->updated_at?->toIso8601String(),
            ];
        }

        // Books (active only)
        foreach (Book::active()->select('slug', 'updated_at', 'approved_at')->get() as $book) {
            $urls[] = [
                'loc' => route('books.show', $book->slug),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => ($book->updated_at ?? $book->approved_at)?->toIso8601String(),
            ];
        }

        // Info pages
        foreach ([
            'info.tentang', 'info.bantuan', 'info.syarat',
            'info.privasi', 'info.komisi', 'info.panduan-penulis', 'info.faq',
        ] as $name) {
            $urls[] = ['loc' => route($name), 'priority' => '0.4', 'changefreq' => 'monthly'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>".htmlspecialchars($u['loc'], ENT_XML1)."</loc>\n";
            if (! empty($u['lastmod'])) {
                $xml .= "    <lastmod>".$u['lastmod']."</lastmod>\n";
            }
            $xml .= "    <changefreq>".$u['changefreq']."</changefreq>\n";
            $xml .= "    <priority>".$u['priority']."</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
