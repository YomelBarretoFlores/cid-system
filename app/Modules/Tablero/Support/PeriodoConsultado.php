<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class PeriodoConsultado
{
    private function __construct(
        public int $id,
        public string $nombre,
        public int $anio,
        public int $numero,
        public string $fechaInicio,
        public string $fechaFin,
    ) {}

    public static function de(int $periodoId): self
    {
        $fila = DB::table('aca_periodos')->where('id', $periodoId)->first(['id', 'nombre', 'anio', 'numero', 'fecha_inicio', 'fecha_fin']);

        return $fila === null
            ? throw new ReglaDeNegocioIncumplida('Elige un periodo válido.')
            : new self((int) $fila->id, (string) $fila->nombre, (int) $fila->anio, (int) $fila->numero, (string) $fila->fecha_inicio, (string) $fila->fecha_fin);
    }
}
