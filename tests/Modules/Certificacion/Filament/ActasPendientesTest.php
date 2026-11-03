<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Filament\Pages\ActasPendientes;
use App\Modules\Certificacion\Filament\Resources\Actas\Pages\ListActas;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-02 10:00', 'America/Lima'));
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Actas->value]);
    $this->periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'estado' => EstadoPeriodo::EnCurso, 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-30', 'actas_inicio' => '2027-03-29', 'actas_fin' => '2027-04-05']);
    $this->ciclo = Ciclo::factory()->for(Idioma::factory()->state(['nombre' => 'Quechua']))->create(['numero' => 2, 'nombre' => 'Básico II']);
});

function grupoDelPeriodo(string $codigo, EstadoGrupo $estado, ?Periodo $periodo = null): Grupo
{
    return Grupo::factory()->create(['codigo' => $codigo, 'ciclo_id' => test()->ciclo->id, 'periodo_id' => ($periodo ?? test()->periodo)->id, 'estado' => $estado]);
}

it('lists the groups of the period in progress that still have no closed acta, with their teacher and acta window', function (): void {
    $sinActa = grupoDelPeriodo('QUE-2-A', EstadoGrupo::Cerrado);
    [, $docente] = PortalDocente::docente();
    Persona::query()->whereKey($docente->persona_id)->update(['apellido_paterno' => 'Vega', 'apellido_materno' => 'Ríos', 'nombres' => 'Carlos']);
    PortalDocente::asignar($sinActa, $docente);
    grupoDelPeriodo('QUE-2-B', EstadoGrupo::Abierto);
    grupoDelPeriodo('QUE-2-P', EstadoGrupo::Programado);
    grupoDelPeriodo('QUE-2-X', EstadoGrupo::Cancelado);
    grupoDelPeriodo('QUE-2-O', EstadoGrupo::Cerrado, Periodo::factory()->create(['nombre' => 'Otro periodo']));
    ActasFinales::historial(Persona::factory()->create(), 'Inglés', [[1, '14.00', 'aprobado']], periodoId: $this->periodo->id);
    $conActa = Grupo::query()->findOrFail(Acta::query()->sole()->grupo_id);
    $conActa->update(['codigo' => 'ING-1-CERRADA', 'estado' => EstadoGrupo::Cerrado]);

    Livewire::actingAs($this->gestor)->test(ActasPendientes::class)
        ->assertOk()
        ->assertSee(['QUE-2-A', 'Quechua', 'Ciclo 2', 'Vega Ríos, Carlos', '29/03/2027 al 05/04/2027', 'Cerrado', 'Sin validar'])
        ->assertSee('QUE-2-B')
        ->assertDontSee(['QUE-2-P', 'QUE-2-X', 'QUE-2-O', 'ING-1-CERRADA']);
});

it('lists the group again while its closed acta is reopened for a rectification', function (): void {
    ActasFinales::historial(Persona::factory()->create(), 'Inglés', [[1, '14.00', 'aprobado']], estadoActa: 'abierta', periodoId: $this->periodo->id);
    Grupo::query()->whereKey(Acta::query()->sole()->grupo_id)->update(['codigo' => 'ING-1-REC', 'estado' => EstadoGrupo::Finalizado]);

    Livewire::actingAs($this->gestor)->test(ActasPendientes::class)
        ->assertSee(['ING-1-REC', 'Finalizado', 'En rectificación']);
});

it('shows the groups of another chosen period', function (): void {
    grupoDelPeriodo('QUE-2-O', EstadoGrupo::Finalizado, Periodo::factory()->create(['nombre' => 'Otro periodo']));

    Livewire::actingAs($this->gestor)->test(ActasPendientes::class)
        ->filterTable('periodo', Periodo::query()->where('nombre', 'Otro periodo')->value('id'))
        ->assertSee('QUE-2-O');
});

it('keeps the pending actas away from staff without the actas permission and links them from the actas list', function (): void {
    $ajeno = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value]);

    $this->actingAs($ajeno)->get(ActasPendientes::getUrl())->assertForbidden();
    $this->actingAs($this->gestor)->get(ActasPendientes::getUrl())->assertOk();
    Livewire::actingAs($this->gestor)->test(ListActas::class)->assertActionHasUrl('pendientes', ActasPendientes::getUrl());
});
