<?php

declare(strict_types=1);

namespace App\Modules\Academico\Support;

use App\Modules\Academico\Enums\RegimenDeEsquema;
use App\Modules\Academico\Enums\TipoDeComponente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

/** Validates and normalizes the columns of an evaluation scheme: codes, names, what each one is, and which ones are the grade of each cycle. */
final class ComponentesDelEsquema
{
    /**
     * @param  list<array{codigo: string, nombre: string, tipo: string, calculo?: ?string, redondeo?: ?string, fuentes?: list<array{codigo: string, peso?: mixed}>, ciclo?: mixed}>  $entrada
     * @return list<array{codigo: string, nombre: string, tipo: string, calculo: ?string, redondeo: string, fuentes: list<array{codigo: string, peso: int}>, ciclo: ?int, orden: int}>
     */
    public static function validar(array $entrada, ?RegimenDeEsquema $regimen): array
    {
        if ($entrada === []) {
            throw new ReglaDeNegocioIncumplida('Agrega al menos una columna.');
        }

        $componentes = [];

        foreach ($entrada as $posicion => $componente) {
            $codigo = self::codigo($componente['codigo']);

            if (array_key_exists($codigo, $componentes)) {
                throw new ReglaDeNegocioIncumplida("El código «{$codigo}» se repite.");
            }

            $componentes[$codigo] = [
                'codigo' => $codigo, 'nombre' => self::nombre($componente['nombre'], $codigo),
                ...ColumnaDelEsquema::definir($componente, $codigo, array_keys($componentes)),
                'ciclo' => self::ciclo($componente['ciclo'] ?? null, $codigo), 'orden' => $posicion + 1,
            ];
        }

        self::asegurarCompleto(array_values($componentes), $regimen);

        return array_values($componentes);
    }

    /**
     * @return numeric-string
     */
    public static function decimal(string $valor): string
    {
        $limpio = trim($valor);

        return is_numeric($limpio) ? bcadd($limpio, '0', 2) : throw new ReglaDeNegocioIncumplida("«{$valor}» no es un número válido.");
    }

    private static function codigo(string $codigo): string
    {
        $normalizado = mb_strtolower(trim($codigo));

        return preg_match('/^[a-z0-9_]{1,30}$/', $normalizado) === 1
            ? $normalizado
            : throw new ReglaDeNegocioIncumplida("El código «{$codigo}» solo admite letras sin tilde, números y guion bajo, hasta 30 caracteres.");
    }

    private static function nombre(string $nombre, string $codigo): string
    {
        $limpio = trim($nombre);

        return $limpio !== '' && mb_strlen($limpio) <= 80 ? $limpio : throw new ReglaDeNegocioIncumplida("Escribe el nombre de la columna «{$codigo}», hasta 80 caracteres.");
    }

    private static function ciclo(mixed $ciclo, string $codigo): ?int
    {
        if ($ciclo === null || $ciclo === '') {
            return null;
        }

        return in_array((string) $ciclo, ['1', '2'], true) ? (int) $ciclo : throw new ReglaDeNegocioIncumplida("La columna «{$codigo}» solo puede ser la nota del primer o del segundo ciclo.");
    }

    /**
     * @param  list<array{tipo: string, ciclo: ?int}>  $componentes
     */
    private static function asegurarCompleto(array $componentes, ?RegimenDeEsquema $regimen): void
    {
        $ciclos = array_count_values(array_filter(array_column($componentes, 'ciclo')));

        if (! in_array(TipoDeComponente::Ingresada->value, array_column($componentes, 'tipo'), true)) {
            throw new ReglaDeNegocioIncumplida('Agrega al menos una columna que ingrese el docente.');
        }

        if (($ciclos[1] ?? 0) !== 1 || ($ciclos[2] ?? 0) > 1) {
            throw new ReglaDeNegocioIncumplida('Marca una sola columna como nota del ciclo (y, en el acelerado, una sola como nota del segundo ciclo).');
        }

        if (isset($ciclos[2]) && $regimen !== RegimenDeEsquema::Acelerado) {
            throw new ReglaDeNegocioIncumplida('La nota del segundo ciclo solo existe en un sistema para el régimen acelerado.');
        }
    }
}
