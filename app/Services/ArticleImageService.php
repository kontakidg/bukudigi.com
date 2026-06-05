<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Orkestrasi gambar artikel: generate via KIE (Nano Banana) → resize 16:9 →
 * watermark "© <tahun> Bukudigi.com" pojok kanan bawah (GD) → simpan ke public disk.
 */
class ArticleImageService
{
    private const W = 1200;
    private const H = 675; // 16:9

    public function __construct(private KieImageService $kie) {}

    public function isActive(): bool
    {
        return $this->kie->isActive() && extension_loaded('gd');
    }

    /**
     * Generate + watermark + simpan. Return relative path (public disk) atau null.
     */
    public function generateAndStore(string $prompt, string $slug): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $binary = $this->kie->generateImage($prompt, '16:9');
        if (! $binary) {
            return null;
        }

        $src = @imagecreatefromstring($binary);
        if (! $src) {
            return null;
        }

        // Resize/crop ke 1200x675 (cover)
        $canvas = $this->coverResize($src, self::W, self::H);
        imagedestroy($src);

        // Watermark
        $this->drawWatermark($canvas);

        // Simpan ke public disk
        $rel = 'article-covers/'.Str::random(8).'-'.$slug.'.jpg';
        $abs = Storage::disk('public')->path($rel);
        @mkdir(dirname($abs), 0775, true);

        imagejpeg($canvas, $abs, 88);
        imagedestroy($canvas);

        return $rel;
    }

    /**
     * Builder prompt gambar sesuai gaya yang dipilih admin.
     */
    public function imagePrompt(string $title, string $topic, string $style): string
    {
        $subject = "Blog header image for an Indonesian article titled \"{$title}\" about {$topic}.";

        $styleDesc = match ($style) {
            'foto'      => 'realistic editorial photography, natural lighting, high detail, professional, 16:9 landscape',
            'minimalis' => 'flat minimalist vector design, clean composition, indigo and violet brand palette, soft shapes, plenty of negative space, 16:9 landscape',
            default     => 'modern colorful digital illustration, friendly and warm, editorial style, clean composition, 16:9 landscape', // ilustrasi
        };

        return "{$subject} Style: {$styleDesc}. "
            . "Do NOT include any text, letters, words, watermark, or logo in the image. "
            . "Focus on a clear, appealing visual concept relevant to the topic.";
    }

    /**
     * Resize gambar ke target dgn mode "cover" (crop center).
     */
    private function coverResize($src, int $tw, int $th)
    {
        $sw = imagesx($src);
        $sh = imagesy($src);

        $scale = max($tw / $sw, $th / $sh);
        $nw = (int) ceil($sw * $scale);
        $nh = (int) ceil($sh * $scale);

        $dstX = (int) (($tw - $nw) / 2);
        $dstY = (int) (($th - $nh) / 2);

        $canvas = imagecreatetruecolor($tw, $th);
        imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $nw, $nh, $sw, $sh);

        return $canvas;
    }

    /**
     * Watermark "© <tahun> Bukudigi.com" pojok kanan bawah:
     * pill gelap semi-transparan + teks putih.
     */
    private function drawWatermark($canvas): void
    {
        $text = '© '.date('Y').' Bukudigi.com';
        $font = $this->fontPath();
        $pad  = 14;
        $margin = 20;
        $fontSize = 18;

        if ($font) {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $textW = abs($box[2] - $box[0]);
            $textH = abs($box[7] - $box[1]);

            $boxW = $textW + $pad * 2;
            $boxH = $textH + $pad * 2;
            $x1 = self::W - $boxW - $margin;
            $y1 = self::H - $boxH - $margin;
            $x2 = self::W - $margin;
            $y2 = self::H - $margin;

            // Pill gelap semi-transparan
            $bg = imagecolorallocatealpha($canvas, 0, 0, 0, 70);
            $this->roundedRect($canvas, $x1, $y1, $x2, $y2, 10, $bg);

            // Teks putih
            $white = imagecolorallocate($canvas, 255, 255, 255);
            $tx = $x1 + $pad;
            $ty = $y2 - $pad; // baseline
            imagettftext($canvas, $fontSize, 0, $tx, $ty, $white, $font, $text);
        } else {
            // Fallback built-in font (tanpa TTF)
            $white = imagecolorallocate($canvas, 255, 255, 255);
            $shadow = imagecolorallocate($canvas, 0, 0, 0);
            $tw = imagefontwidth(5) * strlen($text);
            $x = self::W - $tw - $margin;
            $y = self::H - imagefontheight(5) - $margin;
            imagestring($canvas, 5, $x + 1, $y + 1, $text, $shadow);
            imagestring($canvas, 5, $x, $y, $text, $white);
        }
    }

    private function roundedRect($canvas, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($canvas, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($canvas, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($canvas, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($canvas, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($canvas, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($canvas, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    /**
     * Cari TTF font (sama dgn pola OgCardGenerator).
     */
    private function fontPath(): ?string
    {
        $candidates = [
            storage_path('app/fonts/Inter-Bold.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
        ];
        foreach ($candidates as $f) {
            if (is_file($f)) {
                return $f;
            }
        }
        return null;
    }
}
