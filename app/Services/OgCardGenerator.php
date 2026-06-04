<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Storage;

/**
 * Generate Open Graph card image (1200x630 PNG) per buku.
 *
 * Layout:
 *   - Background gradient indigo
 *   - Cover buku di kiri (portrait, 350x525)
 *   - Title + author + price + brand di kanan
 *   - Footer "bukudigi.com"
 *
 * Output disimpan ke public disk: og-cards/{slug}.png
 * Diakses via asset('storage/og-cards/{slug}.png').
 */
class OgCardGenerator
{
    private const W = 1200;
    private const H = 630;

    public function generate(Book $book): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $canvas = imagecreatetruecolor(self::W, self::H);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        // Background gradient soft pastel — violet-50 → indigo-100
        // (lebih kalem, less saturated, premium feel)
        $this->fillGradient($canvas, [245, 243, 255], [224, 231, 255]);

        // Decorative dots top-right (lebih halus)
        $this->drawDotsPattern($canvas);

        // Load + composite cover image
        $coverDrawn = false;
        if ($book->cover_path) {
            $coverPath = $this->resolveCoverPath($book);
            if ($coverPath) {
                $coverDrawn = $this->drawCover($canvas, $coverPath, 80, 52, 350, 525);
            }
        }

        // Placeholder kalau cover ga ada
        if (! $coverDrawn) {
            $this->drawPlaceholderBook($canvas, 80, 52, 350, 525);
        }

        // Right side text content (start x=480)
        $this->drawText($canvas, $book);

        // Save to public disk
        $outRel = 'og-cards/'.$book->slug.'.png';
        $outAbs = Storage::disk('public')->path($outRel);
        @mkdir(dirname($outAbs), 0775, true);

        imagepng($canvas, $outAbs, 6); // compression 6 (good balance)
        imagedestroy($canvas);

