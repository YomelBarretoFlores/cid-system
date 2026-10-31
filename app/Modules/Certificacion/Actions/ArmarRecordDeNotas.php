<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Certificacion\Data\CicloDelRecord;
use App\Modules\Certificacion\Data\FaltantesDelNivel;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Data\RecordDelIdioma;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Support\HistorialDeNotas;

// One row per cycle: its last passed result, or its last result when it was never passed.
final readonly class ArmarRecordDeNotas implements RecordDeNotas
{
    public function __invoke(int $personaId): array
    {
        return array_values(array_map(
            fn (string $idioma): RecordDelIdioma => $this->record($idioma, HistorialDeNotas::de($personaId, $idioma)),
            HistorialDeNotas::idiomasDe($personaId),
        ));
    }

    /**
     * @param  list<NotaDelHistorial>  $historial
     */
    private function record(string $idioma, array $historial): RecordDelIdioma
    {
        $intentos = [];

        foreach ($historial as $nota) {
            $intentos[$nota->cicloNumero][] = $nota;
        }

        $ciclos = array_values(array_map(fn (array $delCiclo): CicloDelRecord => new CicloDelRecord($this->resultado($delCiclo), count($delCiclo)), $intentos));
        $aprobados = array_values(array_filter(
            array_map(fn (CicloDelRecord $ciclo): NotaDelHistorial => $ciclo->resultado, $ciclos),
            fn (NotaDelHistorial $nota): bool => $nota->estadoFinal === EstadoFinal::Aprobado,
        ));

        return new RecordDelIdioma($idioma, $ciclos, $aprobados === [] ? null : HistorialDeNotas::notaFinal($aprobados), $this->faltantes($historial, $aprobados));
    }

    /**
     * @param  non-empty-list<NotaDelHistorial>  $intentos
     */
    private function resultado(array $intentos): NotaDelHistorial
    {
        $aprobados = array_values(array_filter($intentos, fn (NotaDelHistorial $nota): bool => $nota->estadoFinal === EstadoFinal::Aprobado));

        return $aprobados === [] ? $intentos[array_key_last($intentos)] : $aprobados[array_key_last($aprobados)];
    }

    /**
     * @param  list<NotaDelHistorial>  $historial
     * @param  list<NotaDelHistorial>  $aprobados
     * @return list<FaltantesDelNivel>
     */
    private function faltantes(array $historial, array $aprobados): array
    {
        $niveles = collect($historial)
            ->map(fn (NotaDelHistorial $nota): NivelCurricular => $nota->nivel)
            ->unique(fn (NivelCurricular $nivel): string => $nivel->value)
            ->sortBy(fn (NivelCurricular $nivel): int => $nivel->orden());
        $cursados = array_map(fn (NotaDelHistorial $nota): int => $nota->cicloNumero, $aprobados);

        return array_values($niveles->map(fn (NivelCurricular $nivel): FaltantesDelNivel => new FaltantesDelNivel(
            $nivel->etiqueta(),
            array_values(array_diff(HistorialDeNotas::ciclosDelNivel($nivel), $cursados)),
        ))->all());
    }
}
