<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Actions\AjustarHabilitacion;
use App\Modules\Matricula\Actions\CorregirResultadoDeClasificacion;
use App\Modules\Matricula\Actions\RegistrarResultadoDeClasificacion;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ViewSolicitudClasificacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->cicloV = Ciclo::factory()->for($this->ingles)->create(['numero' => 5, 'nivel' => NivelCurricular::Intermedio]);
    $this->cicloVI = Ciclo::factory()->for($this->ingles)->create(['numero' => 6, 'nivel' => NivelCurricular::Intermedio]);
    $this->persona = Persona::factory()->create();
    $this->evaluador = User::factory()->create(['name' => 'Evaluación Demo'])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoClasificacion::valores()]);
    $this->corrector = User::factory()->create(['name' => 'Coordinación Demo'])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoClasificacion::valores(), ...PermisoMatricula::valores()]);
    $this->solicitud = SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id]);
    app(RegistrarResultadoDeClasificacion::class)($this->solicitud->id, new ResultadoDeClasificacion(CarbonImmutable::parse('2026-10-24'), '78.50', $this->cicloVI->id, 'Buena comprensión'), $this->evaluador);
});

function correccion(int $cicloId, string $puntaje = '58.00'): ResultadoDeClasificacion
{
    return new ResultadoDeClasificacion(CarbonImmutable::parse('2026-10-24'), $puntaje, $cicloId, 'Se sumaron mal las secciones');
}

it('corrects an evaluated result and the enabled cycle it gave, recording who corrected it and why', function (): void {
    app(CorregirResultadoDeClasificacion::class)($this->solicitud->id, correccion($this->cicloV->id), '  Error al sumar el puntaje.  ', $this->corrector);

    expect($this->solicitud->refresh())
        ->estado->toBe(EstadoSolicitudClasificacion::Evaluado)
        ->puntaje->toBe('58.00')
        ->ciclo_asignado_id->toBe($this->cicloV->id)
        ->observaciones->toBe('Se sumaron mal las secciones')
        ->evaluado_por->toBe($this->evaluador->id)
        ->corregido_por->toBe($this->corrector->id)
        ->corregido_en->not->toBeNull()
        ->motivo_correccion->toBe('Error al sumar el puntaje.')
        ->and(Habilitacion::query()->sole())->ciclo_id->toBe($this->cicloV->id)->origen->toBe(OrigenHabilitacion::Clasificacion)->actualizado_por->toBe($this->corrector->id)
        ->and(DB::table('aud_registros')->where(['tabla' => 'mat_solicitudes_clasificacion', 'registro_id' => $this->solicitud->id, 'operacion' => 'UPDATE'])->whereRaw("datos_nuevos->>'motivo_correccion' IS NOT NULL")->exists())->toBeTrue();
});

it('refuses to change the cycle once the student enrolled in it, pointing to the adjustment, but still corrects the score', function (): void {
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => Grupo::factory()->create(['ciclo_id' => $this->cicloVI->id])->id]);

    expect(fn () => app(CorregirResultadoDeClasificacion::class)($this->solicitud->id, correccion($this->cicloV->id), 'Error al sumar el puntaje.', $this->corrector))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El estudiante ya se matriculó en el ciclo asignado: su ciclo se cambia con «Ajustar ciclo habilitado» en Ciclos habilitados.')
        ->and($this->solicitud->refresh())->ciclo_asignado_id->toBe($this->cicloVI->id)->puntaje->toBe('78.50')->corregido_en->toBeNull()
        ->and(Habilitacion::query()->sole()->ciclo_id)->toBe($this->cicloVI->id);

    app(CorregirResultadoDeClasificacion::class)($this->solicitud->id, correccion($this->cicloVI->id, '80.00'), 'Error al sumar el puntaje.', $this->corrector);

    expect($this->solicitud->refresh())->puntaje->toBe('80.00')->ciclo_asignado_id->toBe($this->cicloVI->id);
});

