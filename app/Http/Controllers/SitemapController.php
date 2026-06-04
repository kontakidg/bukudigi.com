<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        // Homepage + main hubs
        $urls[] = ['loc' => route('home'),            'priority' => '1.0', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('books.index'),     'priority' => '0.9', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('kategori.index'),  'priority' => '0.8', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => route('blog.index'),      'priority' => '0.7', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('jual'),            'priority' => '0.7', 'changefreq' => 'monthly'];
        $urls[] = ['loc' => route('affiliate.landing'), 'priority' => '0.6', 'changefreq' => 'monthly'];

        // Categories
        foreach (Category::where('is_active', true)->whereNull('parent_id')->get() as $cat) {
            $urls[] = [
                'loc' => route('kategori.show', $cat),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $cat->updated_at?->toIso8601String(),
            ];
        }

        // Books (active only) — pakai image sitemap extension untuk Google Image SEO
        foreach (Book::active()->select('id', 'slug', 'title', 'cover_path', 'updated_at', 'approved_at')->get() as $book) {
            $coverUrl = null;
            if ($book->cover_path) {
                $coverUrl = Str::startsWith($book->cover_path, ['http://', 'https://'])
                    ? $book->cover_path
                    : asset('storage/'.$book->cover_path);
            }

            $urls[] = [
                'loc' => route('books.show', $book->slug),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => ($book->updated_at ?? $book->approved_at)?->toIso8601String(),
                'image' => $coverUrl ? [
                    'loc' => $coverUrl,
                    'title' => $book->title,
                ] : null,
            ];
        }

        // Blog articles (published only)
        foreach (Article::published()->get(['id', 'slug', 'title', 'cover_path', 'updated_at', 'published_at']) as $article) {
            $coverUrl = null;
            if ($article->cover_path) {
                $coverUrl = Str::startsWith($article->cover_path, ['http://', 'https://'])
                    ? $article->cover_path
                    : asset('storage/'.$article->cover_path);
            }

            $urls[] = [
                'loc' => route('blog.show', $article->slug),
                'priority' => '0.6',
                'changefreq' => 'weekly',
                'lastmod' => ($article->updated_at ?? $article->published_at)?->toIso8601String(),
                'image' => $coverUrl ? [
                    'loc' => $coverUrl,
                    'title' => $article->title,
                ] : null,
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
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>".htmlspecialchars($u['loc'], ENT_XML1)."</loc>\n";
            if (! empty($u['lastmod'])) {
                $xml .= "    <lastmod>".$u['lastmod']."</lastmod>\n";
            }
            $xml .= "    <changefreq>".$u['changefreq']."</changefreq>\n";
            $xml .= "    <priority>".$u['priority']."</priority>\n";
            if (! empty($u['image'])) {
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>".htmlspecialchars($u['image']['loc'], ENT_XML1)."</image:loc>\n";
                $xml .= "      <image:title>".htmlspecialchars($u['image']['title'], ENT_XML1)."</image:title>\n";
                $xml .= "    </image:image>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
