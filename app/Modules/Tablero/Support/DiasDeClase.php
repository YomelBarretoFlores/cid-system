<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

final class DiasDeClase
{
    private const array ABREVIADOS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    /**
     * Three or more consecutive days read as a range ("Lun–Vie"); otherwise they are listed.
     *
     * @param  list<int>  $dias  ISO weekdays in order
     */
    public static function texto(array $dias): string
    {
        $consecutivos = count($dias) >= 3 && $dias === range($dias[0], $dias[0] + count($dias) - 1);

        return $consecutivos
            ? self::ABREVIADOS[$dias[0]].'–'.self::ABREVIADOS[$dias[count($dias) - 1]]
            : implode(', ', array_map(fn (int $dia): string => self::ABREVIADOS[$dia] ?? (string) $dia, $dias));
    }
}
