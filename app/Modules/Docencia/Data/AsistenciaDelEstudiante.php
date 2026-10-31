<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Docencia\Enums\EstadoAsistencia;

// A late arrival counts as attended; percentages are whole numbers rounded half up.
final readonly class AsistenciaDelEstudiante
{
    public int $dictadas;

    public int $presentes;

    public int $tardanzas;

    public int $faltas;

    /**
     * @param  list<AsistenciaDeClase>  $clases
     */
    public function __construct(
        public array $clases,
        public int $programadas,
    ) {
        $this->dictadas = count($clases);
        $this->presentes = $this->contar(EstadoAsistencia::Presente);
        $this->tardanzas = $this->contar(EstadoAsistencia::Tardanza);
        $this->faltas = $this->contar(EstadoAsistencia::Falta);
    }

    public function porcentajeSobreDictadas(): ?int
    {
        return $this->porcentaje($this->dictadas);
    }

    public function porcentajeSobreProgramadas(): ?int
    {
        return $this->porcentaje($this->programadas);
    }

    private function porcentaje(int $total): ?int
    {
        return $total === 0 ? null : intdiv(($this->presentes + $this->tardanzas) * 200 + $total, $total * 2);
    }

    private function contar(EstadoAsistencia $estado): int
    {
        return count(array_filter($this->clases, fn (AsistenciaDeClase $clase): bool => $clase->efectivo === $estado));
    }
}
