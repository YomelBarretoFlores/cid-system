<?php

declare(strict_types=1);

use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Models\Pago;
use Illuminate\Database\QueryException;

it('links an enrollment and a placement request to the payment that unlocked them', function (): void {
    $pagoMatricula = Pago::factory()->validado()->create();
    $pagoExamen = Pago::factory()->validado()->create();

    $matricula = Matricula::factory()->create(['pago_id' => $pagoMatricula->id]);
    $solicitud = SolicitudClasificacion::factory()->create(['pago_id' => $pagoExamen->id]);

    expect($matricula->refresh()->pago_id)->toBe($pagoMatricula->id)
        ->and($solicitud->refresh()->pago_id)->toBe($pagoExamen->id);
});

it('never lets one payment unlock two enrollments', function (): void {
    $pago = Pago::factory()->validado()->create();
    Matricula::factory()->create(['pago_id' => $pago->id]);

    expect(fn () => Matricula::factory()->create(['pago_id' => $pago->id]))
        ->toThrow(QueryException::class, 'mat_matriculas_pago_id_unique');
});

it('never lets one payment unlock two placement requests', function (): void {
    $pago = Pago::factory()->validado()->create();
    SolicitudClasificacion::factory()->create(['pago_id' => $pago->id]);

    expect(fn () => SolicitudClasificacion::factory()->create(['pago_id' => $pago->id]))
        ->toThrow(QueryException::class, 'mat_solicitudes_clasificacion_pago_id_unique');
});
