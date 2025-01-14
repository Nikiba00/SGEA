<?php

namespace App\Services;

use TCPDF;

class TCPDFService
{
    public function createPDF(string $title = 'Reporte', string $author = 'SGEA'): TCPDF
    {
        $pdf = new TCPDF();

        // Configuración básica del PDF
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($author);
        $pdf->SetTitle($title);
        $pdf->SetSubject('Reporte generado');
        $pdf->SetKeywords('Reporte, PDF, Laravel, TCPDF');

        // Márgenes
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        // Configurar fuente y otras opciones
        $pdf->SetFont('helvetica', '', 12);

        return $pdf;
    }
}