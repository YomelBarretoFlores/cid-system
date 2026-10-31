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

    /**
     * @return array<int, string>
     */
    public static function meses(): array
    {
        return self::MESES;
    }

    public static function nombreDelMes(int $mes): ?string
    {
        return self::MESES[$mes] ?? null;
    }

    public static function nombreDeArchivoDelMes(int $anio, int $mes, string $extension): string
    {
        return sprintf('expediente-de-horas-%04d-%02d.%s', $anio, $mes, $extension);
    }

    public function periodo(): string
    {
        return self::nombreDelMes($this->mes).' '.$this->anio;
    }

    public function nombreDeArchivo(string $extension): string
    {
        return str_replace(".{$extension}", "-{$this->codigo}.{$extension}", self::nombreDeArchivoDelMes($this->anio, $this->mes, $extension));
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

    // Informative until the CILN defines deductions: the hours and the amount count every class.
    public function clasesConfirmadas(): int
    {
        return array_sum(array_map(fn (LineaDeHoras $linea): int => $linea->clasesConfirmadas, $this->lineas));
    }

    // Observed by the delegate or left unanswered past the window.
    public function clasesNoConfirmadas(): int
    {
        return array_sum(array_map(fn (LineaDeHoras $linea): int => $linea->clasesNoConfirmadas, $this->lineas));
    }

    public function tieneHorasSinCosto(): bool
    {
        return array_any($this->lineas, fn (LineaDeHoras $linea): bool => $linea->costoCentimos === null);
    }
}
