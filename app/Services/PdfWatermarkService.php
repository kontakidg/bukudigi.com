<?php

namespace App\Services;

use setasign\Fpdi\Tcpdf\Fpdi;

class PdfWatermarkService
{
    /**
     * Inject footer watermark di setiap halaman PDF.
     *
     * @param  string  $sourcePath  Absolute path ke master PDF
     * @param  string  $destPath    Absolute path output (akan ditimpa)
     * @param  string  $watermark   Teks watermark, mis "Dibeli oleh: Nama – email – Order BD-XXX"
     */
    public function apply(string $sourcePath, string $destPath, string $watermark): string
    {
        if (! is_file($sourcePath)) {
            throw new \RuntimeException("Source PDF not found: {$sourcePath}");
        }

        $destDir = dirname($destPath);
        if (! is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($sourcePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            // Watermark di footer center, abu-abu transparan
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetAlpha(0.55);

            $y = $size['height'] - 8; // 8mm dari bawah
            $pdf->SetXY(0, $y);
            $pdf->Cell($size['width'], 5, $watermark, 0, 0, 'C');

            $pdf->SetAlpha(1);

            // Watermark diagonal samar di tengah halaman (anti-bocor sosial)
            $pdf->StartTransform();
            $pdf->SetAlpha(0.06);
            $pdf->SetFont('helvetica', 'B', 38);
            $pdf->SetTextColor(80, 70, 220);
            $pdf->Rotate(-30, $size['width'] / 2, $size['height'] / 2);
            $pdf->SetXY(0, $size['height'] / 2 - 10);
            $pdf->Cell($size['width'], 20, $watermark, 0, 0, 'C');
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
        }

        $pdf->Output($destPath, 'F');

        return $destPath;
    }
}
