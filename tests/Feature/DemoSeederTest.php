<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

it('seeds consistent demo data for local development', function (): void {
    $this->seed(DatabaseSeeder::class);

    $grupo = Grupo::query()->orderBy('id')->firstOrFail();
    $activasPorGrupo = Matricula::query()->where('estado', 'activa')->selectRaw('grupo_id, count(*) as total')->groupBy('grupo_id')->pluck('total', 'grupo_id');

    expect(Ciclo::query()->count())->toBe(60)
        ->and(Grupo::query()->get()->every(fn (Grupo $cadaGrupo): bool => $cadaGrupo->matriculados === (int) ($activasPorGrupo[$cadaGrupo->id] ?? 0)))->toBeTrue()
        ->and(Matricula::query()->whereNull('pago_id')->exists())->toBeFalse()
        ->and(Pago::query()->whereIn('id', Matricula::query()->select('pago_id'))->where('estado', '!=', EstadoPago::Validado)->exists())->toBeFalse()
        ->and(User::query()->where('email', 'admin@ciln.test')->firstOrFail()->can(User::ADMIN_PANEL_PERMISSION))->toBeTrue()
        ->and(app(DocentesDeGrupos::class)([$grupo->id]))->toBe([$grupo->id => 'Docente Demo, Ana']);
});

it('dates the demo around today in Lima so the closed flows show data', function (): void {
    $this->seed(DatabaseSeeder::class);
    $hoy = CarbonImmutable::today('America/Lima')->toDateString();

    expect(Periodo::query()->where('estado', 'en_curso')->whereDate('fecha_inicio', '<=', $hoy)->whereDate('fecha_fin', '>=', $hoy)->count())->toBe(1)
        ->and(Periodo::query()->where('estado', 'matricula_abierta')->whereDate('matricula_inicio', '<=', $hoy)->whereDate('matricula_fin', '>=', $hoy)->count())->toBe(1)
        ->and(Pago::query()->whereDate('fecha_pago', '>', $hoy)->exists())->toBeFalse()
        ->and(Pago::query()->where('estado', EstadoPago::Pendiente)->exists())->toBeTrue()
        ->and(ClaseDictada::query()->whereDate('fecha', '>', $hoy)->exists())->toBeFalse()
        ->and(SolicitudClasificacion::query()->whereNull('pago_id')->exists())->toBeFalse();
});

it('seeds a demo administrator with every payment permission and a cashier who cannot void', function (): void {
    $this->seed(DatabaseSeeder::class);

    $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
    $caja = User::query()->where('email', 'caja@ciln.test')->firstOrFail();

    expect($administrador->getPermissionNames()->sort()->values()->all())->toBe(collect(['admin-panel.access', ...PermisoPago::valores(), ...PermisoAcademico::valores(), ...PermisoPersona::valores(), ...PermisoMatricula::valores(), ...PermisoClasificacion::valores(), ...PermisoDocencia::valores()])->sort()->values()->all())
        ->and($caja->getPermissionNames()->sort()->values()->all())
        ->toBe(collect(['admin-panel.access', PermisoPago::Registrar->value, PermisoPago::Validar->value, PermisoPago::Ver->value, ...PermisoPersona::valores(), PermisoMatricula::Ver->value])->sort()->values()->all());
});

it('seeds a verified portal student with an active enrollment and an unused validated enrollment payment', function (): void {
    $this->seed(DatabaseSeeder::class);

    $estudiante = User::query()->where('email', 'estudiante@ciln.test')->sole();
    $persona = Persona::query()->where('user_id', $estudiante->id)->sole();
    $usados = Matricula::query()->where('persona_id', $persona->id)->pluck('pago_id');

    expect($estudiante->hasVerifiedEmail())->toBeTrue()
        ->and($estudiante->can(User::ADMIN_PANEL_PERMISSION))->toBeFalse()
        ->and(Matricula::query()->where(['persona_id' => $persona->id, 'estado' => 'activa'])->count())->toBe(1)
        ->and(Pago::query()->where(['persona_id' => $persona->id, 'estado' => EstadoPago::Validado])->whereNotIn('id', $usados)->count())->toBe(1);
});

it('seeds a verified portal teacher who has the first demo group', function (): void {
    $this->seed(DatabaseSeeder::class);

    $docente = User::query()->where('email', 'docente@ciln.test')->sole();
    $grupo = Grupo::query()->orderBy('id')->firstOrFail();

    expect($docente->hasVerifiedEmail())->toBeTrue()
        ->and($docente->can(User::ADMIN_PANEL_PERMISSION))->toBeFalse();

    $this->actingAs($docente)->get(route('docente.grupos'))->assertOk()->assertSee("Grupo {$grupo->codigo}");
});

it('seeds a fictitious hourly cost for every teacher category', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(CostoPorHora::query()->whereNull('vigente_hasta')->orderBy('condicion_laboral')->pluck('costo_hora', 'condicion_laboral')->mapWithKeys(fn (string $costo, string $categoria): array => [$categoria => $costo])->all())
        ->toBe(['contratado' => '45.00', 'nombrado' => '50.00', 'tercero' => '40.00']);
});

it('refuses to seed demo data in production even when forced', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    app(DatabaseSeeder::class)->run();
})->throws(RuntimeException::class, 'Demo data must never be seeded in production.');
