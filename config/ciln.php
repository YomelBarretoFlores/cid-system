<?php

declare(strict_types=1);

return [
    // Roll call windows of a class, in minutes, around its scheduled start and end in America/Lima.
    'asistencia' => [
        'inicio_abre_minutos_antes' => 10,
        'cierre_abre_minutos_antes_del_fin' => 15,
        'cierre_cierra_minutos_despues_del_fin' => 30,
        'confirmacion_minutos_despues_del_fin' => 120,
    ],
];
