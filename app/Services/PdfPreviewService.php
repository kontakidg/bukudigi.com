<?php

namespace App\Services;

use setasign\Fpdi\Tcpdf\Fpdi;

class PdfPreviewService
{
    /**
     * Extract N halaman pertama PDF master ke preview file dengan watermark DEMO.
     *
     * @param  string  $sourceAbs    Absolute path master PDF
     * @param  string  $destAbs      Absolute path output preview PDF
     * @param  string  $bookTitle    Untuk metadata + footer
     * @param  int     $maxPages     Default 5
     * @return int                   Jumlah halaman preview yang dihasilkan
     */
    public function generate(string $sourceAbs, string $destAbs, string $bookTitle, int $maxPages = 5): int
    {
        if (! is_file($sourceAbs)) {
            throw new \RuntimeException("Source PDF not found: {$sourceAbs}");
        }

        $destDir = dirname($destAbs);
        if (! is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $pdf = new Fpdi();
        $pdf->SetCreator('bukudigi.com');
        $pdf->SetTitle("Preview: {$bookTitle}");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetMargins(0, 0, 0);

        $totalPages = $pdf->setSourceFile($sourceAbs);
        $extractCount = min($totalPages, $maxPages);

        for ($i = 1; $i <= $extractCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);
            $orient = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orient, [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            // Footer "DEMO PREVIEW"
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(80, 70, 220);
            $pdf->SetAlpha(0.85);
            $pdf->SetXY(0, $size['height'] - 9);
            $pdf->Cell($size['width'], 5, "PREVIEW — bukudigi.com — halaman {$i} dari {$totalPages}", 0, 0, 'C');
            $pdf->SetAlpha(1);

            // Watermark diagonal "DEMO"
            $pdf->StartTransform();
            $pdf->SetAlpha(0.10);
            $pdf->SetFont('helvetica', 'B', 80);
            $pdf->SetTextColor(80, 70, 220);
            $pdf->Rotate(-30, $size['width'] / 2, $size['height'] / 2);
            $pdf->SetXY(0, $size['height'] / 2 - 20);
            $pdf->Cell($size['width'], 40, 'PREVIEW', 0, 0, 'C');
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
        }

        // Kalau master punya lebih dari N halaman, tambah halaman teaser
        if ($totalPages > $maxPages) {
            $pdf->AddPage('P', 'A4');
            $pdf->SetFont('helvetica', 'B', 24);
            $pdf->SetTextColor(80, 70, 220);
            $pdf->Ln(80);
            $pdf->Cell(0, 12, 'Itu Saja Preview-nya', 0, 1, 'C');
            $pdf->Ln(8);
            $pdf->SetFont('helvetica', '', 14);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->Cell(0, 8, "Sisanya — " . ($totalPages - $maxPages) . " halaman lagi.", 0, 1, 'C');
            $pdf->Ln(20);
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->SetTextColor(80, 70, 220);
            $pdf->Cell(0, 10, 'Beli buku ini di bukudigi.com', 0, 1, 'C');
        }

        $pdf->Output($destAbs, 'F');

        return $extractCount;
    }
}
