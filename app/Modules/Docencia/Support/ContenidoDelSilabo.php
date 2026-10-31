<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Data\EsquemaDeEvaluacion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// What the teacher typed, trimmed and checked against the group: one row per period week and per scheme component.
final readonly class ContenidoDelSilabo
{
    public const int LIMITE_DE_TEXTO = 2000;

    public const int LIMITE_DE_CELDA = 500;

    public const array TEXTOS = ['sumilla' => 'Sumilla', 'competencias' => 'Competencias', 'capacidades' => 'Capacidades', 'metodologia' => 'Metodología', 'bibliografia' => 'Bibliografía'];

    public const array CAMPOS_DE_SEMANA = ['unidad' => 'Unidad', 'saber' => 'Saber', 'saber_hacer' => 'Saber hacer', 'saber_ser' => 'Saber ser', 'actividades' => 'Actividades', 'recursos' => 'Recursos'];

    public const array CAMPOS_DE_EVALUACION = ['indicadores' => 'Indicadores', 'instrumento' => 'Instrumento'];

    private const array OBLIGATORIOS_DE_SEMANA = ['saber', 'actividades'];

    /**
     * @param  array<string, ?string>  $textos
     * @param  array<int, array<string, ?string>>  $semanas
     * @param  array<string, array<string, string|int|null>>  $evaluaciones  by component code, in scheme order
     * @param  array<string, string>  $nombres  component code => name
     */
    private function __construct(
        public array $textos,
        public array $semanas,
        public array $evaluaciones,
        private array $nombres,
        private bool $conEsquema,
    ) {}

    /**
     * Missing weeks or components count as empty; unknown ones mean the page is stale.
     *
     * @param  array<mixed>  $datos
     */
    public static function leer(array $datos, int $totalDeSemanas, ?EsquemaDeEvaluacion $esquema): self
    {
        $nombres = [];

        foreach ($esquema->componentes ?? [] as $componente) {
            $nombres[$componente->codigo] = $componente->nombre;
        }

        $semanas = self::lista($datos['semanas'] ?? [], range(1, $totalDeSemanas), 'Las semanas del periodo cambiaron; vuelve a cargar la página.');
        $evaluaciones = self::lista($datos['evaluaciones'] ?? [], array_keys($nombres), 'El sistema de evaluación del grupo cambió; vuelve a cargar la página.');

        return new self(
            self::textos($datos, self::TEXTOS, '', self::LIMITE_DE_TEXTO),
            self::semanas($semanas, $totalDeSemanas),
            self::evaluaciones($evaluaciones, $nombres, $totalDeSemanas),
            $nombres,
            $esquema instanceof EsquemaDeEvaluacion,
        );
    }

    public function asegurarCompleto(): void
    {
        if (! $this->conEsquema) {
            throw new ReglaDeNegocioIncumplida('El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre antes de publicar el sílabo.');
        }

        $faltantes = $this->faltantes();

        if ($faltantes !== []) {
            throw new ReglaDeNegocioIncumplida('Para publicar el sílabo completa: '.implode('; ', $faltantes).'.');
        }
    }

    /**
     * @return list<string>
     */
    public function faltantes(): array
    {
        $textos = array_keys(array_filter($this->textos, fn (?string $texto): bool => $texto === null));

        return [
            ...array_map(fn (string $campo): string => self::TEXTOS[$campo], $textos),
            ...$this->incompletas($this->semanas, self::OBLIGATORIOS_DE_SEMANA, self::CAMPOS_DE_SEMANA, fn (int|string $semana): string => "Semana {$semana}"),
            ...$this->incompletas($this->evaluaciones, array_keys(self::CAMPOS_DE_EVALUACION), self::CAMPOS_DE_EVALUACION, fn (int|string $codigo): string => 'Evaluación '.($this->nombres[$codigo] ?? $codigo)),
        ];
    }

    /**
     * @param  array<int|string, array<string, string|int|null>>  $filas
     * @param  list<string>  $obligatorios
     * @param  array<string, string>  $etiquetas
     * @param  callable(int|string): string  $titulo
     * @return list<string>
     */
    private function incompletas(array $filas, array $obligatorios, array $etiquetas, callable $titulo): array
    {
        $incompletas = [];

        foreach ($filas as $clave => $fila) {
            $vacios = array_values(array_filter($obligatorios, fn (string $campo): bool => $fila[$campo] === null));

            if ($vacios !== []) {
                $incompletas[] = $titulo($clave).': '.implode(' y ', array_map(fn (string $campo): string => $etiquetas[$campo], $vacios));
            }
        }

        return $incompletas;
    }

    /**
     * @param  list<int|string>  $permitidas
     * @return array<mixed>
     */
    private static function lista(mixed $valor, array $permitidas, string $desactualizada): array
    {
        $claves = is_array($valor) ? array_map(strval(...), array_keys($valor)) : null;

        if ($claves === null || array_diff($claves, array_map(strval(...), $permitidas)) !== []) {
            throw new ReglaDeNegocioIncumplida($desactualizada);
        }

        return $valor;
    }

    /**
     * @param  array<mixed>  $entrada
     * @return array<int, array<string, ?string>>
     */
    private static function semanas(array $entrada, int $totalDeSemanas): array
    {
        $semanas = [];

        foreach (range(1, $totalDeSemanas) as $semana) {
            $semanas[$semana] = self::textos(self::fila($entrada[$semana] ?? []), self::CAMPOS_DE_SEMANA, "Semana {$semana}: ", self::LIMITE_DE_CELDA);
        }

        return $semanas;
    }

    /**
     * @param  array<mixed>  $entrada
     * @param  array<string, string>  $nombres
     * @return array<string, array<string, string|int|null>>
     */
    private static function evaluaciones(array $entrada, array $nombres, int $totalDeSemanas): array
    {
        $evaluaciones = [];

        foreach ($nombres as $codigo => $nombre) {
            $fila = self::fila($entrada[$codigo] ?? []);
            $evaluaciones[$codigo] = [
                'indicadores' => self::texto($fila['indicadores'] ?? null, "{$nombre}: Indicadores", self::LIMITE_DE_CELDA),
                'instrumento' => self::texto($fila['instrumento'] ?? null, "{$nombre}: Instrumento", self::LIMITE_DE_CELDA),
                'semana' => self::semana($fila['semana'] ?? null, $nombre, $totalDeSemanas),
            ];
        }

        return $evaluaciones;
    }

    /**
     * @param  array<mixed>  $fila
     * @param  array<string, string>  $etiquetas
     * @return array<string, ?string>
     */
    private static function textos(array $fila, array $etiquetas, string $prefijo, int $limite): array
    {
        $textos = [];

        foreach ($etiquetas as $campo => $etiqueta) {
            $textos[$campo] = self::texto($fila[$campo] ?? null, $prefijo.$etiqueta, $limite);
        }

        return $textos;
    }

    /**
     * @return array<mixed>
     */
    private static function fila(mixed $fila): array
    {
        return is_array($fila) ? $fila : throw new ReglaDeNegocioIncumplida('El sílabo enviado no es válido; vuelve a cargar la página.');
    }

    private static function texto(mixed $valor, string $etiqueta, int $limite): ?string
    {
        if (! is_string($valor) && $valor !== null) {
            throw new ReglaDeNegocioIncumplida("{$etiqueta}: el texto no es válido.");
        }

        $texto = trim(str_replace("\r\n", "\n", (string) $valor));

        if (mb_strlen($texto) > $limite) {
            throw new ReglaDeNegocioIncumplida("{$etiqueta}: usa como máximo {$limite} caracteres.");
        }

        return $texto === '' ? null : $texto;
    }

    private static function semana(mixed $valor, string $nombre, int $totalDeSemanas): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $semana = is_int($valor) || (is_string($valor) && ctype_digit($valor)) ? (int) $valor : 0;

        return $semana >= 1 && $semana <= $totalDeSemanas ? $semana : throw new ReglaDeNegocioIncumplida("{$nombre}: la semana debe estar entre 1 y {$totalDeSemanas}.");
    }
}
