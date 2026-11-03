<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Contracts\CursosDelPeriodo;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use Carbon\CarbonImmutable;

/**
 * @phpstan-type ActaPendiente array{grupo_id: int, codigo: string, idioma: string, ciclo: string, docente: ?string, ventana: ?string, estado_grupo: string, estado_acta: string}
 */
final readonly class ListarActasPendientes
{
    public function __construct(private CursosDelPeriodo $cursosDelPeriodo, private DocentesDeGrupos $docentesDeGrupos) {}

    /**
     * Groups that already finished their classes, or are open inside the acta window, and still have no closed acta (decision #55).
     *
     * @return list<ActaPendiente>
     */
    public function __invoke(int $periodoId): array
    {
        $ahora = CarbonImmutable::now();
        $cursos = array_values(array_filter(($this->cursosDelPeriodo)($periodoId), fn (DatosDelCurso $curso): bool => $this->esperaSuActa($curso, $ahora)));
        $ids = array_map(fn (DatosDelCurso $curso): int => $curso->grupoId, $cursos);
        $actas = Acta::query()->whereIn('grupo_id', $ids)->pluck('estado', 'grupo_id');
        $docentes = $ids === [] ? [] : ($this->docentesDeGrupos)($ids);
        $pendientes = array_filter($cursos, fn (DatosDelCurso $curso): bool => $actas->get($curso->grupoId) !== EstadoActa::Cerrada);

        return array_values(array_map(fn (DatosDelCurso $curso): array => [
            'grupo_id' => $curso->grupoId,
            'codigo' => $curso->codigo,
            'idioma' => $curso->idioma,
            'ciclo' => "Ciclo {$curso->cicloNumero} · {$curso->cicloNombre}",
            'docente' => $docentes[$curso->grupoId] ?? null,
            'ventana' => $this->ventana($curso),
            'estado_grupo' => $curso->estado->etiqueta(),
            'estado_acta' => $actas->get($curso->grupoId)?->etiqueta() ?? 'Sin validar',
        ], $pendientes));
    }

    private function esperaSuActa(DatosDelCurso $curso, CarbonImmutable $ahora): bool
    {
        return in_array($curso->estado, [EstadoGrupo::Cerrado, EstadoGrupo::Finalizado], true)
            || ($curso->estado === EstadoGrupo::Abierto && $curso->admiteActasEl($ahora));
    }

    private function ventana(DatosDelCurso $curso): ?string
    {
        if (! $curso->actasInicio instanceof CarbonImmutable || ! $curso->actasFin instanceof CarbonImmutable) {
            return null;
        }

        return "{$curso->actasInicio->format('d/m/Y')} al {$curso->actasFin->format('d/m/Y')}";
    }
}
