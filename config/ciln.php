<?php

declare(strict_types=1);

return [
    'pagos' => [
        // Segregation of duties: whoever registered a payment (staff on someone's behalf) cannot validate it.
        'validacion_por_otra_persona' => (bool) env('CILN_VALIDACION_POR_OTRA_PERSONA', true),
    ],

    'respaldos' => [
        // Off until the backup disk is agreed with the DSTI (pending decision #5); see docs/despliegue.md.
        'programados' => (bool) env('CILN_RESPALDOS_PROGRAMADOS', false),
    ],
];
