<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateOgImage extends Command
{
    protected $signature = 'og:generate {--output=public/og-default.png}';
    protected $description = 'Generate og-default.png 1200x630 dengan logo + tagline (untuk OG social share fallback)';

    public function handle(): int
    {
        $W = 1200;
        $H = 630;
        $outPath = base_path($this->option('output'));

        if (! function_exists('imagecreatetruecolor')) {
            $this->error('PHP GD extension tidak tersedia.');
            return self::FAILURE;
        }

        $im = imagecreatetruecolor($W, $H);
        imageantialias($im, true);

        // === Background: gradient indigo brand-700 → brand-800 ===
        $c1 = [0x43, 0x38, 0xca]; // brand-700
        $c2 = [0x31, 0x2e, 0x81]; // brand-900
        for ($y = 0; $y < $H; $y++) {
            $t = $y / $H;
            $r = (int) ($c1[0] * (1 - $t) + $c2[0] * $t);
            $g = (int) ($c1[1] * (1 - $t) + $c2[1] * $t);
            $b = (int) ($c1[2] * (1 - $t) + $c2[2] * $t);
            imageline($im, 0, $y, $W - 1, $y, imagecolorallocate($im, $r, $g, $b));
        }

        // === Dot pattern overlay (decorative) ===
        $dotColor = imagecolorallocatealpha($im, 255, 255, 255, 110);
        for ($y = 30; $y < $H; $y += 28) {
            for ($x = 30; $x < $W; $x += 28) {
                imagefilledellipse($im, $x, $y, 2, 2, $dotColor);
            }
        }

        // === Logo (load PNG, scale, composite) ===
        $logoPath = public_path('logo.png');
        if (is_file($logoPath)) {
            // Suppress libpng sRGB profile warning yang dilempar Laravel sebagai exception
            $logo = @imagecreatefrompng($logoPath);
            if (! $logo) {
                $this->warn('Gagal load logo.png, skip composite.');
                $logo = null;
            }
        }
        if (! empty($logo)) {
            imagealphablending($logo, true);
            imagesavealpha($logo, true);

            $lw = imagesx($logo);
            $lh = imagesy($logo);

            // Target logo width 600px (mendekati 50% canvas)
            $targetW = 600;
            $targetH = (int) ($targetW * $lh / $lw);

            // White rounded rectangle behind logo (semi opaque) supaya logo dual-tone tetap terbaca
            $padX = 40;
            $padY = 30;
            $bgW = $targetW + $padX * 2;
            $bgH = $targetH + $padY * 2;
            $bgX = (int) (($W - $bgW) / 2);
            $bgY = 100;

            $white = imagecolorallocatealpha($im, 255, 255, 255, 18);
            $this->drawRoundedRect($im, $bgX, $bgY, $bgX + $bgW, $bgY + $bgH, 24, $white);

            // Composite logo with resampling
            imagecopyresampled(
                $im, $logo,
                $bgX + $padX, $bgY + $padY,
                0, 0,
                $targetW, $targetH,
                $lw, $lh
            );
            imagedestroy($logo);
        } elseif (! is_file($logoPath)) {
            $this->warn('logo.png tidak ditemukan, skip composite logo.');
        }

        // === Text: tagline besar ===
        $fontPath = $this->findFontPath();
        $taglineColor = imagecolorallocate($im, 255, 255, 255);
        $subColor = imagecolorallocatealpha($im, 255, 255, 255, 50);

        $tagline = 'Marketplace Ebook PDF';
        $sub = 'dari Penulis Indonesia';
        $details = 'Bayar QRIS  ·  Komisi adil 80%  ·  Watermark personal';

        if ($fontPath) {
            // Tagline 1
            $size = 44;
            $box = imagettfbbox($size, 0, $fontPath, $tagline);
            $tw = $box[2] - $box[0];
            imagettftext($im, $size, 0, (int) (($W - $tw) / 2), 380, $taglineColor, $fontPath, $tagline);

            // Tagline 2
            $size2 = 44;
            $box2 = imagettfbbox($size2, 0, $fontPath, $sub);
            $tw2 = $box2[2] - $box2[0];
            imagettftext($im, $size2, 0, (int) (($W - $tw2) / 2), 444, $taglineColor, $fontPath, $sub);

            // Details
            $size3 = 22;
            $box3 = imagettfbbox($size3, 0, $fontPath, $details);
            $tw3 = $box3[2] - $box3[0];
            imagettftext($im, $size3, 0, (int) (($W - $tw3) / 2), 530, $subColor, $fontPath, $details);

            // Domain footer
            $domain = 'bukudigi.com';
            $size4 = 18;
            $box4 = imagettfbbox($size4, 0, $fontPath, $domain);
            $tw4 = $box4[2] - $box4[0];
            imagettftext($im, $size4, 0, (int) (($W - $tw4) / 2), 590, $subColor, $fontPath, $domain);
        } else {
            // Fallback: imagestring (built-in bitmap font, kecil)
            $this->warn('TTF font tidak ditemukan, pakai built-in font (hasil kurang bagus).');
            imagestring($im, 5, (int) (($W - strlen($tagline) * 9) / 2), 380, $tagline, $taglineColor);
            imagestring($im, 5, (int) (($W - strlen($sub) * 9) / 2), 410, $sub, $taglineColor);
            imagestring($im, 3, (int) (($W - strlen($details) * 7) / 2), 460, $details, $subColor);
        }

        imagepng($im, $outPath, 6);
        imagedestroy($im);

        $size = filesize($outPath);
        $this->info(sprintf('✓ %s — %d × %d, %s KB', $outPath, $W, $H, number_format($size / 1024, 1)));
        return self::SUCCESS;
    }

    private function drawRoundedRect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    private function findFontPath(): ?string
    {
        // Windows fonts (XAMPP dev)
        $win = [
            'C:\\Windows\\Fonts\\segoeuib.ttf',  // Segoe UI Bold
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\verdana.ttf',
        ];
        // Linux fonts (production VPS)
        $linux = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
        ];

        foreach (PHP_OS_FAMILY === 'Windows' ? $win : $linux as $p) {
            if (is_file($p)) {
                return $p;
            }
        }
        return null;
    }
}
