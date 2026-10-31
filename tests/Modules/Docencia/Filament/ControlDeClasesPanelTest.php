<?php

declare(strict_types=1);

use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Pages\ControlDeClases;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    Persona::query()->whereKey($this->docente->persona_id)->update(['apellido_paterno' => 'Ramos', 'apellido_materno' => 'Vega', 'nombres' => 'Julia']);
    [$this->grupo, $estudiantes] = PortalDocente::grupoConEstudiantes($this->docente, 'Quispe', 'Álvarez');
    Persona::query()->whereKey($estudiantes[0]->persona_id)->update(['apellido_materno' => 'Rojas', 'nombres' => 'Ana']);
    $this->clases = PortalDocente::clasesEnCadaEstado($this->grupo, $this->docente, $this->usuario, $estudiantes);

    [$otroUsuario, $this->otroDocente] = PortalDocente::docente();
    [$this->otroGrupo, [$luis]] = PortalDocente::grupoConEstudiantes($this->otroDocente, 'Zapata');
    $this->otraClase = PortalDocente::dictarClase($this->otroGrupo, $this->otroDocente, $otroUsuario, [$luis->id => 'presente'], null, '2027-03-01');

    $this->travelTo(CarbonImmutable::parse('2027-03-20 12:00', 'America/Lima'));
    $this->coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value]);
});

it('keeps the class control away from staff without the teaching view permission', function (): void {
    $sinPermiso = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Reportar->value]);

    $this->actingAs($sinPermiso)->get(ControlDeClases::getUrl())->assertForbidden();
});

it('lists every class with its schedule, group, teacher, attendance at the close, state and the delegate review', function (): void {
    $this->actingAs($this->coordinador)
        ->get(ControlDeClases::getUrl())
        ->assertOk()
        ->assertSeeInOrder(['Control de clases', '17/03/2027', '15/03/2027', 'registro posterior', '10/03/2027', 'sin llamado de cierre', '08/03/2027', 'sin confirmar', '03/03/2027', 'observada', 'Quispe Rojas, Ana', 'El docente llegó a las 19:30.', '01/03/2027', '18:00 – 20:00', 'ING-01-A', 'Ramos Vega, Julia', '2 / 2', 'confirmada', 'Quispe Rojas, Ana']);
});

it('filters the classes by state, with sin confirmar computed from the time', function (EstadoDeClase $estado, string $clave): void {
    $this->actingAs($this->coordinador);

    Livewire::test(ControlDeClases::class)
        ->filterTable('estado', $estado->value)
        ->assertCanSeeTableRecords([$this->clases[$clave]])
        ->assertCanNotSeeTableRecords(collect($this->clases)->except($clave)->values()->all())
        ->assertCanNotSeeTableRecords([$this->otraClase]);
})->with([
    [EstadoDeClase::Confirmada, 'confirmada'],
    [EstadoDeClase::Observada, 'observada'],
    [EstadoDeClase::SinConfirmar, 'pendiente'],
    [EstadoDeClase::RegistroPosterior, 'posterior'],
    [EstadoDeClase::RegistroAnterior, 'anterior'],
]);

it('shows a class waiting for the delegate as por confirmar until the window closes', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-08 21:00', 'America/Lima'));
    $this->actingAs($this->coordinador);

    Livewire::test(ControlDeClases::class)
        ->filterTable('estado', EstadoDeClase::PorConfirmar->value)
        ->assertCanSeeTableRecords([$this->clases['pendiente']])
        ->assertCanNotSeeTableRecords([$this->clases['confirmada'], $this->clases['observada']]);
});

it('filters the classes by teacher, group and period', function (string $filtro, Closure $valor, bool $delOtro): void {
    $this->actingAs($this->coordinador);

    $pagina = Livewire::test(ControlDeClases::class)->filterTable($filtro, $valor->call($this));

    $delOtro
        ? $pagina->assertCanSeeTableRecords([$this->otraClase])->assertCanNotSeeTableRecords(array_values($this->clases))
        : $pagina->assertCanSeeTableRecords(array_values($this->clases))->assertCanNotSeeTableRecords([$this->otraClase]);
})->with([
    'teacher' => ['docente', fn (): int => $this->otroDocente->id, true],
    'group' => ['grupo', fn (): int => $this->otroGrupo->id, true],
    'period' => ['periodo', fn (): int => $this->grupo->periodo_id, false],
]);
