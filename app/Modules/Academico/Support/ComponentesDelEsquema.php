<?php

declare(strict_types=1);

namespace App\Modules\Academico\Support;

use App\Modules\Academico\Enums\GrupoDeComponente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

/** Validates and normalizes the components of an evaluation scheme: codes, names and a group, with at least one skill and one exam. */
final class ComponentesDelEsquema
{
    /**
     * @param  list<array{codigo: string, nombre: string, grupo: string}>  $entrada
     * @return list<array{codigo: string, nombre: string, grupo: GrupoDeComponente, orden: int}>
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

            $componentes[$codigo] = ['codigo' => $codigo, 'nombre' => self::nombre($componente['nombre'], $codigo), 'grupo' => self::grupo($componente['grupo'], $codigo), 'orden' => $posicion + 1];
        }

        self::asegurarAmbosGrupos(array_column($componentes, 'grupo'));

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

    private static function grupo(string $grupo, string $codigo): GrupoDeComponente
    {
        return GrupoDeComponente::tryFrom(trim($grupo)) ?? throw new ReglaDeNegocioIncumplida("Elige si el componente «{$codigo}» es una habilidad o un examen.");
    }

    /**
     * @param  list<GrupoDeComponente>  $grupos
     */
    private static function asegurarAmbosGrupos(array $grupos): void
    {
        if (! in_array(GrupoDeComponente::Habilidades, $grupos, true) || ! in_array(GrupoDeComponente::Examenes, $grupos, true)) {
            throw new ReglaDeNegocioIncumplida('El sistema de evaluación necesita al menos una habilidad y al menos un examen.');
        }
    }
}
