<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Parametros\Data\ValoresDeParametros;
use Carbon\CarbonImmutable;

final readonly class VentanasDeLlamado
{
    public function __construct(
        public CarbonImmutable $inicio,
        public CarbonImmutable $fin,
    ) {}

    public static function de(CarbonImmutable $fecha, SesionDeClase $sesion): self
    {
        return self::entre($fecha->toDateString(), $sesion->horaInicio, $sesion->horaFin);
    }

    public static function deLaClase(ClaseDictada $clase): self
    {
        return self::entre($clase->fecha->toDateString(), $clase->hora_inicio, $clase->hora_fin);
    }

    public function abreInicio(): CarbonImmutable
    {
        return $this->inicio->subMinutes($this->parametros()->inicioAbreMinutosAntes);
    }

    public function cierraInicio(): CarbonImmutable
    {
        return $this->fin;
    }

    public function abreCierre(): CarbonImmutable
    {
        return $this->fin->subMinutes($this->parametros()->cierreAbreMinutosAntesDelFin);
    }

    public function cierraCierre(): CarbonImmutable
    {
        return $this->fin->addMinutes($this->parametros()->cierreCierraMinutosDespuesDelFin);
    }

    public function venceConfirmacion(): CarbonImmutable
    {
        return $this->fin->addMinutes($this->parametros()->confirmacionMinutosDespuesDelFin);
    }

    public function yaAbrio(CarbonImmutable $ahora): bool
    {
        return $ahora->gte($this->abreInicio());
    }

    public function inicioAbierto(CarbonImmutable $ahora): bool
    {
        return $ahora->between($this->abreInicio(), $this->cierraInicio());
    }

    public function cierreAbierto(CarbonImmutable $ahora): bool
    {
        return $ahora->between($this->abreCierre(), $this->cierraCierre());
    }

    private static function entre(string $fecha, string $horaInicio, string $horaFin): self
    {
        return new self(
            CarbonImmutable::parse("{$fecha} {$horaInicio}", ClasesDelHorario::ZONA),
            CarbonImmutable::parse("{$fecha} {$horaFin}", ClasesDelHorario::ZONA),
        );
    }

    private function parametros(): ValoresDeParametros
    {
        return app(ParametrosDelSistema::class)();
    }
}
