<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;

it('gives one validated fee to only one trámite when a new request and its validation happen at once', function (): void {
    $persona = Persona::factory()->create();
    // Payments refuse TRUNCATE, so this row stays until the next migrate:fresh.
    $pago = Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Constancia]))->create(['persona_id' => $persona->id]);
    $esperando = Tramite::factory()->for(TipoTramite::factory()->state(['accion_final' => AccionFinal::Ninguna]))->create(['persona_id' => $persona->id, 'estado' => 'pendiente_pago']);
    $nuevo = TipoTramite::factory()->create(['accion_final' => AccionFinal::Ninguna]);

    $resultados = enParalelo('pagar-tramite.php', [['solicitar', $persona->id, $nuevo->id, $pago->id], ['aplicar', $persona->id, $nuevo->id, $pago->id]]);

    expect(Tramite::query()->where('pago_id', $pago->id)->count())->toBe(1)
        ->and(collect($resultados)->filter(fn (string $resultado): bool => str_ends_with($resultado, 'con el pago'))->count())->toBe(1)
        ->and($resultados)->toHaveCount(2)->each->toMatch('/^(solicitar|aplicar): (con|sin) el pago$/')
        ->and($esperando->refresh()->pago_id === $pago->id || Tramite::query()->where('tipo_tramite_id', $nuevo->id)->value('pago_id') === $pago->id)->toBeTrue();
});
