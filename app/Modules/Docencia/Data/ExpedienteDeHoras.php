<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

final readonly class ExpedienteDeHoras
{
    private const array MESES = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    /**
     * @param  list<LineaDeHoras>  $lineas
     */
    public function __construct(
        public int $docenteId,
        public string $codigo,
        public string $nombre,
        public string $categoria,
        public int $anio,
        public int $mes,
        public array $lineas,
    ) {}

    public static function nombreDelMes(int $mes): string
    {
        return self::MESES[$mes];
    }

    public function periodo(): string
    {
        return self::MESES[$this->mes].' '.$this->anio;
    }

    public function nombreDeArchivo(string $extension): string
    {
        return sprintf('expediente-de-horas-%04d-%02d-%s.%s', $this->anio, $this->mes, $this->codigo, $extension);
    }

    public function montoTotalCentimos(): int
    {
        return array_sum(array_map(fn (LineaDeHoras $linea): int => $linea->montoCentimos ?? 0, $this->lineas));
    }

    public function horasTotalesCentesimas(): int
    {
        return array_sum(array_map(fn (LineaDeHoras $linea): int => $linea->centesimasDeHora, $this->lineas));
    }

    public function horasTotales(): string
    {
        return LineaDeHoras::decimal($this->horasTotalesCentesimas());
    }

    public function montoTotal(): string
    {
        return LineaDeHoras::decimal($this->montoTotalCentimos());
    }

    public function tieneHorasSinCosto(): bool
    {
        return array_any($this->lineas, fn (LineaDeHoras $linea): bool => $linea->costoCentimos === null);
    }
}
