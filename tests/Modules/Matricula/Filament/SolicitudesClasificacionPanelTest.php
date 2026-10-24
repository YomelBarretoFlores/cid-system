<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ListSolicitudesClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ViewSolicitudClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\SolicitudClasificacionResource;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Select;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->coordinador = User::factory()->create(['name' => 'Coordinación Demo'])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoClasificacion::valores()]);
    $this->lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoClasificacion::Ver->value]);

    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->cicloVI = Ciclo::factory()->for($this->ingles)->create(['numero' => 6, 'nivel' => NivelCurricular::Intermedio]);
    $this->frances = Idioma::factory()->create(['nombre' => 'Francés']);
    $this->cicloFrances = Ciclo::factory()->for($this->frances)->create(['numero' => 2]);

    $this->persona = Persona::factory()->create(['numero_documento' => '41234567', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa']);
    $this->solicitud = SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id]);
});

function panelClasificacionResultado(array $cambios = []): array
{
    return ['fecha_examen' => '2026-10-24', 'puntaje' => '78.50', 'ciclo_id' => test()->cicloVI->id, 'observaciones' => 'Buena comprensión lectora', ...$cambios];
}

function panelClasificacionSinCambios(): void
{
    expect(test()->solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion)
        ->and(test()->solicitud->puntaje)->toBeNull()
        ->and(Habilitacion::query()->count())->toBe(0);
}

it('keeps the placement requests away from users without the consult permission', function (): void {
    $cajero = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($cajero)->get(SolicitudClasificacionResource::getUrl('index'))->assertForbidden();
    $this->actingAs($this->lector)->get(SolicitudClasificacionResource::getUrl('index'))->assertOk();
});

it('lists placement requests with the student and language and finds them by the student', function (): void {
    $ajena = SolicitudClasificacion::factory()->create(['idioma_id' => $this->frances->id]);
    $this->actingAs($this->lector);

    Livewire::test(ListSolicitudesClasificacion::class)
        ->assertCanSeeTableRecords([$this->solicitud, $ajena])
        ->assertSee(['DNI 41234567 · Quispe Mamani, Rosa', 'Inglés', 'Pendiente de evaluación', '23/11/2026'])
        ->searchTable('41234567')
        ->assertCanSeeTableRecords([$this->solicitud])
        ->assertCanNotSeeTableRecords([$ajena]);
});

it('filters placement requests by state and language', function (): void {
    $evaluada = SolicitudClasificacion::factory()->evaluada()->create(['idioma_id' => $this->frances->id, 'ciclo_asignado_id' => $this->cicloFrances->id]);
    $this->actingAs($this->lector);

    Livewire::test(ListSolicitudesClasificacion::class)
        ->filterTable('estado', EstadoSolicitudClasificacion::Evaluado->value)
        ->assertCanSeeTableRecords([$evaluada])
        ->assertCanNotSeeTableRecords([$this->solicitud])
        ->resetTableFilters()
        ->filterTable('idioma_id', $this->ingles->id)
        ->assertCanSeeTableRecords([$this->solicitud])
        ->assertCanNotSeeTableRecords([$evaluada]);
});

it('shows the result of an evaluated placement request', function (): void {
    $this->solicitud->update([
        'estado' => EstadoSolicitudClasificacion::Evaluado, 'fecha_examen' => '2026-10-24', 'puntaje' => '81.25',
        'ciclo_asignado_id' => $this->cicloVI->id, 'observaciones' => 'Buena comprensión lectora',
        'evaluado_por' => $this->coordinador->id, 'evaluado_en' => now(),
    ]);
    $this->actingAs($this->lector);

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])
        ->assertSee(['Quispe Mamani, Rosa', 'Inglés', 'Evaluado', '24/10/2026', '81.25', 'Ciclo 6 (Intermedio)', 'Buena comprensión lectora', 'Coordinación Demo']);
});

it('records the result and enables the assigned cycle for the student', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])
        ->callAction('registrarResultado', data: panelClasificacionResultado())
        ->assertHasNoFormErrors()
        ->assertNotified('Resultado registrado');

    $solicitud = $this->solicitud->refresh();
    $habilitacion = Habilitacion::query()->sole();

    expect($solicitud->estado)->toBe(EstadoSolicitudClasificacion::Evaluado)
        ->and($solicitud->fecha_examen->toDateString())->toBe('2026-10-24')
        ->and($solicitud->puntaje)->toBe('78.50')
        ->and($solicitud->ciclo_asignado_id)->toBe($this->cicloVI->id)
        ->and($solicitud->observaciones)->toBe('Buena comprensión lectora')
        ->and($solicitud->evaluado_por)->toBe($this->coordinador->id)
        ->and($habilitacion->persona_id)->toBe($this->persona->id)
        ->and($habilitacion->idioma_id)->toBe($this->ingles->id)
        ->and($habilitacion->ciclo_id)->toBe($this->cicloVI->id)
        ->and($habilitacion->origen)->toBe(OrigenHabilitacion::Clasificacion)
        ->and($habilitacion->actualizado_por)->toBe($this->coordinador->id);
});

it('offers only the cycles of the requested language', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(ListSolicitudesClasificacion::class)
        ->mountAction(TestAction::make('registrarResultado')->table($this->solicitud))
        ->assertFormFieldExists('ciclo_id', fn (Select $campo): bool => $campo->getOptions() === [$this->cicloVI->id => 'Ciclo 6 (Intermedio)'])
        ->fillForm(panelClasificacionResultado(['ciclo_id' => $this->cicloFrances->id]))
        ->callMountedAction()
        ->assertHasFormErrors(['ciclo_id']);

    panelClasificacionSinCambios();
});

it('shows the business refusal of a result and changes nothing', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])
        ->callAction('registrarResultado', data: panelClasificacionResultado(['fecha_examen' => '2026-10-23']))
        ->assertNotified('La fecha del examen debe estar entre el 24/10/2026 y el 24/10/2026.');

    panelClasificacionSinCambios();
});

it('offers recording a result only to evaluators and only for pending requests', function (): void {
    $evaluada = SolicitudClasificacion::factory()->evaluada()->create(['idioma_id' => $this->ingles->id, 'ciclo_asignado_id' => $this->cicloVI->id]);
    $expirada = SolicitudClasificacion::factory()->create(['idioma_id' => $this->ingles->id, 'estado' => EstadoSolicitudClasificacion::Expirado]);

    $this->actingAs($this->lector);
    Livewire::test(ListSolicitudesClasificacion::class)
        ->assertActionHidden(TestAction::make('registrarResultado')->table($this->solicitud));

    $this->actingAs($this->coordinador);
    Livewire::test(ListSolicitudesClasificacion::class)
        ->assertActionVisible(TestAction::make('registrarResultado')->table($this->solicitud))
        ->assertActionHidden(TestAction::make('registrarResultado')->table($evaluada))
        ->assertActionHidden(TestAction::make('registrarResultado')->table($expirada));
});
