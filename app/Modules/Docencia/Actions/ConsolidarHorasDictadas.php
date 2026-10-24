<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Data\ExpedienteDeHoras;
use App\Modules\Docencia\Data\LineaDeHoras;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class ConsolidarHorasDictadas
{
    // Sorts after any padded cost, so hours without a cost close each language and level.
    private const string SIN_COSTO = 'z';

    public function __construct(
        private HorariosDeGrupos $horariosDeGrupos,
        private FichasDePersonas $fichasDePersonas,
    ) {}

    /**
     * One report per teacher with classes in the month, or the report of the given teacher even without classes.
     *
     * @return list<ExpedienteDeHoras>
     */
    public function __invoke(int $anio, int $mes, ?int $docenteId = null): array
    {
        if ($mes < 1 || $mes > 12 || $anio < 2000 || $anio > 2100) {
            throw new ReglaDeNegocioIncumplida('Elige un mes y un año válidos.');
        }

        $inicio = CarbonImmutable::parse(sprintf('%04d-%02d-01', $anio, $mes));
        $clases = ClaseDictada::query()
            ->whereBetween('fecha', [$inicio->toDateString(), $inicio->endOfMonth()->toDateString()])
            ->when($docenteId !== null, fn ($query) => $query->where('docente_id', $docenteId))
            ->orderBy('fecha')
            ->get();

        $docentes = Docente::query()->whereIntegerInRaw('id', $docenteId === null ? $clases->pluck('docente_id')->unique()->all() : [$docenteId])->get();
        $grupos = ($this->horariosDeGrupos)(array_values(array_map(intval(...), $clases->pluck('grupo_id')->unique()->all())));
        $nombres = ($this->fichasDePersonas)(array_values(array_map(intval(...), $docentes->pluck('persona_id')->all())));

        $expedientes = $docentes->map(fn (Docente $docente): ExpedienteDeHoras => new ExpedienteDeHoras(
            docenteId: $docente->id,
            codigo: $docente->codigo,
            nombre: isset($nombres[$docente->persona_id]) ? $nombres[$docente->persona_id]->nombreCompleto : $docente->codigo,
            categoria: $docente->condicion_laboral->etiqueta(),
            anio: $anio,
            mes: $mes,
            lineas: $this->lineas($clases->where('docente_id', $docente->id), $grupos, $docente),
        ));

        return array_values($expedientes->sortBy(fn (ExpedienteDeHoras $expediente): string => Str::lower(Str::ascii($expediente->nombre)))->all());
    }

    /**
     * @param  Collection<int, ClaseDictada>  $clases
     * @param  array<int, HorarioDeGrupo>  $grupos
     * @return list<LineaDeHoras>
     */
    private function lineas(Collection $clases, array $grupos, Docente $docente): array
    {
        $costos = CostoPorHora::query()->where('condicion_laboral', $docente->condicion_laboral)->get();
        $minutos = [];

        foreach ($clases as $clase) {
            $grupo = $grupos[$clase->grupo_id];
            $costo = $costos->first(fn (CostoPorHora $vigente): bool => $vigente->vigente_desde->lte($clase->fecha) && ($vigente->vigente_hasta === null || $vigente->vigente_hasta->gte($clase->fecha)));
            $clave = implode('|', [$grupo->idioma, $grupo->nivel->orden(), $grupo->nivel->etiqueta(), $costo === null ? self::SIN_COSTO : str_pad((string) $this->centimos($costo->costo_hora), 12, '0', STR_PAD_LEFT)]);
            $minutos[$clave] = ($minutos[$clave] ?? 0) + $this->minutos($clase);
        }

        ksort($minutos);

        return array_map($this->linea(...), array_keys($minutos), array_values($minutos));
    }

    private function linea(string $clave, int $minutos): LineaDeHoras
    {
        [$idioma, , $nivel, $costo] = explode('|', $clave);
        // Hours are rounded once per line, and the amount is computed from those hours so anyone can reproduce it.
        $centesimas = intdiv($minutos * 100 + 30, 60);
        $costoCentimos = $costo === self::SIN_COSTO ? null : (int) $costo;

        return new LineaDeHoras($idioma, $nivel, $centesimas, $costoCentimos, $costoCentimos === null ? null : intdiv($centesimas * $costoCentimos + 50, 100));
    }

    private function minutos(ClaseDictada $clase): int
    {
        return intdiv(strtotime("1970-01-01 {$clase->hora_fin} UTC") - strtotime("1970-01-01 {$clase->hora_inicio} UTC"), 60);
    }

    private function centimos(string $monto): int
    {
        [$soles, $centimos] = explode('.', $monto.'.00');

        return (int) $soles * 100 + (int) str_pad(substr($centimos, 0, 2), 2, '0');
    }
}