it('refuses to change the cycle when the enabled cycle no longer comes from this placement', function (): void {
    app(AjustarHabilitacion::class)($this->persona->id, $this->ingles->id, $this->cicloV->id, 'Reubicado tras entrevista', $this->corrector);

    expect(fn () => app(CorregirResultadoDeClasificacion::class)($this->solicitud->id, correccion($this->cicloV->id), 'Error al sumar el puntaje.', $this->corrector))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El ciclo habilitado ya no proviene de esta clasificación: use «Ajustar ciclo habilitado» en Ciclos habilitados.')
        ->and($this->solicitud->refresh()->ciclo_asignado_id)->toBe($this->cicloVI->id)
        ->and(Habilitacion::query()->sole())->ciclo_id->toBe($this->cicloV->id)->origen->toBe(OrigenHabilitacion::AjusteAdministrativo);
});

it('refuses a correction of a pending request, without the permission or a reason, or with an invalid result', function (): void {
    $pendiente = SolicitudClasificacion::factory()->create(['idioma_id' => $this->ingles->id]);
    $otroIdioma = Ciclo::factory()->create();
    $lector = User::factory()->create()->givePermissionTo(PermisoClasificacion::Ver->value);
    $corregir = fn (int $id, ResultadoDeClasificacion $resultado, string $motivo, User $usuario): Closure => fn () => app(CorregirResultadoDeClasificacion::class)($id, $resultado, $motivo, $usuario);

    expect($corregir($pendiente->id, correccion($this->cicloV->id), 'Error al sumar el puntaje.', $this->corrector))->toThrow(ReglaDeNegocioIncumplida::class, 'Solo se corrige el resultado de una solicitud evaluada.')
        ->and($corregir($this->solicitud->id, correccion($this->cicloV->id), 'Error al sumar el puntaje.', $lector))->toThrow(ReglaDeNegocioIncumplida::class, 'No tiene permiso para corregir resultados de clasificación.')
        ->and($corregir($this->solicitud->id, correccion($this->cicloV->id), 'Mal', $this->corrector))->toThrow(ReglaDeNegocioIncumplida::class, 'Indique el motivo de la corrección, entre 5 y 500 caracteres.')
        ->and($corregir($this->solicitud->id, correccion($this->cicloV->id, '100.50'), 'Error al sumar el puntaje.', $this->corrector))->toThrow(ReglaDeNegocioIncumplida::class, 'El puntaje va de 0 a 100, con hasta dos decimales.')
        ->and($corregir($this->solicitud->id, correccion($otroIdioma->id), 'Error al sumar el puntaje.', $this->corrector))->toThrow(ReglaDeNegocioIncumplida::class, 'El ciclo asignado no pertenece a Inglés.')
        ->and($this->solicitud->refresh())->puntaje->toBe('78.50')->corregido_en->toBeNull()
        ->and($pendiente->refresh()->estado)->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion);
});

it('corrects an evaluated result from the request page, prefilled, and shows the correction', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoClasificacion::Ver->value]));
    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])->assertActionHidden('corregirResultado');

    $this->actingAs($this->corrector);
    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])
        ->assertActionHidden('registrarResultado')
        ->mountAction('corregirResultado')
        ->assertSchemaStateSet(['puntaje' => '78.50', 'ciclo_id' => $this->cicloVI->id, 'observaciones' => 'Buena comprensión'])
        ->setActionData(['puntaje' => '58.00', 'ciclo_id' => $this->cicloV->id, 'motivo' => 'Error al sumar el puntaje.'])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified('Resultado corregido');

    expect($this->solicitud->refresh())->puntaje->toBe('58.00')->ciclo_asignado_id->toBe($this->cicloV->id);

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])
        ->assertSeeText('Coordinación Demo')
        ->assertSeeText('Error al sumar el puntaje.');
});
