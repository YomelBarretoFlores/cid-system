<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\NivelesConNotas;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Pagos\Actions\RechazarPago;
use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\GuardarTipoDeTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Data\DatosDeTipoTramite;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
    $this->cajero = User::factory()->create()->givePermissionTo(['pagos.validar', 'pagos.anular']);
    $this->notas = Concepto::factory()->create(['nombre' => 'Constancia de notas', 'tipo' => TipoConcepto::Constancia]);
    $this->otraConstancia = Concepto::factory()->create(['nombre' => 'Constancia de estudios', 'tipo' => TipoConcepto::Constancia]);
    $this->basico = Tarifa::factory()->for($this->notas)->create(['monto' => '20.00', 'nivel' => 'basico', 'vigente_desde' => '2020-01-01']);
    $this->intermedio = Tarifa::factory()->for($this->notas)->create(['monto' => '20.00', 'nivel' => 'intermedio', 'vigente_desde' => '2020-01-01']);
    $this->tipo = TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->sole();
    $this->tipo->update(['concepto_id' => $this->notas->id]);
    $this->app->instance(NivelesConNotas::class, new class implements NivelesConNotas
    {
        public function __invoke(int $personaId): array
        {
            return [new NivelPorCertificar('Inglés', NivelCurricular::Basico), new NivelPorCertificar('Inglés', NivelCurricular::Intermedio)];
        }
    });
});

function constanciaDeNivel(Persona $persona, string $nivel = 'basico'): Tramite
{
    return app(SolicitarTramite::class)($persona->id, test()->tipo->id, new DatosDeSolicitud(idioma: 'Inglés', nivel: $nivel))->refresh();
}

function pagoDeTarifa(Persona $persona, Concepto $concepto, ?Tarifa $tarifa = null, string $estado = 'validado', string $fecha = '2026-10-01'): Pago
{
    return Pago::factory()->{$estado}()->for($concepto)->create(['persona_id' => $persona->id, 'tarifa_id' => $tarifa?->id, 'monto' => $tarifa?->monto ?? '150.00', 'fecha_pago' => $fecha]);
}

it('applies on request only a free payment of the concept and level of the trámite', function (): void {
    pagoDeTarifa($this->persona, $this->otraConstancia, fecha: '2026-08-01');
    pagoDeTarifa($this->persona, $this->notas, $this->intermedio, fecha: '2026-09-01');
    $delNivel = pagoDeTarifa($this->persona, $this->notas, $this->basico, fecha: '2026-10-01');

    $tramite = constanciaDeNivel($this->persona);

    expect($tramite)->estado->toBe(EstadoTramite::EnProceso)->pago_id->toBe($delNivel->id)->concepto_id->toBe($this->notas->id);
});

it('keeps waiting when the free payments are of another concept of the same type or of another level', function (): void {
    pagoDeTarifa($this->persona, $this->otraConstancia);
    pagoDeTarifa($this->persona, $this->notas, $this->intermedio);

    expect(constanciaDeNivel($this->persona))->estado->toBe(EstadoTramite::PendientePago)->pago_id->toBeNull();
});

it('applies a payment validated later only when it is of the concept and level of the waiting trámite', function (): void {
    $tramite = constanciaDeNivel($this->persona);
    $otroConcepto = pagoDeTarifa($this->persona, $this->otraConstancia, estado: 'pendiente');
    $otroNivel = pagoDeTarifa($this->persona, $this->notas, $this->intermedio, estado: 'pendiente');
    $correcto = pagoDeTarifa($this->persona, $this->notas, $this->basico, estado: 'pendiente');

    app(ValidarPago::class)($otroConcepto->id, $this->cajero, boletaOriginalVerificada: true);
    app(ValidarPago::class)($otroNivel->id, $this->cajero, boletaOriginalVerificada: true);
    expect($tramite->refresh())->estado->toBe(EstadoTramite::PendientePago)->pago_id->toBeNull();

    app(ValidarPago::class)($correcto->id, $this->cajero, boletaOriginalVerificada: true);

    expect($tramite->refresh())->estado->toBe(EstadoTramite::EnProceso)->pago_id->toBe($correcto->id);
});

it('does not observe the trámite when the rejected receipt is of another concept', function (): void {
    $tramite = constanciaDeNivel($this->persona);

    app(RechazarPago::class)(pagoDeTarifa($this->persona, $this->otraConstancia, estado: 'pendiente')->id, $this->cajero, 'El número de operación no existe.');

    expect($tramite->refresh()->estado)->toBe(EstadoTramite::PendientePago);
});

it('keeps accepting any payment of the concept type when the trámite type names no concept', function (): void {
    $this->tipo->update(['concepto_id' => null]);
    $cualquiera = pagoDeTarifa($this->persona, $this->otraConstancia);

    expect(constanciaDeNivel($this->persona))->pago_id->toBe($cualquiera->id)->concepto_id->toBeNull();
});

it('keeps the concept a trámite was requested with when its type changes later', function (): void {
    $tramite = constanciaDeNivel($this->persona);
    $this->tipo->update(['concepto_id' => $this->otraConstancia->id]);
    $correcto = pagoDeTarifa($this->persona, $this->notas, $this->basico, estado: 'pendiente');

    app(ValidarPago::class)($correcto->id, $this->cajero, boletaOriginalVerificada: true);

    expect($tramite->refresh())->concepto_id->toBe($this->notas->id)->pago_id->toBe($correcto->id);
});

function datosDeTipoConConcepto(?int $conceptoId, ?TipoConcepto $tipo = null): DatosDeTipoTramite
{
    return new DatosDeTipoTramite(
        codigo: 'CERT-PRUEBA',
        nombre: 'Certificado de prueba',
        requisitos: null,
        tipoConcepto: $tipo,
        accionFinal: AccionFinal::Ninguna,
        activo: true,
        etapas: [
            ['nombre' => 'Solicitud recibida', 'avance' => 0, 'condicion' => CondicionDeEtapa::Solicitud],
            ['nombre' => 'Pago validado', 'avance' => 50, 'condicion' => CondicionDeEtapa::PagoValidado],
            ['nombre' => 'Atendido', 'avance' => 100, 'condicion' => CondicionDeEtapa::AccionDelPersonal],
        ],
        conceptoId: $conceptoId,
    );
}

it('takes the fee type of a trámite type from its payment concept', function (): void {
    $administrador = User::factory()->create()->givePermissionTo('tramites.configurar');
    $certificado = Concepto::factory()->create(['tipo' => TipoConcepto::Certificado]);

    $tipo = app(GuardarTipoDeTramite::class)(datosDeTipoConConcepto($certificado->id, TipoConcepto::Constancia), $administrador);

    expect($tipo->refresh())->concepto_id->toBe($certificado->id)->tipo_concepto->toBe(TipoConcepto::Certificado);
});

it('refuses a payment concept that is inactive or of a type trámites do not charge', function (array $atributos): void {
    $administrador = User::factory()->create()->givePermissionTo('tramites.configurar');
    $concepto = Concepto::factory()->create($atributos);

    expect(fn () => app(GuardarTipoDeTramite::class)(datosDeTipoConConcepto($concepto->id), $administrador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El concepto de pago debe estar activo y ser de constancia, certificado, trámite u otro.');
})->with([
    'enrollment' => [['tipo' => TipoConcepto::Matricula]],
    'inactive' => [['tipo' => TipoConcepto::Constancia, 'activo' => false]],
]);
