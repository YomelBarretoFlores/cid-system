<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

it('creates placement requests pending evaluation with a public ULID', function (): void {
    $solicitud = SolicitudClasificacion::factory()->create();

    expect($solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion)
        ->and(Str::isUlid($solicitud->ulid))->toBeTrue()
        ->and($solicitud->getRouteKey())->toBe($solicitud->ulid);
});

it('records a complete evaluation', function (): void {
    $solicitud = SolicitudClasificacion::factory()->evaluada()->create();

    expect($solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::Evaluado)
        ->and($solicitud->puntaje)->toBe('72.50')
        ->and($solicitud->ciclo_asignado_id)->not->toBeNull()
        ->and($solicitud->evaluado_por)->not->toBeNull();
});

it('rejects an evaluated request without its result', function (string $missing): void {
    expect(fn () => SolicitudClasificacion::factory()->evaluada()->create([$missing => null]))
        ->toThrow(QueryException::class, 'mat_solicitudes_clasificacion_evaluado_check');
})->with(['puntaje', 'ciclo_asignado_id', 'fecha_examen', 'evaluado_por', 'evaluado_en']);

it('rejects scores outside 0-100', function (): void {
    expect(fn () => SolicitudClasificacion::factory()->evaluada()->create(['puntaje' => '100.50']))
        ->toThrow(QueryException::class, 'mat_solicitudes_clasificacion_puntaje_check');
});

it('rejects an assigned cycle from another language', function (): void {
    $otroIdioma = Ciclo::factory()->create();

    expect(fn () => SolicitudClasificacion::factory()->evaluada()->create(['ciclo_asignado_id' => $otroIdioma->id]))
        ->toThrow(QueryException::class, 'mat_solicitudes_clasificacion_ciclo_fk');
});

it('allows only one pending request per person and language', function (): void {
    $solicitud = SolicitudClasificacion::factory()->create();

    expect(fn () => SolicitudClasificacion::factory()->create([
        'persona_id' => $solicitud->persona_id,
        'idioma_id' => $solicitud->idioma_id,
    ]))->toThrow(QueryException::class, 'mat_solicitudes_clasificacion_pendiente_unique');
});

it('allows a new request once the previous one is no longer pending', function (): void {
    $persona = Persona::factory()->create();
    $idioma = Idioma::factory()->create();
    SolicitudClasificacion::factory()->create([
        'persona_id' => $persona->id,
        'idioma_id' => $idioma->id,
        'estado' => EstadoSolicitudClasificacion::Expirado,
    ]);

    $nueva = SolicitudClasificacion::factory()->create(['persona_id' => $persona->id, 'idioma_id' => $idioma->id]);

    expect($nueva->exists)->toBeTrue();
});

it('keeps one enabled cycle per person and language', function (): void {
    $habilitacion = Habilitacion::factory()->create();

    expect(fn () => Habilitacion::factory()->create([
        'persona_id' => $habilitacion->persona_id,
        'ciclo_id' => $habilitacion->ciclo_id,
    ]))->toThrow(QueryException::class, 'mat_habilitaciones_persona_id_idioma_id_unique');
});

it('rejects an enabled cycle that belongs to another language', function (): void {
    $ciclo = Ciclo::factory()->create();

    expect(fn () => Habilitacion::factory()->create([
        'ciclo_id' => $ciclo->id,
        'idioma_id' => Idioma::factory()->create()->id,
    ]))->toThrow(QueryException::class, 'mat_habilitaciones_ciclo_fk');
});
