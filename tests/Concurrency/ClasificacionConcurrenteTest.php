<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;

it('uses one placement payment for only one of two simultaneous requests', function (): void {
    [$ingles, $quechua] = Idioma::factory()->count(2)->create();
    $persona = Persona::factory()->create();
    // Payments refuse TRUNCATE, so this row stays until the next migrate:fresh.
    $pago = Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::ExamenClasificacion]))->create(['persona_id' => $persona->id]);

    expect(enParalelo('solicitar-examen.php', [[$persona->id, $ingles->id, $pago->id], [$persona->id, $quechua->id, $pago->id]]))
        ->toBe(['El pago ya se usó en otra solicitud.', 'solicitada'])
        ->and(SolicitudClasificacion::query()->where('pago_id', $pago->id)->count())->toBe(1);
});
