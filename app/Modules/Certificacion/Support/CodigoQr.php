<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

// PNG rather than SVG because DOMPDF draws embedded PNG images reliably.
final class CodigoQr
{
    public static function comoDataUri(string $contenido): string
    {
        return (new Builder(
            writer: new PngWriter,
            data: $contenido,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 240,
            margin: 0,
        ))->build()->getDataUri();
    }
}
