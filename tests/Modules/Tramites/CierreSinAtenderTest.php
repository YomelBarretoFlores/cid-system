<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Actions\AnularPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\CerrarTramiteSinAtender;
use App\Modules\Tramites\Actions\FinalizarTramite;
use App\Modules\Tramites\Actions\ObservarTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ViewTramite;
use App\Modules\Tramites\Livewire\MisTramites;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id]);
    $this->matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]);
    $this->personal = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value, PermisoMatricula::Gestionar->value, PermisoCertificacion::Documentos->value]);
});

function solicitudDeTramite(Persona $persona, Matricula $matricula, string $codigo): Tramite
{
    return app(SolicitarTramite::class)(
        $persona->id,
        TipoTramite::query()->where('codigo', $codigo)->value('id'),
        new DatosDeSolicitud(matriculaId: $matricula->id, motivo: 'Cambio de horario de trabajo'),
    )->refresh();
}

it('closes an observed withdrawal without withdrawing the enrollment, recording who closed it and why', function (): void {
    $retiro = solicitudDeTramite($this->persona, $this->matricula, 'RETIRO');
    app(ObservarTramite::class)($retiro->id, $this->personal, 'Falta la firma del apoderado');
    Event::fake([TramiteActualizado::class]);

    app(CerrarTramiteSinAtender::class)($retiro->id, $this->personal, '  El estudiante desistió del retiro.  ');

    $ultimo = Movimiento::query()->where('tramite_id', $retiro->id)->latest('id')->first();
    expect($retiro->refresh())
        ->estado->toBe(EstadoTramite::Cerrado)
        ->motivo_cierre->toBe('El estudiante desistió del retiro.')
        ->cerrado_en->not->toBeNull()
        ->observacion->toBeNull()
        ->finalizado_en->toBeNull()
        ->documento_id->toBeNull()
        ->and($ultimo)->estado->toBe(EstadoTramite::Cerrado)->actor_id->toBe($this->personal->id)->comentario->toBe('El estudiante desistió del retiro.')
        ->and($this->matricula->refresh()->estado)->toBe(EstadoMatricula::Activa)
        ->and(Documento::query()->count())->toBe(0)
        ->and(Tramite::query()->count())->toBe(1)
        ->and(EstadoTramite::Cerrado->etiqueta())->toBe('Cerrado sin atender')
        ->and(DB::table('aud_registros')->where(['tabla' => 'tra_tramites', 'registro_id' => $retiro->id, 'operacion' => 'UPDATE'])->whereRaw("datos_nuevos->>'estado' = 'cerrado'")->exists())->toBeTrue();
    Event::assertDispatched(TramiteActualizado::class, fn (TramiteActualizado $evento): bool => $evento->movimientoId === $ultimo->id);
});

it('refuses to close a trámite without a reason, without the permission, or once it has ended, leaving it as it was', function (): void {
    $tramite = solicitudDeTramite($this->persona, $this->matricula, 'RETIRO');
    $sinPermiso = User::factory()->create();

    expect(fn () => app(CerrarTramiteSinAtender::class)($tramite->id, $this->personal, 'No'))->toThrow(ReglaDeNegocioIncumplida::class, 'Indica el motivo del cierre, entre 5 y 500 caracteres.')
        ->and(fn () => app(CerrarTramiteSinAtender::class)($tramite->id, $this->personal, str_repeat('a', 501)))->toThrow(ReglaDeNegocioIncumplida::class, 'Indica el motivo del cierre, entre 5 y 500 caracteres.')
        ->and(fn () => app(CerrarTramiteSinAtender::class)($tramite->id, $sinPermiso, 'Solicitado por error'))->toThrow(ReglaDeNegocioIncumplida::class, 'No tienes permiso para atender trámites.')
        ->and($tramite->refresh()->estado)->toBe(EstadoTramite::EnProceso);

    app(CerrarTramiteSinAtender::class)($tramite->id, $this->personal, 'Solicitado por error');

    expect(fn () => app(CerrarTramiteSinAtender::class)($tramite->id, $this->personal, 'Otra vez'))->toThrow(ReglaDeNegocioIncumplida::class, 'El trámite está Cerrado sin atender y no admite esta acción.')
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->count())->toBe(2);

    $otra = Persona::factory()->create();
    $finalizado = solicitudDeTramite($otra, Matricula::factory()->activa()->create(['persona_id' => $otra->id, 'grupo_id' => Grupo::factory()->create(['matriculados' => 1])->id]), 'RETIRO');
    app(AvanzarTramite::class)($finalizado->id, $this->personal);
    app(FinalizarTramite::class)($finalizado->id, $this->personal);

    expect(fn () => app(CerrarTramiteSinAtender::class)($finalizado->id, $this->personal, 'Solicitado por error'))->toThrow(ReglaDeNegocioIncumplida::class, 'El trámite está Finalizado y no admite esta acción.')
        ->and($finalizado->refresh()->estado)->toBe(EstadoTramite::Finalizado);
});

