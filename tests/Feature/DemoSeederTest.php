<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds consistent demo data for local development', function (): void {
    $this->seed(DatabaseSeeder::class);

    $grupo = Grupo::query()->orderBy('id')->firstOrFail();

    expect(Ciclo::query()->count())->toBe(24)
        ->and($grupo->matriculados)->toBe(Matricula::query()->where('grupo_id', $grupo->id)->count())
        ->and($grupo->matriculados)->toBe(10)
        ->and(Matricula::query()->whereNull('pago_id')->exists())->toBeFalse()
        ->and(Pago::query()->where('estado', EstadoPago::Validado)->count())->toBe(10)
        ->and(User::query()->where('email', 'admin@ciln.test')->firstOrFail()->can(User::ADMIN_PANEL_PERMISSION))->toBeTrue();
});

it('seeds a demo administrator with every payment permission and a cashier who cannot void', function (): void {
    $this->seed(DatabaseSeeder::class);

    $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
    $caja = User::query()->where('email', 'caja@ciln.test')->firstOrFail();

    expect($administrador->getPermissionNames()->sort()->values()->all())->toBe(['admin-panel.access', ...collect(PermisoPago::valores())->sort()->values()->all()])
        ->and($caja->getPermissionNames()->sort()->values()->all())
        ->toBe(['admin-panel.access', PermisoPago::Registrar->value, PermisoPago::Validar->value, PermisoPago::Ver->value]);
});

it('refuses to seed demo data in production even when forced', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    app(DatabaseSeeder::class)->run();
})->throws(RuntimeException::class, 'Demo data must never be seeded in production.');
