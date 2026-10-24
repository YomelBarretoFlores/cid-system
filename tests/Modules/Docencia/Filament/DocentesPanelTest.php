<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\Docentes\DocenteResource;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\CreateDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\EditDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ListDocentes;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ViewDocente;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoDocencia::valores()]);
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->persona = Persona::factory()->create([
        'numero_documento' => '40123456', 'apellido_paterno' => 'Rímac', 'apellido_materno' => 'Huaylas', 'nombres' => 'Rosa Elena',
        'correo' => 'rosa.personal@example.test', 'celular' => '987654321',
    ]);
    $this->docente = Docente::factory()->create([
        'persona_id' => $this->persona->id, 'correo_institucional' => 'rrimac@unasam.test', 'ruc' => '10401234567',
        'banco' => 'Banco de la Nación', 'cuenta_bancaria' => '04-123-456789', 'condicion_laboral' => CondicionLaboral::Nombrado,
    ]);
    $this->docente->idiomas()->create(['idioma_id' => $this->ingles->id, 'nivel_maximo' => NivelCurricular::Intermedio]);
    $this->undoRepeaterFake = Repeater::fake();
});

afterEach(function (): void {
    ($this->undoRepeaterFake)();
});

function formularioDeDocente(array $cambios = []): array
{
    return [
        'persona_id' => Persona::factory()->create()->id,
        'correo_institucional' => 'nuevo@unasam.test',
        'ruc' => '10777777777',
        'banco' => 'BBVA',
        'cuenta_bancaria' => '0011-0222-0333',
        'condicion_laboral' => CondicionLaboral::Tercero->value,
        'idiomas' => [['idioma_id' => test()->ingles->id, 'nivel_maximo' => NivelCurricular::Basico->value]],
        ...$cambios,
    ];
}

it('keeps the roster away from users without the teaching read permission', function (): void {
    $ajeno = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($ajeno)->get(DocenteResource::getUrl('index'))->assertForbidden();
    $this->actingAs($ajeno)->get(DocenteResource::getUrl('view', ['record' => $this->docente]))->assertForbidden();
});

it('lists teachers with code, names, condition and status, and finds them by name', function (): void {
    $otro = Docente::factory()->create();

    $this->actingAs($this->gestor)->get(DocenteResource::getUrl('index'))
        ->assertOk()
        ->assertSee($this->docente->codigo)
        ->assertSee('Rímac Huaylas, Rosa Elena')
        ->assertSee('Nombrado')
        ->assertSee('Activo')
        ->assertDontSee('04-123-456789');

    Livewire::actingAs($this->gestor)->test(ListDocentes::class)
        ->searchTable('Huaylas')
        ->assertCanSeeTableRecords([$this->docente])
        ->assertCanNotSeeTableRecords([$otro]);
});

it('shows the profile with personal data, a masked account and the enabled languages', function (): void {
    $this->actingAs($this->gestor)->get(DocenteResource::getUrl('view', ['record' => $this->docente]))
        ->assertOk()
        ->assertSee([$this->docente->codigo, 'DNI 40123456', 'rrimac@unasam.test', 'rosa.personal@example.test', '987654321', '10401234567', '•••• 6789', 'Inglés · hasta Intermedio'])
        ->assertDontSee('04-123-456789');
});

it('registers a teacher from the panel', function (): void {
    $datos = formularioDeDocente();

    Livewire::actingAs($this->gestor)->test(CreateDocente::class)
        ->fillForm($datos)
        ->call('create')
        ->assertHasNoFormErrors();

    $docente = Docente::query()->where('persona_id', $datos['persona_id'])->firstOrFail();

    expect([$docente->ruc, $docente->cuenta_bancaria, $docente->condicion_laboral, $docente->idiomas()->count()])
        ->toBe(['10777777777', '0011-0222-0333', CondicionLaboral::Tercero, 1]);
});

it('tells why a teacher cannot be registered and saves nothing', function (): void {
    Livewire::actingAs($this->gestor)->test(CreateDocente::class)
        ->fillForm(formularioDeDocente(['persona_id' => $this->persona->id]))
        ->call('create')
        ->assertNotified('Esta persona ya está registrada como docente.');

    expect(Docente::query()->count())->toBe(1);
});

it('edits the profile without changing the person', function (): void {
    Livewire::actingAs($this->gestor)->test(EditDocente::class, ['record' => $this->docente->getRouteKey()])
        ->assertSchemaStateSet(['ruc' => '10401234567', 'idiomas' => [['idioma_id' => $this->ingles->id, 'nivel_maximo' => 'intermedio']]])
        ->fillForm(['banco' => 'Interbank', 'idiomas' => [['idioma_id' => $this->ingles->id, 'nivel_maximo' => 'avanzado']]])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->docente->refresh();

    expect([$this->docente->persona_id, $this->docente->banco, $this->docente->idiomas()->sole()->nivel_maximo])
        ->toBe([$this->persona->id, 'Interbank', NivelCurricular::Avanzado]);
});

it('changes the status from the profile', function (): void {
    Livewire::actingAs($this->gestor)->test(ViewDocente::class, ['record' => $this->docente->getRouteKey()])
        ->callAction('cambiarEstado', data: ['estado' => EstadoDocente::DeLicencia->value])
        ->assertNotified('Estado del docente actualizado');

    expect($this->docente->refresh()->estado)->toBe(EstadoDocente::DeLicencia);
});

it('lets readers see the profile but not edit it or change its status', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value]);

    Livewire::actingAs($lector)->test(ViewDocente::class, ['record' => $this->docente->getRouteKey()])
        ->assertActionHidden('edit')
        ->assertActionHidden('cambiarEstado')
        ->call('mountAction', 'cambiarEstado')
        ->set('mountedActions.0.data', ['estado' => EstadoDocente::Inactivo->value])
        ->call('callMountedAction');

    expect($this->docente->refresh()->estado)->toBe(EstadoDocente::Activo);

    $this->actingAs($lector)->get(DocenteResource::getUrl('edit', ['record' => $this->docente]))->assertForbidden();
});
