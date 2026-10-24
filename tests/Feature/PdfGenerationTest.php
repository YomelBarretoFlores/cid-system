<?php

declare(strict_types=1);

use Spatie\LaravelPdf\Facades\Pdf;

it('renders PDFs with the DOMPDF driver', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'ciln-pdf-').'.pdf';

    Pdf::html('<h1>CILN</h1>')->save($path);

    expect(config('laravel-pdf.driver'))->toBe('dompdf')
        ->and(file_get_contents($path))->toStartWith('%PDF');

    unlink($path);
});
