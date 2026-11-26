<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CampoFut;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Normalizes what a trámite type asks: its FUT fields in form order and its attachments.
final class ConfiguracionDelFut
{
    public const int MAXIMO_ADJUNTOS = 5;

    /**
     * @param  ?list<array{clave: string, obligatorio: bool}>  $campos
     * @return list<array{clave: string, obligatorio: bool}>
     */
    public static function campos(?array $campos, AccionFinal $accion): array
    {
        if ($campos === null) {
            return $accion->camposPorDefecto();
        }

        $obligatorios = [];

        foreach ($campos as $campo) {
            $clave = CampoFut::tryFrom($campo['clave']) ?? throw new ReglaDeNegocioIncumplida('El FUT solo admite los campos de la lista.');
            $obligatorios[$clave->value] = ($obligatorios[$clave->value] ?? false) || $campo['obligatorio'];
        }

        foreach ($accion->camposRequeridos() as $requerido) {
            throw_unless($obligatorios[$requerido->value] ?? false, ReglaDeNegocioIncumplida::class, "La acción «{$accion->etiqueta()}» necesita el campo «{$requerido->etiqueta()}» como obligatorio.");
        }

        return array_values(array_map(
            fn (CampoFut $campo): array => ['clave' => $campo->value, 'obligatorio' => $obligatorios[$campo->value] ?? false],
            array_filter(CampoFut::cases(), fn (CampoFut $campo): bool => array_key_exists($campo->value, $obligatorios)),
        ));
    }

    /**
     * @param  list<array{nombre: string, obligatorio: bool}>  $adjuntos
     * @return list<array{nombre: string, obligatorio: bool}>
     */
    public static function adjuntos(array $adjuntos): array
    {
        $normalizados = array_map(fn (array $adjunto): array => ['nombre' => trim($adjunto['nombre']), 'obligatorio' => $adjunto['obligatorio']], $adjuntos);
        $nombres = array_map(fn (array $adjunto): string => mb_strtolower($adjunto['nombre']), $normalizados);
        $validos = count($normalizados) <= self::MAXIMO_ADJUNTOS
            && count(array_unique($nombres)) === count($nombres)
            && array_filter($normalizados, fn (array $adjunto): bool => mb_strlen($adjunto['nombre']) < 3 || mb_strlen($adjunto['nombre']) > 80) === [];

        throw_unless($validos, ReglaDeNegocioIncumplida::class, 'Un trámite pide como máximo 5 adjuntos, cada uno con un nombre de 3 a 80 caracteres sin repetir.');

        return $normalizados;
    }
}
