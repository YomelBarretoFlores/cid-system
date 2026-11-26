<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Data\PagoData;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\FinalizarTramite;
use App\Modules\Tramites\Actions\ObservarTramite;
use App\Modules\Tramites\Actions\ReanudarTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Event;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->periodo = Periodo::factory()->conMatriculaAbierta()->create();
    $this->ciclo = Ciclo::factory()->create(['numero' => 1]);
    [$this->origen, $this->destino] = Grupo::factory()->count(2)->for($this->periodo)->for($this->ciclo)->create(['cupo_maximo' => 3, 'cupo_minimo' => 1])->all();
    $this->persona = Persona::factory()->create();
    $this->matricula = app(MatricularEstudiante::class)($this->persona->id, $this->origen->id, Pago::factory()->validado()->for(Concepto::factory())->create(['persona_id' => $this->persona->id])->id);
    $this->personal = User::factory()->create()->givePermissionTo([PermisoTramite::Gestionar->value, PermisoCertificacion::Documentos->value, PermisoMatricula::Gestionar->value]);
});

function tramiteListoParaFirma(Persona $persona, Matricula $matricula): Tramite
{
    Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Constancia]))->create(['persona_id' => $persona->id]);

    return app(SolicitarTramite::class)($persona->id, TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->value('id'), new DatosDeSolicitud(matriculaId: $matricula->id))->refresh();
}

function tramiteSinTasa(Persona $persona, Matricula $matricula, string $codigo): Tramite
{
    return app(SolicitarTramite::class)($persona->id, TipoTramite::query()->where('codigo', $codigo)->value('id'), new DatosDeSolicitud(matriculaId: $matricula->id, motivo: 'Cambio de horario de trabajo'));
}

it('moves a paid trámite to the next staff stage, and leaves the last one to the finish action', function (): void {
    $tramite = tramiteListoParaFirma($this->persona, $this->matricula);

    app(AvanzarTramite::class)($tramite->id, $this->personal);

    expect($tramite->refresh())->etapa_actual->toBe(2)->estado->toBe(EstadoTramite::EnProceso)
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->latest('id')->first())->etapa_nombre->toBe('En firma de Dirección')->actor_id->toBe($this->personal->id)
        ->and(fn () => app(AvanzarTramite::class)($tramite->id, $this->personal))->toThrow(ReglaDeNegocioIncumplida::class, 'La siguiente es la última etapa: finalice el trámite.');
});

it('does not move a trámite that waits for its fee, and refuses staff without the permission', function (): void {
    $pendiente = app(SolicitarTramite::class)($this->persona->id, TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->value('id'), new DatosDeSolicitud(matriculaId: $this->matricula->id));

    expect(fn () => app(AvanzarTramite::class)($pendiente->id, $this->personal))->toThrow(ReglaDeNegocioIncumplida::class, 'El trámite está Pendiente de pago y no admite esta acción.')
        ->and(fn () => app(ObservarTramite::class)($pendiente->id, User::factory()->create(), 'Falta el documento'))->toThrow(ReglaDeNegocioIncumplida::class, 'No tienes permiso para atender trámites.');
});

it('finishes an enrollment record trámite by issuing the document as the staff member', function (): void {
    $tramite = tramiteListoParaFirma($this->persona, $this->matricula);
    app(AvanzarTramite::class)($tramite->id, $this->personal);

    app(FinalizarTramite::class)($tramite->id, $this->personal);

    $documento = Documento::query()->where('persona_id', $this->persona->id)->sole();
    expect($tramite->refresh())->estado->toBe(EstadoTramite::Finalizado)->etapa_actual->toBe(3)->documento_id->toBe($documento->id)->finalizado_en->not->toBeNull()
        ->and($documento)->tipo->toBe(TipoDocumento::ConstanciaMatricula)->matricula_id->toBe($this->matricula->id)->emitido_por->toBe($this->personal->id);
});

it('leaves the trámite as it was when the final action is refused', function (): void {
    $tramite = tramiteListoParaFirma($this->persona, $this->matricula);
    app(AvanzarTramite::class)($tramite->id, $this->personal);
    $sinDocumentos = User::factory()->create()->givePermissionTo(PermisoTramite::Gestionar->value);

    expect(fn () => app(FinalizarTramite::class)($tramite->id, $sinDocumentos))->toThrow(ReglaDeNegocioIncumplida::class, 'No tienes permiso para emitir constancias y certificados.')
        ->and($tramite->refresh())->estado->toBe(EstadoTramite::EnProceso)->etapa_actual->toBe(2)
        ->and(Documento::query()->count())->toBe(0);
});

it('finishes a withdrawal by withdrawing the enrollment, and a transfer by moving it to the chosen group', function (): void {
    $retiro = tramiteSinTasa($this->persona, $this->matricula, 'RETIRO');
    app(AvanzarTramite::class)($retiro->id, $this->personal);
    app(FinalizarTramite::class)($retiro->id, $this->personal);

    expect($this->matricula->refresh()->estado)->toBe(EstadoMatricula::Retirada)
        ->and($retiro->refresh()->estado)->toBe(EstadoTramite::Finalizado);

    $otra = Persona::factory()->create();
    $matricula = app(MatricularEstudiante::class)($otra->id, $this->origen->id, Pago::factory()->validado()->for(Concepto::factory())->create(['persona_id' => $otra->id])->id);
    Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Tramite]))->create(['persona_id' => $otra->id]);
    $traslado = tramiteSinTasa($otra, $matricula, 'TRASLADO')->refresh();
    app(AvanzarTramite::class)($traslado->id, $this->personal);

    expect(fn () => app(FinalizarTramite::class)($traslado->id, $this->personal))->toThrow(ReglaDeNegocioIncumplida::class, 'Elige el grupo de destino del traslado.');

    app(FinalizarTramite::class)($traslado->id, $this->personal, $this->destino->id);

    expect($matricula->refresh()->grupo_id)->toBe($this->destino->id)
        ->and($traslado->refresh()->estado)->toBe(EstadoTramite::Finalizado);
});

