<?php

namespace App\Services;

use TCPDF;

class DummyPdfGenerator
{
    /**
     * Generate dummy PDF sample (5 halaman) untuk testing.
     */
    public function generate(string $destPath, string $bookTitle, string $authorName): string
    {
        $destDir = dirname($destPath);
        if (! is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('bukudigi.com');
        $pdf->SetAuthor($authorName);
        $pdf->SetTitle($bookTitle);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(20, 25, 20);
        $pdf->SetAutoPageBreak(true, 25);

        // Cover page
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->Ln(60);
        $pdf->MultiCell(0, 12, $bookTitle, 0, 'C');
        $pdf->Ln(8);
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 8, 'oleh ' . $authorName, 0, 1, 'C');
        $pdf->Ln(80);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 8, '— SAMPLE DOCUMENT FOR DEMO PURPOSES —', 0, 1, 'C');
        $pdf->Cell(0, 6, 'bukudigi.com', 0, 1, 'C');

        // Content pages (4 halaman lorem ipsum)
        $chapters = [
            'Pengantar' => "Selamat datang di buku \"{$bookTitle}\". Buku ini adalah dokumen contoh yang dibuat untuk demonstrasi platform bukudigi.com.\n\nKonten asli akan diisi oleh penulis melalui dashboard penulis. Setiap pembeli akan mendapat versi PDF yang sudah ditambahkan watermark personal di setiap halaman.",
            'Bab 1: Pendahuluan' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.\n\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n\nSed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.",
            'Bab 2: Konsep Dasar' => "Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.\n\nAt vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.\n\nTemporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae.",
            'Penutup' => "Demikian buku sample ini. Sekali lagi, konten ini hanya untuk demonstrasi.\n\nPenulis sebenarnya akan menggantikan konten ini dengan karya asli mereka. bukudigi.com hanya menyediakan platform marketplace, kurasi, dan watermarking — bukan menulis kontennya.\n\nTerima kasih telah mendukung penulis lokal Indonesia dengan membeli ebook dari bukudigi.com.",
        ];

        foreach ($chapters as $title => $body) {
            $pdf->AddPage();
            $pdf->SetTextColor(40, 40, 40);
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->Cell(0, 10, $title, 0, 1);
            $pdf->Ln(4);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->MultiCell(0, 6, $body, 0, 'J');
        }

        $pdf->Output($destPath, 'F');

        return $destPath;
    }
}
