<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Data\ExpedienteDeHoras;
use App\Modules\Docencia\Data\LineaDeHoras;
use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\EstadoDeLaClase;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class ConsolidarHorasDictadas
{
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

        $costos = CostoPorHora::query()->get()->groupBy(fn (CostoPorHora $costo): string => $costo->condicion_laboral->value);

        $expedientes = $docentes->map(fn (Docente $docente): ExpedienteDeHoras => new ExpedienteDeHoras(
            docenteId: $docente->id,
            codigo: $docente->codigo,
            nombre: isset($nombres[$docente->persona_id]) ? $nombres[$docente->persona_id]->nombreCompleto : $docente->codigo,
            categoria: $docente->condicion_laboral->etiqueta(),
            anio: $anio,
            mes: $mes,
            lineas: $this->lineas($clases->where('docente_id', $docente->id), $grupos, $costos->get($docente->condicion_laboral->value, new Collection)),
        ));

        return array_values($expedientes->sortBy(fn (ExpedienteDeHoras $expediente): string => Str::lower(Str::ascii($expediente->nombre)))->all());
    }

    /**
     * @param  Collection<int, ClaseDictada>  $clases
     * @param  array<int, HorarioDeGrupo>  $grupos
     * @param  Collection<int, CostoPorHora>  $costos  costs of the teacher's category
     * @return list<LineaDeHoras>
     */
    private function lineas(Collection $clases, array $grupos, Collection $costos): array
    {
        $lineas = [];
        $ahora = ClasesDelHorario::ahora();

        foreach ($clases as $clase) {
            $grupo = $grupos[$clase->grupo_id];
            $costo = $costos->first(fn (CostoPorHora $vigente): bool => $vigente->vigente_desde->lte($clase->fecha) && ($vigente->vigente_hasta === null || $vigente->vigente_hasta->gte($clase->fecha)));
            $centimos = $costo === null ? null : $this->centimos($costo->costo_hora);
            $clave = "{$grupo->idioma}\0{$grupo->nivel->value}\0".($centimos ?? '-');
            $lineas[$clave] ??= ['idioma' => $grupo->idioma, 'orden' => $grupo->nivel->orden(), 'nivel' => $grupo->nivel->etiqueta(), 'costo' => $centimos, 'minutos' => 0, 'confirmadas' => 0, 'noConfirmadas' => 0];
            $lineas[$clave]['minutos'] += $this->minutos($clase);
            $estado = EstadoDeLaClase::de($clase, $ahora);
            $lineas[$clave]['confirmadas'] += (int) ($estado === EstadoDeClase::Confirmada);
            $lineas[$clave]['noConfirmadas'] += (int) in_array($estado, [EstadoDeClase::Observada, EstadoDeClase::SinConfirmar], true);
        }

        // Language, then curricular level, then cost; hours without a cost close each language and level.
        usort($lineas, fn (array $a, array $b): int => [$a['idioma'], $a['orden'], $a['costo'] ?? PHP_INT_MAX] <=> [$b['idioma'], $b['orden'], $b['costo'] ?? PHP_INT_MAX]);

        return array_map($this->linea(...), $lineas);
    }

    /**
     * @param  array{idioma: string, orden: int, nivel: string, costo: ?int, minutos: int, confirmadas: int, noConfirmadas: int}  $linea
     */
    private function linea(array $linea): LineaDeHoras
    {
        // Hours are rounded once per line, and the amount is computed from those hours so anyone can reproduce it.
        $centesimas = intdiv($linea['minutos'] * 100 + 30, 60);
        $monto = $linea['costo'] === null ? null : intdiv($centesimas * $linea['costo'] + 50, 100);

        return new LineaDeHoras($linea['idioma'], $linea['nivel'], $centesimas, $linea['costo'], $monto, $linea['confirmadas'], $linea['noConfirmadas']);
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
