<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;

// Printed registers name each grade column by its initials, as the previous system did (E, H, ES, L, PH, EP…), with a legend below.
final class SiglasDeColumnas
{
    private const array CONECTORES = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'a', 'en'];

    /**
     * @param  list<ComponenteDeEvaluacion>  $columnas
     * @return array<string, string> column code => initials, unique within the acta
     */
    public static function de(array $columnas): array
    {
        $siglas = [];

        foreach ($columnas as $columna) {
            $siglas[$columna->codigo] = self::libre(self::palabras($columna->nombre), $siglas);
        }

        return $siglas;
    }

    /**
     * @return list<string>
     */
    private static function palabras(string $nombre): array
    {
        $palabras = preg_split('/\s+/u', trim($nombre)) ?: [];
        $significativas = array_values(array_filter($palabras, fn (string $palabra): bool => ! in_array(mb_strtolower($palabra), self::CONECTORES, true)));

        return $significativas === [] ? [$nombre] : $significativas;
    }

    // A repeated initial grows with the next letters of the first word (Escuchar E, Escribir ES), then gets a number.
    /**
     * @param  list<string>  $palabras
     * @param  array<string, string>  $usadas
     */
    private static function libre(array $palabras, array $usadas): string
    {
        $resto = implode('', array_map(fn (string $palabra): string => mb_substr($palabra, 0, 1), array_slice($palabras, 1)));
        $primera = $palabras[0];

        for ($largo = 1; $largo <= mb_strlen($primera); $largo++) {
            $sigla = mb_strtoupper(mb_substr($primera, 0, $largo).$resto);

            if (! in_array($sigla, $usadas, true)) {
                return $sigla;
            }
        }

        $base = mb_strtoupper(mb_substr($primera, 0, 1).$resto);
        $numero = 2;

        while (in_array($base.$numero, $usadas, true)) {
            $numero++;
        }

        return $base.$numero;
    }
}
