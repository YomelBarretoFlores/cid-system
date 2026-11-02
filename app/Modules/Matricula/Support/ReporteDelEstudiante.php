<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Certificacion\Contracts\DenominacionDelAnio;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Carbon\CarbonImmutable;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

// Every student report carries who it belongs to, when it was generated (in Lima) and the denomination of the current year.
final class ReporteDelEstudiante
{
    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public static function datos(array $datos): array
    {
        return [
            'estudiante' => app(FichaDelUsuario::class)((int) auth()->id()) ?? abort(403),
            'generadoEl' => CarbonImmutable::now('America/Lima')->format('d/m/Y H:i'),
            'denominacion' => app(DenominacionDelAnio::class)((int) CarbonImmutable::now('America/Lima')->format('Y')),
            ...$datos,
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function pdf(string $vista, string $archivo, array $datos): PdfBuilder
    {
        return Pdf::view("matricula::pdf.reportes.{$vista}", self::datos($datos))->format('a4')->inline("{$archivo}.pdf");
    }
}
