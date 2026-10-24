<?php

declare(strict_types=1);

use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;

it('keeps one open cost per category when two new costs are registered at the same time', function (): void {
    $usuario = User::factory()->create();
    CostoPorHora::query()->create(['condicion_laboral' => 'contratado', 'costo_hora' => '40.00', 'vigente_desde' => '2027-01-01', 'registrado_por' => $usuario->id]);

    $salidas = enParalelo('registrar-costo-por-hora.php', [['2027-06-01', '45.00', $usuario->id], ['2027-07-01', '50.00', $usuario->id]]);

    $superpuestos = DB::selectOne("select count(*) as total from doc_costos_hora a join doc_costos_hora b on a.id < b.id and a.condicion_laboral = b.condicion_laboral and daterange(a.vigente_desde, a.vigente_hasta, '[]') && daterange(b.vigente_desde, b.vigente_hasta, '[]')")->total;

    expect(collect($salidas)->filter(fn (string $salida): bool => str_ends_with($salida, ': hecho'))->count())->toBe(1)
        ->and(collect($salidas)->contains(fn (string $salida): bool => str_ends_with($salida, 'La vigencia se superpone con otro costo por hora de esta categoría.')))->toBeTrue()
        ->and(CostoPorHora::query()->count())->toBe(2)
        ->and(CostoPorHora::query()->whereNull('vigente_hasta')->count())->toBe(1)
        ->and($superpuestos)->toBe(0);
});
