<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;

// One card of the teacher's "Mis grupos": where the group stands on each task and which one comes next.
final readonly class ResumenDeMiGrupo
{
    public function __construct(
        public HorarioDeGrupo $grupo,
        public ?EstadoSilabo $silabo,
        public int $dictadas,
        public int $programadas,
        public ?ClaseProgramada $proxima,
        public bool $esHoy,
        public int $notasRegistradas,
        public int $notasEsperadas,
        public ?EstadoActa $acta,
        public ?CarbonImmutable $actasInicio,
        public ?CarbonImmutable $actasFin,
    ) {}

    public function proximaClase(): string
    {
        return implode(' · ', $this->partesDeLaProximaClase());
    }

    /**
     * The next class as separate parts (when, time, classroom), so the view can keep each one on a single line.
     *
     * @return list<string>
     */
    public function partesDeLaProximaClase(): array
    {
        if (! $this->proxima instanceof ClaseProgramada) {
            return ['Sin clases pendientes en el periodo'];
        }

        $cuando = match (true) {
            $this->esHoy => 'Hoy',
            $this->dictadas === 0 && $this->proxima->fecha->isSameDay($this->grupo->periodoInicio) => 'Empieza: '.Formato::diaYMes($this->proxima->fecha),
            default => 'Próxima clase: '.Formato::diaYMes($this->proxima->fecha),
        };

        $partes = [$cuando, substr($this->proxima->sesion->horaInicio, 0, 5).'–'.substr($this->proxima->sesion->horaFin, 0, 5)];

        return $this->grupo->aulaCodigo === null ? $partes : [...$partes, $this->grupo->aulaCodigo];
    }

    public function silaboListo(): bool
    {
        return $this->silabo === EstadoSilabo::Publicado;
    }

    public function notasCompletas(): bool
    {
        return $this->notasEsperadas > 0 && $this->notasRegistradas >= $this->notasEsperadas;
    }

    public function detalleDelActa(): string
    {
        return match (true) {
            $this->acta instanceof EstadoActa => $this->acta->etiqueta(),
            $this->actasInicio instanceof CarbonImmutable && $this->actasFin instanceof CarbonImmutable => 'Del '.Formato::diaYMes($this->actasInicio).' al '.Formato::diaYMes($this->actasFin),
            default => 'Sin fechas de actas',
        };
    }

    public function actaAbiertaHoy(): bool
    {
        $hoy = ClasesDelHorario::hoy();

        return $this->acta !== EstadoActa::Cerrada && $this->actasInicio instanceof CarbonImmutable && $this->actasFin instanceof CarbonImmutable
            && $hoy->betweenIncluded($this->actasInicio, $this->actasFin);
    }

    /**
     * The step the teacher should take now, as [label, route name of the group page].
     *
     * @return array{string, string}
     */
    public function siguientePaso(): array
    {
        return match (true) {
            ! $this->silaboListo() => ['Completar sílabo', 'filament.docente.pages.grupos.silabo'],
            $this->esHoy => ['Tomar asistencia', 'filament.docente.pages.grupos.asistencia'],
            $this->acta === EstadoActa::Cerrada => ['Ver acta final', 'filament.docente.pages.grupos.acta'],
            $this->actaAbiertaHoy() && $this->notasCompletas() => ['Cerrar acta', 'filament.docente.pages.grupos.acta'],
            ! $this->notasCompletas() && $this->dictadas > 0 => ['Completar notas', 'filament.docente.pages.grupos.notas'],
            default => ['Tomar asistencia', 'filament.docente.pages.grupos.asistencia'],
        };
    }
}
