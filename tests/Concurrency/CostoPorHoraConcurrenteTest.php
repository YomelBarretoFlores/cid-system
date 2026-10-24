<?php

declare(strict_types=1);

use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('queues two successive costs of one category registered at the same time and keeps both', function (): void {
    $usuario = User::factory()->create();
    $hoy = CarbonImmutable::today('America/Lima');
    CostoPorHora::query()->create(['condicion_laboral' => 'contratado', 'costo_hora' => '40.00', 'vigente_desde' => $hoy->subYear(), 'registrado_por' => $usuario->id]);

    $salidas = enParalelo('registrar-costo-por-hora.php', [
        ['primero', $hoy->addMonth()->toDateString(), '45.00', $usuario->id],
        ['segundo', $hoy->addMonths(2)->toDateString(), '50.00', $usuario->id],
    ]);

    $superpuestos = DB::selectOne("select count(*) as total from doc_costos_hora a join doc_costos_hora b on a.id < b.id and a.condicion_laboral = b.condicion_laboral and daterange(a.vigente_desde, a.vigente_hasta, '[]') && daterange(b.vigente_desde, b.vigente_hasta, '[]')")->total;

    expect($salidas)->toBe(['primero: hecho', 'segundo: hecho'])
        ->and(CostoPorHora::query()->orderBy('vigente_desde')->pluck('costo_hora')->all())->toBe(['40.00', '45.00', '50.00'])
        ->and(CostoPorHora::query()->whereNull('vigente_hasta')->sole()->costo_hora)->toBe('50.00')
        ->and($superpuestos)->toBe(0);
});
