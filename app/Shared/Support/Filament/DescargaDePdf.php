<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Livewire only turns file or streamed responses into a browser download; a PdfBuilder renders to a plain response it drops.
final class DescargaDePdf
{
    public static function desde(PdfBuilder $pdf): StreamedResponse
    {
        $contenido = (string) $pdf->toResponse(request())->getContent();

        return response()->streamDownload(function () use ($contenido): void {
            echo $contenido;
        }, $pdf->downloadName, ['Content-Type' => 'application/pdf']);
    }
}
