<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PdfService
{
    public function generar(string $vista, array $datos, string $orientacion = 'portrait'): string
    {
        $pdf = Pdf::loadView($vista, $datos)
            ->setPaper('letter', $orientacion)
            ->setOption('isFontSubsettingEnabled', true);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $lienzo = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $lienzo->page_text($lienzo->get_width() - 98, $lienzo->get_height() - 38, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 6.5, [0.42, 0.49, 0.47]);

        return $dompdf->output();
    }

    public function descargar(string $vista, array $datos, string $archivo, string $orientacion = 'portrait'): Response
    {
        return response($this->generar($vista, $datos, $orientacion), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$archivo.'"',
        ]);
    }
}