        return $outRel;
    }

    private function fillGradient($canvas, array $from, array $to): void
    {
        for ($y = 0; $y < self::H; $y++) {
            $t = $y / self::H;
            $r = (int) ($from[0] * (1 - $t) + $to[0] * $t);
            $g = (int) ($from[1] * (1 - $t) + $to[1] * $t);
            $b = (int) ($from[2] * (1 - $t) + $to[2] * $t);
            $color = imagecolorallocate($canvas, $r, $g, $b);
            imageline($canvas, 0, $y, self::W, $y, $color);
        }
    }

    private function drawDotsPattern($canvas): void
    {
        // Dots indigo soft di pojok (bukan putih, biar terlihat di bg pastel)
        $color = imagecolorallocatealpha($canvas, 99, 102, 241, 100); // indigo-500, alpha low
        for ($x = self::W - 200; $x < self::W; $x += 14) {
            for ($y = 20; $y < 200; $y += 14) {
                imagefilledellipse($canvas, $x, $y, 3, 3, $color);
            }
        }
        // Decorative blob di bottom-left (soft accent)
        $blob = imagecolorallocatealpha($canvas, 165, 180, 252, 100); // indigo-300 soft
        imagefilledellipse($canvas, 50, self::H + 40, 200, 200, $blob);
    }

    private function resolveCoverPath(Book $book): ?string
    {
        if (! $book->cover_path) {
            return null;
        }

        if (str_starts_with($book->cover_path, 'http://') || str_starts_with($book->cover_path, 'https://')) {
            // Download remote URL ke temp
            $tmp = tempnam(sys_get_temp_dir(), 'cover-');
            $data = @file_get_contents($book->cover_path);
            if ($data) {
                file_put_contents($tmp, $data);
                return $tmp;
            }
            return null;
        }

        $local = Storage::disk('public')->path($book->cover_path);
        return is_file($local) ? $local : null;
    }

    private function drawCover($canvas, string $coverPath, int $x, int $y, int $w, int $h): bool
    {
        $ext = strtolower(pathinfo($coverPath, PATHINFO_EXTENSION));
        $source = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($coverPath),
            'png' => @imagecreatefrompng($coverPath),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($coverPath) : false,
            default => false,
        };

        if (! $source) {
            return false;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        // Cover shadow soft indigo (offset 10px right-down)
        $shadow = imagecolorallocatealpha($canvas, 99, 102, 241, 100);
        imagefilledrectangle($canvas, $x + 10, $y + 10, $x + $w + 10, $y + $h + 10, $shadow);

        // Resample to target box, preserve aspect via cover/crop
        $srcRatio = $srcW / $srcH;
        $dstRatio = $w / $h;
        if ($srcRatio > $dstRatio) {
            // Source lebih lebar — crop sides
            $cropW = (int) ($srcH * $dstRatio);
            $cropX = (int) (($srcW - $cropW) / 2);
            imagecopyresampled($canvas, $source, $x, $y, $cropX, 0, $w, $h, $cropW, $srcH);
        } else {
            $cropH = (int) ($srcW / $dstRatio);
            $cropY = (int) (($srcH - $cropH) / 2);
            imagecopyresampled($canvas, $source, $x, $y, 0, $cropY, $w, $h, $srcW, $cropH);
        }

        imagedestroy($source);
        return true;
    }

    private function drawPlaceholderBook($canvas, int $x, int $y, int $w, int $h): void
    {
        $bg = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, $x, $y, $x + $w, $y + $h, $bg);
        // Border
        $border = imagecolorallocate($canvas, 200, 200, 220);
        imagerectangle($canvas, $x, $y, $x + $w, $y + $h, $border);
    }

    private function drawText($canvas, Book $book): void
    {
        $font = $this->fontPath();
        if (! $font || ! is_file($font)) {
            // Fallback ke built-in font (jelek tapi works)
            $this->drawTextBuiltin($canvas, $book);
            return;
        }

        $x = 500;
        // Soft palette — text dark di background pastel
        $titleColor = imagecolorallocate($canvas, 30, 27, 75);        // indigo-950
        $brandColor = imagecolorallocate($canvas, 79, 70, 229);       // indigo-600
        $mutedColor = imagecolorallocate($canvas, 100, 116, 139);     // slate-500
        $priceColor = imagecolorallocate($canvas, 67, 56, 202);       // indigo-700
        $accentBg   = imagecolorallocatealpha($canvas, 79, 70, 229, 115); // soft pill bg

        // Brand badge pill — "📚 bukudigi.com"
        $brandText = '📚 bukudigi.com';
        $brandBox = imagettfbbox(15, 0, $font, $brandText);
        $brandW = $brandBox[2] - $brandBox[0];
        $this->drawRoundedRect($canvas, $x - 8, 60, $x + $brandW + 14, 96, 14, $accentBg);
        imagettftext($canvas, 15, 0, $x, 84, $brandColor, $font, $brandText);

        // Title (max 2 lines, wrap pakai font size 40)
        $titleLines = $this->wrapText($book->title, $font, 40, 620);
        $y = 175;
        foreach (array_slice($titleLines, 0, 2) as $line) {
            imagettftext($canvas, 40, 0, $x, $y, $titleColor, $font, $line);
            $y += 54;
        }

        // Author
        $y += 22;
        $author = 'oleh '.$book->displayAuthor();
        imagettftext($canvas, 21, 0, $x, $y, $mutedColor, $font, mb_substr($author, 0, 60));

        // Separator line
        $y += 30;
        $lineColor = imagecolorallocatealpha($canvas, 79, 70, 229, 110);
        imagerectangle($canvas, $x, $y, $x + 60, $y + 2, $lineColor);
        imagefilledrectangle($canvas, $x, $y, $x + 60, $y + 2, $lineColor);

        // Kategori atau badge "Preview Gratis"
        $y += 42;
        $categoryText = $book->category?->name ? '📂 '.$book->category->name : '📚 Ebook Indonesia';
        imagettftext($canvas, 22, 0, $x, $y, $mutedColor, $font, mb_substr($categoryText, 0, 40));

        // Footer CTA — "⬇ Beli & download instan"
        $cta = '⬇ Beli & download instan di bukudigi.com';
        $box = imagettfbbox(15, 0, $font, $cta);
        $ctaW = $box[2] - $box[0];
        imagettftext($canvas, 15, 0, self::W - $ctaW - 80, self::H - 40, $mutedColor, $font, $cta);
    }

    private function drawTextBuiltin($canvas, Book $book): void
    {
        $dark = imagecolorallocate($canvas, 30, 27, 75);
        $brand = imagecolorallocate($canvas, 79, 70, 229);
        imagestring($canvas, 5, 500, 100, '📚 bukudigi.com', $brand);
        imagestring($canvas, 5, 500, 200, mb_substr($book->title, 0, 40), $dark);
        imagestring($canvas, 4, 500, 260, 'oleh '.$book->displayAuthor(), $dark);
        imagestring($canvas, 4, 500, 360, $book->category?->name ?? 'Ebook Indonesia', $brand);
    }

    /**
     * Helper: draw rounded rectangle (manual karena GD ga punya bawaan).
     */
    private function drawRoundedRect($canvas, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        // Body
        imagefilledrectangle($canvas, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($canvas, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        // 4 corner arcs
        imagefilledellipse($canvas, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($canvas, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($canvas, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($canvas, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    private function wrapText(string $text, string $font, int $fontSize, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($fontSize, 0, $font, $test);
            $w = $box[2] - $box[0];
            if ($w > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $test;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        return $lines;
    }

    private function fontPath(): ?string
    {
        // Coba Inter (kalau ada di server) → fallback ke DejaVu (umum di Linux)
        $candidates = [
            storage_path('app/fonts/Inter-Bold.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            'C:/Windows/Fonts/arialbd.ttf',  // Windows fallback (XAMPP local)
        ];
        foreach ($candidates as $f) {
            if (is_file($f)) {
                return $f;
            }
        }
        return null;
    }
}