it('lets the student request the same trámite again once the previous one is closed, while an open one still blocks it', function (): void {
    $primero = solicitudDeTramite($this->persona, $this->matricula, 'RETIRO');

    expect(fn (): Tramite => solicitudDeTramite($this->persona, $this->matricula, 'RETIRO'))->toThrow(ReglaDeNegocioIncumplida::class, 'Ya tienes un trámite de Retiro del ciclo en curso para lo mismo.');

    app(CerrarTramiteSinAtender::class)($primero->id, $this->personal, 'Solicitado por error');
    $segundo = solicitudDeTramite($this->persona, $this->matricula, 'RETIRO');

    expect($segundo->estado)->toBe(EstadoTramite::EnProceso)
        ->and(Tramite::query()->where('persona_id', $this->persona->id)->count())->toBe(2);
});

it('keeps the applied fee on a closed trámite, so voiding it later leaves the trámite closed and a new request waits for another payment', function (): void {
    $pago = Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Constancia]))->create(['persona_id' => $this->persona->id]);
    $constancia = solicitudDeTramite($this->persona, $this->matricula, 'CONSTANCIA-MATRICULA');
    expect($constancia->pago_id)->toBe($pago->id);

    app(CerrarTramiteSinAtender::class)($constancia->id, $this->personal, 'El estudiante ya no la necesita');
    $nueva = solicitudDeTramite($this->persona, $this->matricula, 'CONSTANCIA-MATRICULA');
    app(AnularPago::class)($pago->id, User::factory()->create()->givePermissionTo(PermisoPago::Anular->value), 'Depósito duplicado en el banco');

    expect($constancia->refresh())->estado->toBe(EstadoTramite::Cerrado)->pago_id->toBe($pago->id)
        ->and($nueva->refresh())->estado->toBe(EstadoTramite::PendientePago)->pago_id->toBeNull();
});

it('keeps a closed trámite unchangeable and its reason required in the database', function (): void {
    $tramite = solicitudDeTramite($this->persona, $this->matricula, 'RETIRO');

    expect(fn () => DB::transaction(fn () => DB::table('tra_tramites')->where('id', $tramite->id)->update(['estado' => 'cerrado'])))->toThrow(QueryException::class, 'tra_tramites_cierre_check');

    app(CerrarTramiteSinAtender::class)($tramite->id, $this->personal, 'Solicitado por error');

    expect(fn () => DB::table('tra_tramites')->where('id', $tramite->id)->update(['etapa_actual' => 1]))->toThrow(QueryException::class, 'un trámite cerrado no cambia');
});

it('closes a trámite from its panel page with a reason and shows why it was closed', function (): void {
    $tramite = solicitudDeTramite($this->persona, $this->matricula, 'RETIRO');
    $this->actingAs($this->personal);

    Livewire::test(ViewTramite::class, ['record' => $tramite->getRouteKey()])
        ->assertActionVisible('cerrar')
        ->callAction('cerrar', ['motivo' => 'No'])
        ->assertHasActionErrors(['motivo' => 'min']);

    expect($tramite->refresh()->estado)->toBe(EstadoTramite::EnProceso);

    Livewire::test(ViewTramite::class, ['record' => $tramite->getRouteKey()])
        ->callAction('cerrar', ['motivo' => 'El estudiante desistió del retiro.'])
        ->assertHasNoActionErrors()
        ->assertNotified('Trámite cerrado sin atender');

    expect($tramite->refresh()->estado)->toBe(EstadoTramite::Cerrado)
        ->and($this->matricula->refresh()->estado)->toBe(EstadoMatricula::Activa);

    Livewire::test(ViewTramite::class, ['record' => $tramite->getRouteKey()])
        ->assertSeeText('Cerrado sin atender')
        ->assertSeeText('El estudiante desistió del retiro.')
        ->assertActionHidden('cerrar')
        ->assertActionHidden('observar')
        ->assertActionHidden('avanzar');
});

it('shows the student the closed state and its reason in the portal', function (): void {
    $tramite = solicitudDeTramite($this->persona, $this->matricula, 'RETIRO');
    app(CerrarTramiteSinAtender::class)($tramite->id, $this->personal, 'El estudiante desistió del retiro.');

    $this->actingAs($this->user);

    Livewire::test(MisTramites::class)
        ->assertSeeText($tramite->codigo)
        ->assertSeeText('Cerrado sin atender')
        ->assertSeeText('El estudiante desistió del retiro.');
});
