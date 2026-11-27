<?php

declare(strict_types=1);

namespace App\Modules\Academico\Support;

use App\Modules\Academico\Enums\CalculoDeComponente;
use App\Modules\Academico\Enums\RedondeoDeNota;
use App\Modules\Academico\Enums\TipoDeComponente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

/** What one column is: entered by the teacher, or calculated from earlier columns with a simple or weighted average. */
final class ColumnaDelEsquema
{
    /**
     * @param  array{tipo: string, calculo?: ?string, redondeo?: ?string, fuentes?: list<array{codigo: string, peso?: mixed}>}  $componente
     * @param  list<string>  $anteriores  codes of the columns before this one
     * @return array{tipo: string, calculo: ?string, redondeo: string, fuentes: list<array{codigo: string, peso: int}>}
     */
    public static function definir(array $componente, string $codigo, array $anteriores): array
    {
        $tipo = TipoDeComponente::tryFrom(trim($componente['tipo']))
            ?? throw new ReglaDeNegocioIncumplida("Elige si la columna «{$codigo}» la ingresa el docente o es calculada.");

        if ($tipo === TipoDeComponente::Ingresada) {
            return ['tipo' => $tipo->value, 'calculo' => null, 'redondeo' => RedondeoDeNota::Ninguno->value, 'fuentes' => []];
        }

        $calculo = CalculoDeComponente::tryFrom(trim((string) ($componente['calculo'] ?? '')))
            ?? throw new ReglaDeNegocioIncumplida("Elige cómo se calcula la columna «{$codigo}».");
        $redondeo = RedondeoDeNota::tryFrom(trim((string) ($componente['redondeo'] ?? ''))) ?? RedondeoDeNota::Ninguno;

        return ['tipo' => $tipo->value, 'calculo' => $calculo->value, 'redondeo' => $redondeo->value, 'fuentes' => self::fuentes($componente['fuentes'] ?? [], $codigo, $calculo, $anteriores)];
    }

    /**
     * @param  list<array{codigo: string, peso?: mixed}>  $fuentes
     * @param  list<string>  $anteriores
     * @return list<array{codigo: string, peso: int}>
     */
    private static function fuentes(array $fuentes, string $codigo, CalculoDeComponente $calculo, array $anteriores): array
    {
        $usadas = [];

        foreach ($fuentes as $fuente) {
            $usada = mb_strtolower(trim($fuente['codigo']));

            if (! in_array($usada, $anteriores, true) || isset($usadas[$usada])) {
                throw new ReglaDeNegocioIncumplida("La columna «{$codigo}» solo puede usar columnas que estén antes de ella, cada una una vez.");
            }

            $usadas[$usada] = ['codigo' => $usada, 'peso' => $calculo === CalculoDeComponente::Ponderado ? self::peso($fuente['peso'] ?? null, $codigo) : 1];
        }

        return $usadas === [] ? throw new ReglaDeNegocioIncumplida("Elige las columnas que usa «{$codigo}».") : array_values($usadas);
    }

    private static function peso(mixed $peso, string $codigo): int
    {
        $texto = is_scalar($peso) ? trim((string) $peso) : '';

        return preg_match('/^\d{1,3}$/', $texto) === 1 && (int) $texto >= 1 && (int) $texto <= 100
            ? (int) $texto
            : throw new ReglaDeNegocioIncumplida("Cada peso de la columna «{$codigo}» debe ser un número entero de 1 a 100.");
    }
}
