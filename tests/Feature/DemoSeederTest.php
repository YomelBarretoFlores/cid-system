<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds consistent demo data for local development', function (): void {
    $this->seed(DatabaseSeeder::class);

    $grupo = Grupo::query()->orderBy('id')->firstOrFail();

    expect(Ciclo::query()->count())->toBe(24)
        ->and($grupo->matriculados)->toBe(Matricula::query()->where('grupo_id', $grupo->id)->count())
        ->and($grupo->matriculados)->toBe(10)
        ->and(Matricula::query()->whereNull('pago_id')->exists())->toBeFalse()
        ->and(Pago::query()->where('estado', EstadoPago::Validado)->whereIn('id', Matricula::query()->select('pago_id'))->count())->toBe(10)
        ->and(User::query()->where('email', 'admin@ciln.test')->firstOrFail()->can(User::ADMIN_PANEL_PERMISSION))->toBeTrue()
        ->and(app(DocentesDeGrupos::class)([$grupo->id]))->toBe([$grupo->id => 'Docente Demo, Ana']);
});

it('seeds a demo administrator with every payment permission and a cashier who cannot void', function (): void {
    $this->seed(DatabaseSeeder::class);

    $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
    $caja = User::query()->where('email', 'caja@ciln.test')->firstOrFail();

    expect($administrador->getPermissionNames()->sort()->values()->all())->toBe(collect(['admin-panel.access', ...PermisoPago::valores(), ...PermisoAcademico::valores(), ...PermisoPersona::valores(), ...PermisoMatricula::valores(), ...PermisoClasificacion::valores(), ...PermisoDocencia::valores()])->sort()->values()->all())
        ->and($caja->getPermissionNames()->sort()->values()->all())
        ->toBe(collect(['admin-panel.access', PermisoPago::Registrar->value, PermisoPago::Validar->value, PermisoPago::Ver->value, ...PermisoPersona::valores(), PermisoMatricula::Ver->value])->sort()->values()->all());
});

it('seeds a verified portal student with an unused validated enrollment payment', function (): void {
    $this->seed(DatabaseSeeder::class);

    $estudiante = User::query()->where('email', 'estudiante@ciln.test')->sole();
    $persona = Persona::query()->where('user_id', $estudiante->id)->sole();

    expect($estudiante->hasVerifiedEmail())->toBeTrue()
        ->and($estudiante->can(User::ADMIN_PANEL_PERMISSION))->toBeFalse()
        ->and(Pago::query()->where(['persona_id' => $persona->id, 'estado' => EstadoPago::Validado])->count())->toBe(1)
        ->and(Matricula::query()->where('persona_id', $persona->id)->exists())->toBeFalse();
});

it('seeds a verified portal teacher who has the first demo group', function (): void {
    $this->seed(DatabaseSeeder::class);

    $docente = User::query()->where('email', 'docente@ciln.test')->sole();
    $grupo = Grupo::query()->orderBy('id')->firstOrFail();

    expect($docente->hasVerifiedEmail())->toBeTrue()
        ->and($docente->can(User::ADMIN_PANEL_PERMISSION))->toBeFalse();

    $this->actingAs($docente)->get(route('docente.grupos'))->assertOk()->assertSee("Grupo {$grupo->codigo}");
});

it('refuses to seed demo data in production even when forced', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    app(DatabaseSeeder::class)->run();
})->throws(RuntimeException::class, 'Demo data must never be seeded in production.');
