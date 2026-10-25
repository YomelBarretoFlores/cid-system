<?php

declare(strict_types=1);

use App\Modules\Academico\Data\SesionDeClase;

it('joins the days that share the same hours into one short line', function (): void {
    $sesiones = [
        new SesionDeClase(5, '07:00:00', '08:30:00'),
        new SesionDeClase(1, '07:00:00', '08:30:00'),
        new SesionDeClase(3, '07:00:00', '08:30:00'),
        new SesionDeClase(6, '14:00:00', '18:30:00'),
    ];

    expect(SesionDeClase::compactar($sesiones))->toBe(['Lun · Mié · Vie, 07:00–08:30', 'Sáb, 14:00–18:30'])
        ->and(SesionDeClase::compactar([]))->toBe([]);
});
