<?php

declare(strict_types=1);

namespace App\Modules\Academico\Support;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

/** Validates and normalizes the components of an evaluation scheme: codes, names and weights that sum exactly 100. */
final class ComponentesDelEsquema
{
    /**
     * @param  list<array{codigo: string, nombre: string, peso: string}>  $entrada
     * @return list<array{codigo: string, nombre: string, peso: numeric-string, orden: int}>
     */
    public static function validar(array $entrada): array
    {
        if ($entrada === []) {
            throw new ReglaDeNegocioIncumplida('Agrega al menos un componente de evaluación.');
        }

        $componentes = [];

        foreach ($entrada as $posicion => $componente) {
            $codigo = self::codigo($componente['codigo']);

            if (array_key_exists($codigo, $componentes)) {
                throw new ReglaDeNegocioIncumplida("El código «{$codigo}» se repite.");
            }

            $componentes[$codigo] = ['codigo' => $codigo, 'nombre' => self::nombre($componente['nombre'], $codigo), 'peso' => self::peso($componente['peso'], $codigo), 'orden' => $posicion + 1];
        }

        self::asegurarCien(array_column($componentes, 'peso'));

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

        return $limpio !== '' && mb_strlen($limpio) <= 80 ? $limpio : throw new ReglaDeNegocioIncumplida("Escribe el nombre del componente «{$codigo}», hasta 80 caracteres.");
    }

    /**
     * @return numeric-string
     */
    private static function peso(string $peso, string $codigo): string
    {
        $valor = trim($peso);

        if (! is_numeric($valor) || bccomp($valor, '0', 2) <= 0 || bccomp($valor, '100', 2) > 0) {
            throw new ReglaDeNegocioIncumplida("El peso de «{$codigo}» debe ser mayor que 0 y hasta 100.");
        }

        return bcadd($valor, '0', 2);
    }

    /**
     * @param  list<numeric-string>  $pesos
     */
    private static function asegurarCien(array $pesos): void
    {
        $total = array_reduce($pesos, fn (string $suma, string $peso): string => bcadd($suma, $peso, 2), '0');

        if (bccomp($total, '100', 2) !== 0) {
            throw new ReglaDeNegocioIncumplida('Los pesos de los componentes deben sumar 100; ahora suman '.rtrim(rtrim($total, '0'), '.').'.');
        }
    }
}
