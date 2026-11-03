<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\AjustarHabilitacion;
use App\Modules\Matricula\Actions\HabilitarCiclosSiguientes;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Resources\Habilitaciones\HabilitacionResource;
use App\Modules\Matricula\Filament\Resources\Habilitaciones\Pages\ListHabilitaciones;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->ingles = ActasFinales::planDeEstudios('Inglés', [2, 2, 2]);
    $this->ciclos = Ciclo::query()->where('idioma_id', $this->ingles->id)->orderBy('numero')->pluck('id', 'numero')->all();
    $this->persona = Persona::factory()->create(['apellido_paterno' => 'Quispe', 'nombres' => 'Rosa']);
    $this->gestor = User::factory()->create(['name' => 'Coordinación Demo'])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoMatricula::valores()]);
});

it('enables a continuing student in the chosen cycle with the reason, so the student can enroll in it', function (): void {
    $habilitacion = app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->ciclos[5], '  Estudiante del sistema anterior: aprobó hasta el ciclo 4.  ', $this->gestor);

    expect($habilitacion->refresh())
        ->ciclo_id->toBe($this->ciclos[5])
        ->origen->toBe(OrigenHabilitacion::AjusteAdministrativo)
        ->motivo->toBe('Estudiante del sistema anterior: aprobó hasta el ciclo 4.')
        ->actualizado_por->toBe($this->gestor->id)
        ->and(DB::table('aud_registros')->where(['tabla' => 'mat_habilitaciones', 'registro_id' => $habilitacion->id, 'operacion' => 'INSERT'])->exists())->toBeTrue();

    $grupo = Grupo::factory()->for(Periodo::factory()->conMatriculaAbierta())->create(['ciclo_id' => $this->ciclos[5], 'cupo_maximo' => 3, 'cupo_minimo' => 1]);
    $pago = Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Matricula]))->create(['persona_id' => $this->persona->id]);

    expect(app(MatricularEstudiante::class)($this->persona->id, $grupo->id, $pago->id)->grupo_id)->toBe($grupo->id);
});

it('replaces the enabled cycle a placement gave, keeping one row per language', function (): void {
    Habilitacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id, 'ciclo_id' => $this->ciclos[3]]);

    app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->ciclos[4], 'Reubicado tras entrevista de nivelación', $this->gestor);

    expect(Habilitacion::query()->sole())->ciclo_id->toBe($this->ciclos[4])->origen->toBe(OrigenHabilitacion::AjusteAdministrativo);
});

it('refuses an adjustment without the permission, without a reason or with a cycle of another language', function (): void {
    $otroIdioma = Ciclo::factory()->for(Idioma::factory())->create();
    $lector = User::factory()->create()->givePermissionTo(PermisoMatricula::Ver->value);

    expect(fn () => app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->ciclos[2], 'Reubicado tras entrevista', $lector))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No tiene permiso para ajustar el ciclo habilitado.')
        ->and(fn () => app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->ciclos[2], 'Ok', $this->gestor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Indique el motivo del ajuste, entre 5 y 500 caracteres.')
        ->and(fn () => app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $otroIdioma->id, 'Reubicado tras entrevista', $this->gestor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El ciclo elegido no pertenece a Inglés.')
        ->and(Habilitacion::query()->count())->toBe(0);
});

it('asks to confirm enabling a cycle the student already passed or an earlier one, but not a later one', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '16.00', 'aprobado'], [3, '08.00', 'desaprobado']]);

    expect(fn () => app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->ciclos[2], 'Debe repetir por cambio de plan', $this->gestor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El estudiante ya aprobó el ciclo 2 (Básico) de Inglés. Para habilitarle ese ciclo o uno anterior hay que confirmarlo.')
        ->and(Habilitacion::query()->count())->toBe(0);

    app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->ciclos[3], 'Repite el ciclo desaprobado', $this->gestor);
    expect(Habilitacion::query()->sole()->ciclo_id)->toBe($this->ciclos[3]);

    app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->ciclos[1], 'Debe repetir por cambio de plan', $this->gestor, confirmarDescenso: true);
    expect(Habilitacion::query()->sole()->ciclo_id)->toBe($this->ciclos[1]);
});

it('still moves an adjusted student forward when a cycle is passed, dropping the adjustment reason', function (): void {
    app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->ciclos[3], 'Estudiante del sistema anterior', $this->gestor);
    $matricula = Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => Grupo::factory()->create(['ciclo_id' => $this->ciclos[3]])->id]);

    app(HabilitarCiclosSiguientes::class)([$matricula->id]);

    expect(Habilitacion::query()->sole())->ciclo_id->toBe($this->ciclos[4])->origen->toBe(OrigenHabilitacion::Aprobacion)->motivo->toBeNull();
});

it('lists the enabled cycles to enrollment staff and lets only managers adjust them from the panel', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoMatricula::Ver->value]);
    $cajero = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($cajero)->get(HabilitacionResource::getUrl('index'))->assertForbidden();
    $this->actingAs($lector)->get(HabilitacionResource::getUrl('index'))->assertOk();
    Livewire::test(ListHabilitaciones::class)->assertActionHidden('ajustar');

    $this->actingAs($this->gestor);
    Livewire::test(ListHabilitaciones::class)
        ->assertActionVisible('ajustar')
        ->callAction('ajustar', ['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id, 'ciclo_id' => $this->ciclos[5], 'motivo' => 'Estudiante del sistema anterior'])
        ->assertHasNoActionErrors()
        ->assertNotified('Ciclo habilitado ajustado');

    $habilitacion = Habilitacion::query()->sole();
    expect($habilitacion)->ciclo_id->toBe($this->ciclos[5])->actualizado_por->toBe($this->gestor->id);

    Livewire::test(ListHabilitaciones::class)
        ->assertCanSeeTableRecords([$habilitacion])
        ->assertSeeText('Quispe')
        ->assertSeeText('Ajuste administrativo')
        ->assertSeeText('Estudiante del sistema anterior')
        ->assertSeeText('Coordinación Demo');
});

it('explains in the panel why lowering below a passed cycle needs the confirmation', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado']]);
    $this->actingAs($this->gestor);
    $datos = ['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id, 'ciclo_id' => $this->ciclos[1], 'motivo' => 'Debe repetir por cambio de plan'];

    Livewire::test(ListHabilitaciones::class)
        ->callAction('ajustar', $datos)
        ->assertNotified('El estudiante ya aprobó el ciclo 1 (Básico) de Inglés. Para habilitarle ese ciclo o uno anterior hay que confirmarlo.');

    expect(Habilitacion::query()->count())->toBe(0);

    Livewire::test(ListHabilitaciones::class)
        ->callAction('ajustar', [...$datos, 'confirmar_descenso' => true])
        ->assertNotified('Ciclo habilitado ajustado');

    expect(Habilitacion::query()->sole()->ciclo_id)->toBe($this->ciclos[1]);
});
