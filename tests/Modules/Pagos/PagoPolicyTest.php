<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;

it('creates the payment permissions with the migrations', function (): void {
    expect(DB::table('permissions')->where('guard_name', 'web')->whereLike('name', 'pagos.%')->orderBy('name')->pluck('name')->all())
        ->toBe(collect(PermisoPago::valores())->sort()->values()->all());
});

it('grants each payment ability only with its permission', function (string $habilidad, bool $conRegistro, PermisoPago $permiso): void {
    $pago = Pago::factory()->create();
    $argumentos = $conRegistro ? [$habilidad, $pago] : [$habilidad, Pago::class];
    $sinPermiso = User::factory()->create();
    $otrosPermisos = User::factory()->create()->givePermissionTo(array_values(array_diff(PermisoPago::valores(), [$permiso->value])));
    $conPermiso = User::factory()->create()->givePermissionTo($permiso->value);

    expect($sinPermiso->can(...$argumentos))->toBeFalse()
        ->and($otrosPermisos->can(...$argumentos))->toBeFalse()
        ->and($conPermiso->can(...$argumentos))->toBeTrue();
})->with([
    'list' => ['viewAny', false, PermisoPago::Ver],
    'view' => ['view', true, PermisoPago::Ver],
    'register' => ['create', false, PermisoPago::Registrar],
    'validate' => ['validar', true, PermisoPago::Validar],
    'reject' => ['rechazar', true, PermisoPago::Validar],
    'void' => ['anular', true, PermisoPago::Anular],
    'report' => ['reportar', false, PermisoPago::Reportar],
]);

it('never lets anyone edit or delete a payment from the screens', function (string $habilidad): void {
    $usuario = User::factory()->create()->givePermissionTo(PermisoPago::valores());

    expect($usuario->can($habilidad, Pago::factory()->create()))->toBeFalse();
})->with(['update', 'delete', 'forceDelete', 'restore']);
