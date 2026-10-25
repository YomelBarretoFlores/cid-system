<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\Tablero;
use App\Modules\Tablero\Support\PeriodoConsultado;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

// Every indicator is computed on each request from the modules' own tables: the dashboard shows the data as it is now.
final readonly class ArmarTablero
{
    public function __construct(
        private CalcularRecaudacion $recaudacion,
        private CalcularPagosPorValidar $pagosPorValidar,
        private CalcularRentabilidadPorGrupo $rentabilidad,
        private CalcularMatriculas $matriculas,
        private CalcularDesercion $desercion,
        private CalcularDemandaHoraria $demandaHoraria,
    ) {}

    public function __invoke(FiltrosDelTablero $filtros): Tablero
    {
        return new Tablero(
            filtros: $filtros,
            periodo: PeriodoConsultado::de($filtros->periodoId)->nombre,
            idioma: $this->idioma($filtros->idiomaId),
            nivel: $filtros->nivel?->etiqueta() ?? 'Todos los niveles',
            recaudacion: ($this->recaudacion)($filtros),
            pagosPorValidar: ($this->pagosPorValidar)($filtros),
            rentabilidad: ($this->rentabilidad)($filtros),
            matriculas: ($this->matriculas)($filtros),
            desercion: ($this->desercion)($filtros),
            demandaHoraria: ($this->demandaHoraria)($filtros),
        );
    }

    private function idioma(?int $idiomaId): string
    {
        if ($idiomaId === null) {
            return 'Todos los idiomas';
        }

        $nombre = DB::table('aca_idiomas')->where('id', $idiomaId)->value('nombre');

        return is_string($nombre) ? $nombre : throw new ReglaDeNegocioIncumplida('Elige un idioma válido.');
    }
}
