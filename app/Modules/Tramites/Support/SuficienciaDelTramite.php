<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Contracts\CiclosDelIdioma;
use App\Modules\Academico\Contracts\NotaAprobatoriaDelNivel;
use App\Modules\Academico\Data\CicloDelIdioma;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\AprobacionesPorSuficiencia;
use App\Modules\Matricula\Contracts\HabilitacionPorTramite;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Tramites\Data\DatosDeResolucion;
use App\Modules\Tramites\Data\ResultadoDeAccionFinal;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

// The grade staff enter decides, against the passing grade of the evaluation scheme in force, whether the level is passed.
final readonly class SuficienciaDelTramite
{
    public function __construct(
        private NotaAprobatoriaDelNivel $notaAprobatoria,
        private CiclosDelIdioma $ciclosDelIdioma,
        private AprobacionesPorSuficiencia $aprobacionesPorSuficiencia,
        private HabilitacionPorTramite $habilitacion,
    ) {}

    public function resolver(Tramite $tramite, User $usuario, DatosDeResolucion $resolucion): ResultadoDeAccionFinal
    {
        $idioma = (string) ($tramite->datos['idioma'] ?? '');
        $nivel = NivelCurricular::tryFrom((string) ($tramite->datos['nivel'] ?? '')) ?? throw new ReglaDeNegocioIncumplida('El trámite no indica un nivel válido.');
        $nota = $this->nota($resolucion->nota);
        $minima = ($this->notaAprobatoria)($idioma, $nivel, CarbonImmutable::today('America/Lima'))
            ?? throw new ReglaDeNegocioIncumplida("No hay un esquema de evaluación vigente para {$idioma} en el nivel {$nivel->etiqueta()}: configúralo para conocer la nota aprobatoria.");
        $minima = number_format((float) $minima, 2, '.', '');
        $aprobado = $this->centesimos($nota) >= $this->centesimos($minima);

        if ($aprobado) {
            ($this->aprobacionesPorSuficiencia)($tramite->persona_id, $idioma, $nivel, $nota, $tramite->id, $usuario->id);
            $this->habilitarSiguienteNivel($tramite->persona_id, $idioma, $nivel, $usuario);
        }

        $condicion = $aprobado ? 'aprobado' : 'desaprobado';

        return new ResultadoDeAccionFinal(
            resultado: ['nota' => $nota, 'nota_minima' => $minima, 'aprobado' => $aprobado],
            comentario: "Nota del examen de suficiencia: {$nota} ({$condicion}; nota mínima {$minima}).",
        );
    }

    // A passed level enables the first cycle of the next one, never lowering a cycle the student already reached.
    private function habilitarSiguienteNivel(int $personaId, string $idioma, NivelCurricular $nivel, User $usuario): void
    {
        $activos = array_values(array_filter(($this->ciclosDelIdioma)($idioma), fn (CicloDelIdioma $ciclo): bool => $ciclo->activo));
        $ultimo = max([0, ...array_map(fn (CicloDelIdioma $ciclo): int => $ciclo->nivel === $nivel ? $ciclo->numero : 0, $activos)]);
        $siguiente = collect($activos)->first(fn (CicloDelIdioma $ciclo): bool => $ciclo->numero > $ultimo && $ciclo->nivel !== $nivel);

        if ($siguiente instanceof CicloDelIdioma) {
            ($this->habilitacion)($personaId, $siguiente->id, OrigenHabilitacion::Suficiencia, $usuario->id, true);
        }
    }

    private function nota(?string $nota): string
    {
        $limpia = str_replace(',', '.', trim((string) $nota));

        throw_unless(preg_match('/^\d{1,2}(\.\d{1,2})?$/', $limpia) === 1 && (float) $limpia <= 20, ReglaDeNegocioIncumplida::class, 'Indica la nota del examen, de 0 a 20.');

        return number_format((float) $limpia, 2, '.', '');
    }

    private function centesimos(string $nota): int
    {
        return (int) round((float) $nota * 100);
    }
}
