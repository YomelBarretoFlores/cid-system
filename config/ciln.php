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

    'docencia' => [
        // Suggested while typing the teacher's bank, so its spelling converges; any other bank is still accepted.
        'bancos' => [
            'Banco de la Nación', 'BCP', 'BBVA', 'Interbank', 'Scotiabank', 'Banco Pichincha', 'BanBif',
            'Caja Arequipa', 'Caja Huancayo', 'Caja Piura', 'Caja Trujillo', 'Caja Cusco',
        ],
    ],

    'pagos' => [
        // Segregation of duties: whoever registered a payment (staff on someone's behalf) cannot validate it.
        'validacion_por_otra_persona' => (bool) env('CILN_VALIDACION_POR_OTRA_PERSONA', true),
    ],

    'respaldos' => [
        // Off until the backup disk is agreed with the DSTI (pending decision #5); see docs/despliegue.md.
        'programados' => (bool) env('CILN_RESPALDOS_PROGRAMADOS', false),
    ],
];
