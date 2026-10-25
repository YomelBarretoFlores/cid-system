<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ListTramites;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ViewTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\TramiteResource;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->personal = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value, PermisoCertificacion::Documentos->value]);
    $this->persona = Persona::factory()->create(['apellido_paterno' => 'Ramírez', 'nombres' => 'Lucía']);
    $this->matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]);
    Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Constancia]))->create(['persona_id' => $this->persona->id, 'numero_operacion' => 'OP-7788']);
    $this->tramite = app(SolicitarTramite::class)($this->persona->id, TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->value('id'), new DatosDeSolicitud(matriculaId: $this->matricula->id, comentario: 'Para una beca.'))->refresh();
});

it('keeps the trámite inbox away from staff without the trámite permission', function (): void {
    $cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'pagos.validar']);

    $this->actingAs($cajero)->get(TramiteResource::getUrl('index'))->assertForbidden();
    $this->actingAs($cajero)->get(TramiteResource::getUrl('view', ['record' => $this->tramite]))->assertForbidden();
});

it('lists trámites with their student, stage and state, filtered by state', function (): void {
    $this->actingAs($this->personal);
    $pendiente = Tramite::factory()->create(['estado' => EstadoTramite::PendientePago]);

    Livewire::test(ListTramites::class)
        ->assertCanSeeTableRecords([$this->tramite, $pendiente])
        ->assertTableColumnFormattedStateSet('etapa_actual', 'Pago validado · 33 %', $this->tramite)
        ->assertTableColumnFormattedStateSet('estado', 'En proceso', $this->tramite)
        ->filterTable('estado', EstadoTramite::EnProceso->value)
        ->assertCanSeeTableRecords([$this->tramite])
        ->assertCanNotSeeTableRecords([$pendiente]);
});

it('shows the request, the applied payment and the history, and moves the trámite to the end from its page', function (): void {
    $this->actingAs($this->personal);

    Livewire::test(ViewTramite::class, ['record' => $this->tramite->getRouteKey()])
        ->assertSeeText('Para una beca.')
        ->assertSeeText('operación OP-7788')
        ->assertSeeText('Solicitud recibida')
        ->assertActionHidden('finalizar')
        ->assertActionVisible('avanzar')
        ->callAction('avanzar')
        ->assertNotified('Trámite avanzado');

    Livewire::test(ViewTramite::class, ['record' => $this->tramite->getRouteKey()])
        ->assertActionHidden('avanzar')
        ->callAction('finalizar')
        ->assertNotified('Trámite finalizado');

    expect($this->tramite->refresh())->estado->toBe(EstadoTramite::Finalizado)->documento_id->not->toBeNull();
});

it('observes a trámite from its page with a counted reason and lifts the observation', function (): void {
    $this->actingAs($this->personal);

    Livewire::test(ViewTramite::class, ['record' => $this->tramite->getRouteKey()])
        ->callAction('observar', ['motivo' => 'La matrícula elegida no corresponde al periodo solicitado.'])
        ->assertNotified('Trámite observado');

    expect($this->tramite->refresh()->estado)->toBe(EstadoTramite::Observado);

    Livewire::test(ViewTramite::class, ['record' => $this->tramite->getRouteKey()])
        ->assertActionHidden('observar')
        ->callAction('reanudar')
        ->assertNotified('Observación levantada');

    expect($this->tramite->refresh()->estado)->toBe(EstadoTramite::EnProceso);
});

it('finishes a transfer from its page with the destination group chosen among those that can receive it', function (): void {
    $this->actingAs($this->personal->givePermissionTo(PermisoMatricula::Gestionar->value));
    $periodo = Periodo::factory()->conMatriculaAbierta()->create();
    $ciclo = Ciclo::factory()->create(['numero' => 1]);
    [$origen, $destino] = Grupo::factory()->count(2)->for($periodo)->for($ciclo)->create(['cupo_maximo' => 3, 'cupo_minimo' => 1])->all();
    $matricula = app(MatricularEstudiante::class)($this->persona->id, $origen->id, Pago::factory()->validado()->for(Concepto::factory())->create(['persona_id' => $this->persona->id])->id);
    Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Tramite]))->create(['persona_id' => $this->persona->id]);
    $traslado = app(SolicitarTramite::class)($this->persona->id, TipoTramite::query()->where('codigo', 'TRASLADO')->value('id'), new DatosDeSolicitud(matriculaId: $matricula->id, motivo: 'Cambio de turno en el trabajo'));
    app(AvanzarTramite::class)($traslado->id, $this->personal);

    Livewire::test(ViewTramite::class, ['record' => $traslado->getRouteKey()])
        ->callAction('finalizar', ['grupo_destino' => $destino->id])
        ->assertHasNoActionErrors()
        ->assertNotified('Trámite finalizado');

    expect($matricula->refresh()->grupo_id)->toBe($destino->id);
});