it('observes a trámite with a reason and resumes it, back to waiting for the fee when it is still unpaid', function (): void {
    $enProceso = tramiteSinTasa($this->persona, $this->matricula, 'RETIRO');

    expect(fn () => app(ObservarTramite::class)($enProceso->id, $this->personal, 'no'))->toThrow(ReglaDeNegocioIncumplida::class, 'Indica la observación, entre 5 y 500 caracteres.');

    app(ObservarTramite::class)($enProceso->id, $this->personal, 'Indica desde qué fecha dejas de asistir.');
    expect($enProceso->refresh())->estado->toBe(EstadoTramite::Observado)->observacion->toBe('Indica desde qué fecha dejas de asistir.');

    app(ReanudarTramite::class)($enProceso->id, $this->personal);
    expect($enProceso->refresh())->estado->toBe(EstadoTramite::EnProceso)->observacion->toBeNull();

    $sinPagar = app(SolicitarTramite::class)($this->persona->id, TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->value('id'), new DatosDeSolicitud(matriculaId: $this->matricula->id));
    app(ObservarTramite::class)($sinPagar->id, $this->personal, 'Adjunta tu documento de identidad.');
    app(ReanudarTramite::class)($sinPagar->id, $this->personal);
    expect($sinPagar->refresh()->estado)->toBe(EstadoTramite::PendientePago);

    app(ObservarTramite::class)($sinPagar->id, $this->personal, 'Adjunta tu documento de identidad.');
    $pago = Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Constancia]))->create(['persona_id' => $this->persona->id]);
    app(ReanudarTramite::class)($sinPagar->id, $this->personal);
    expect($sinPagar->refresh())->estado->toBe(EstadoTramite::EnProceso)->pago_id->toBe($pago->id)->etapa_actual->toBe(1);
});

it('asks for the enrollment permission to finish a withdrawal, leaving everything unchanged without it', function (): void {
    $retiro = tramiteSinTasa($this->persona, $this->matricula, 'RETIRO');
    app(AvanzarTramite::class)($retiro->id, $this->personal);
    $soloTramites = User::factory()->create()->givePermissionTo(PermisoTramite::Gestionar->value);

    expect(fn () => app(FinalizarTramite::class)($retiro->id, $soloTramites))->toThrow(ReglaDeNegocioIncumplida::class, 'No tienes permiso para trasladar ni retirar matrículas.')
        ->and($retiro->refresh()->estado)->toBe(EstadoTramite::EnProceso)
        ->and($this->matricula->refresh()->estado)->toBe(EstadoMatricula::Activa);
});

it('finishes a grade record and a level certificate trámite with the language and level the student asked for', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado']]);
    Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Constancia]))->create(['persona_id' => $this->persona->id]);
    Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Certificado]))->create(['persona_id' => $this->persona->id]);
    $notas = app(SolicitarTramite::class)($this->persona->id, TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->value('id'), new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'));
    $certificado = app(SolicitarTramite::class)($this->persona->id, TipoTramite::query()->where('codigo', 'CERTIFICADO-NIVEL')->value('id'), new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'));

    foreach ([$notas, $certificado] as $tramite) {
        app(AvanzarTramite::class)($tramite->id, $this->personal);
        app(FinalizarTramite::class)($tramite->id, $this->personal);
    }

    expect(Documento::query()->whereKey($notas->refresh()->documento_id)->sole())->tipo->toBe(TipoDocumento::ConstanciaNotas)->idioma->toBe('Inglés')->nivel->toBe(NivelCurricular::Basico)
        ->and(Documento::query()->whereKey($certificado->refresh()->documento_id)->sole())->tipo->toBe(TipoDocumento::CertificadoNivel)->nivel->toBe(NivelCurricular::Basico);
});

it('resumes an unpaid trámite back to waiting for the fee when its free payment can no longer be used', function (): void {
    $sinPagar = app(SolicitarTramite::class)($this->persona->id, TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->value('id'), new DatosDeSolicitud(matriculaId: $this->matricula->id));
    app(ObservarTramite::class)($sinPagar->id, $this->personal, 'Adjunta tu documento de identidad.');
    Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Constancia]))->create(['persona_id' => $this->persona->id]);
    $this->app->bind(VerificacionDePago::class, fn (): VerificacionDePago => new class implements VerificacionDePago
    {
        public function __invoke(int $pagoId, int $personaId, TipoConcepto $servicio): PagoData
        {
            throw new ReglaDeNegocioIncumplida('El pago fue anulado.');
        }
    });

    Event::fake([TramiteActualizado::class]);

    app(ReanudarTramite::class)($sinPagar->id, $this->personal);

    expect($sinPagar->refresh())->estado->toBe(EstadoTramite::PendientePago)->pago_id->toBeNull();
    Event::assertDispatchedTimes(TramiteActualizado::class, 1);
});
