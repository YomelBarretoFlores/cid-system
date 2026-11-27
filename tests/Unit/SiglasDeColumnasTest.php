<?php

declare(strict_types=1);

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Certificacion\Support\SiglasDeColumnas;

it('names grade columns by their initials as the previous register did, lengthening a repeated one', function (): void {
    $columnas = array_map(
        fn (array $columna): ComponenteDeEvaluacion => new ComponenteDeEvaluacion($columna[0], $columna[1], $columna[2]),
        [[1, 'escuchar', 'Escuchar'], [2, 'hablar', 'Hablar'], [3, 'escribir', 'Escribir'], [4, 'leer', 'Leer'],
            [5, 'ph', 'Promedio de habilidades'], [6, 'ep', 'Examen parcial'], [7, 'ef', 'Examen final'], [8, 'pe', 'Promedio de exámenes'], [9, 'pf', 'Promedio final'],
            [10, 'pf2', 'Promedio final']],
    );

    expect(SiglasDeColumnas::de($columnas))->toBe([
        'escuchar' => 'E', 'hablar' => 'H', 'escribir' => 'ES', 'leer' => 'L',
        'ph' => 'PH', 'ep' => 'EP', 'ef' => 'EF', 'pe' => 'PE', 'pf' => 'PF', 'pf2' => 'PRF',
    ]);
});
