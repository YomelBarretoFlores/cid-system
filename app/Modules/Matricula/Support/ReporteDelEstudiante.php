<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Personas\Contracts\FichaDelUsuario;
use Carbon\CarbonImmutable;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

// Every student report carries who it belongs to and when it was generated, in Lima.
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
