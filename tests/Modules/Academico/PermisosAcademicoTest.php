<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;

it('creates the academic and people permissions with the migrations', function (): void {
    expect(DB::table('permissions')->where('guard_name', 'web')->where(fn ($q) => $q->whereLike('name', 'academico.%')->orWhereLike('name', 'personas.%'))->orderBy('name')->pluck('name')->all())
        ->toBe(collect([...PermisoAcademico::valores(), ...PermisoPersona::valores()])->sort()->values()->all());
});

it('lets read-only users see and managers change the academic offer', function (string $modelo): void {
    $registro = $modelo::factory()->create();
    $lector = User::factory()->create()->givePermissionTo(PermisoAcademico::Ver->value);
    $gestor = User::factory()->create()->givePermissionTo(PermisoAcademico::valores());
    $ajeno = User::factory()->create();

    expect([$lector->can('viewAny', $modelo), $lector->can('view', $registro), $lector->can('create', $modelo), $lector->can('update', $registro)])
        ->toBe([true, true, false, false])
        ->and([$gestor->can('create', $modelo), $gestor->can('update', $registro), $gestor->can('delete', $registro)])
        ->toBe([true, true, false])
        ->and($ajeno->can('viewAny', $modelo))->toBeFalse();
})->with([Idioma::class, Ciclo::class, Aula::class, Periodo::class, Grupo::class]);

it('lets managers change the state of periods and groups', function (string $modelo): void {
    $registro = $modelo::factory()->create();

    expect(User::factory()->create()->givePermissionTo(PermisoAcademico::valores())->can('cambiarEstado', $registro))->toBeTrue()
        ->and(User::factory()->create()->givePermissionTo(PermisoAcademico::Ver->value)->can('cambiarEstado', $registro))->toBeFalse();
})->with([Periodo::class, Grupo::class]);

it('lets managers remove a schedule line of a group', function (): void {
    $horario = Horario::factory()->create();

    expect(User::factory()->create()->givePermissionTo(PermisoAcademico::valores())->can('delete', $horario))->toBeTrue()
        ->and(User::factory()->create()->givePermissionTo(PermisoAcademico::Ver->value)->can('delete', $horario))->toBeFalse();
});

it('protects people records with their own permissions and never deletes them', function (): void {
    $persona = Persona::factory()->create();
    $lector = User::factory()->create()->givePermissionTo(PermisoPersona::Ver->value);
    $gestor = User::factory()->create()->givePermissionTo(PermisoPersona::valores());

    expect([$lector->can('viewAny', Persona::class), $lector->can('view', $persona), $lector->can('create', Persona::class), $lector->can('update', $persona)])
        ->toBe([true, true, false, false])
        ->and([$gestor->can('create', Persona::class), $gestor->can('update', $persona), $gestor->can('delete', $persona)])
        ->toBe([true, true, false])
        ->and(User::factory()->create()->givePermissionTo(PermisoAcademico::valores())->can('view', $persona))->toBeFalse();
});
