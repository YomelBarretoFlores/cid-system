<?php

declare(strict_types=1);

use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;

it('lists only the validated payments of the person for the requested service, newest first', function (): void {
    $persona = Persona::factory()->create();
    $matricula = Concepto::factory()->create(['nombre' => 'Derecho de matrícula', 'tipo' => TipoConcepto::Matricula]);
    $antiguo = Pago::factory()->validado()->for($matricula)->create([
        'persona_id' => $persona->id, 'numero_operacion' => 'op-001', 'monto' => '150.00', 'fecha_pago' => '2026-10-01',
    ]);
    $reciente = Pago::factory()->validado()->for($matricula)->create(['persona_id' => $persona->id, 'fecha_pago' => '2026-10-20']);
    Pago::factory()->pendiente()->for($matricula)->create(['persona_id' => $persona->id]);
    Pago::factory()->rechazado()->for($matricula)->create(['persona_id' => $persona->id]);
    Pago::factory()->anulado()->for($matricula)->create(['persona_id' => $persona->id]);
    Pago::factory()->validado()->for($matricula)->create();
    Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Certificado]))->create(['persona_id' => $persona->id]);

    $pagos = app(PagosValidadosDePersona::class)($persona->id, TipoConcepto::Matricula);

    expect(array_map(fn (PagoValidado $pago): int => $pago->id, $pagos))->toBe([$reciente->id, $antiguo->id])
        ->and($pagos[1])
        ->concepto->toBe('Derecho de matrícula')
        ->numeroOperacion->toBe('OP-001')
        ->monto->toBe('150.00')
        ->and($pagos[1]->fechaPago->toDateString())->toBe('2026-10-01');
});
