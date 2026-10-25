<?php

declare(strict_types=1);

use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Models\Etapa;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\Notificacion;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\ReglasDeEtapas;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

function rechazaEnBase(Closure $sentencia, string $mensaje): void
{
    expect(fn () => DB::transaction($sentencia))->toThrow(QueryException::class, $mensaje);
}

it('ships the Anexo trámites with valid stages and the two trámite permissions', function (): void {
    $tipos = TipoTramite::query()->with('etapas')->orderBy('id')->get();

    expect($tipos->pluck('codigo')->all())->toBe(['CONSTANCIA-MATRICULA', 'CONSTANCIA-NOTAS', 'CERTIFICADO-NIVEL', 'TRASLADO', 'RETIRO'])
        ->and($tipos->first()?->etapas->pluck('nombre')->all())->toBe(['Solicitud recibida', 'Pago validado', 'En firma de Dirección', 'Listo para recojo o descarga'])
        ->and($tipos->first()?->etapas->pluck('avance')->all())->toBe([0, 33, 66, 100])
        ->and(Permission::query()->whereIn('name', PermisoTramite::valores())->count())->toBe(2);

    $tipos->each(fn (TipoTramite $tipo) => ReglasDeEtapas::validar(
        $tipo->etapas->map(fn (Etapa $etapa): array => ['nombre' => $etapa->nombre, 'avance' => $etapa->avance, 'condicion' => $etapa->condicion])->all(),
        $tipo->cobraTasa(),
    ));
});

it('gives each trámite a unique tracking code with the year and a six-digit number', function (): void {
    $this->travelTo(now('America/Lima')->setDate(2026, 12, 31)->setTime(23, 30));

    $primero = Tramite::factory()->create()->refresh();
    $segundo = Tramite::factory()->create()->refresh();

    expect($primero->codigo)->toMatch('/^TR-2026-\d{6}$/')
        ->and($segundo->codigo)->not->toBe($primero->codigo)
        ->and((int) substr($segundo->codigo, -6))->toBe((int) substr($primero->codigo, -6) + 1);
});

it('keeps the request of a trámite fixed, a finished one closed and nothing deleted, outside the Actions', function (Closure $sentencia, string $mensaje): void {
    rechazaEnBase(fn () => $sentencia(Tramite::factory()->create()), $mensaje);
})->with([
    'deleting a trámite' => [fn (Tramite $tramite) => DB::table('tra_tramites')->where('id', $tramite->id)->delete(), 'un trámite no se elimina'],
    'changing its data' => [fn (Tramite $tramite) => DB::table('tra_tramites')->where('id', $tramite->id)->update(['datos' => '{"matricula_id": 9}']), 'la solicitud de un trámite no cambia'],
    'changing its stages' => [fn (Tramite $tramite) => DB::table('tra_tramites')->where('id', $tramite->id)->update(['etapas' => '[{"nombre":"x","avance":0,"condicion":"solicitud"},{"nombre":"y","avance":100,"condicion":"accion_del_personal"}]']), 'la solicitud de un trámite no cambia'],
    'moving it to another person' => [fn (Tramite $tramite) => DB::table('tra_tramites')->where('id', $tramite->id)->update(['persona_id' => Persona::factory()->create()->id]), 'la solicitud de un trámite no cambia'],
    'changing a finished trámite' => [function (Tramite $tramite): void {
        DB::table('tra_tramites')->where('id', $tramite->id)->update(['estado' => 'finalizado', 'etapa_actual' => 2, 'finalizado_en' => now()]);
        DB::table('tra_tramites')->where('id', $tramite->id)->update(['etapa_actual' => 1]);
    }, 'un trámite finalizado no cambia'],
    'a stage beyond its pipeline' => [fn (Tramite $tramite) => DB::table('tra_tramites')->where('id', $tramite->id)->update(['etapa_actual' => 3]), 'tra_tramites_etapa_check'],
    'observed without a reason' => [fn (Tramite $tramite) => DB::table('tra_tramites')->where('id', $tramite->id)->update(['estado' => EstadoTramite::Observado->value]), 'tra_tramites_observacion_check'],
    'finished without its date' => [fn (Tramite $tramite) => DB::table('tra_tramites')->where('id', $tramite->id)->update(['estado' => EstadoTramite::Finalizado->value]), 'tra_tramites_observacion_check'],
    'editing the history' => [function (Tramite $tramite): void {
        $movimiento = Movimiento::query()->create(['tramite_id' => $tramite->id, 'estado' => EstadoTramite::EnProceso, 'etapa' => 0, 'etapa_nombre' => 'Solicitud recibida']);
        DB::table('tra_movimientos')->where('id', $movimiento->id)->update(['comentario' => 'otro']);
    }, 'el historial de trámites no se modifica'],
    'deleting a notification' => [function (Tramite $tramite): void {
        $notificacion = Notificacion::query()->create(['tramite_id' => $tramite->id, 'destinatario' => 'ana@correo.test', 'asunto' => 'Tu trámite cambió']);
        DB::table('tra_notificaciones')->where('id', $notificacion->id)->delete();
    }, 'el historial de trámites no se modifica'],
    'deleting a trámite type' => [fn (Tramite $tramite) => DB::table('tra_tipos_tramite')->where('id', $tramite->tipo_tramite_id)->delete(), 'el historial de trámites no se modifica'],
    'truncating the trámites' => [fn () => DB::statement('TRUNCATE tra_tramites CASCADE'), 'los trámites y su historial no se borran'],
]);

it('records changes to trámites in the audit trail', function (): void {
    $tramite = Tramite::factory()->create();

    expect(DB::table('aud_registros')->where(['tabla' => 'tra_tramites', 'registro_id' => $tramite->id])->count())->toBe(1);
});

it('copies the stages of its type, so later configuration changes leave it untouched', function (): void {
    $tipo = TipoTramite::factory()->sinTasa()->create();
    $tramite = Tramite::factory()->for($tipo)->create();

    $tipo->etapas()->where('orden', 2)->update(['nombre' => 'Otra etapa', 'condicion' => CondicionDeEtapa::AccionDelPersonal]);

    expect($tramite->refresh()->etapas[1]['nombre'])->toBe('En revisión')
        ->and($tramite->avance())->toBe(0)
        ->and($tramite->condicionDeLaSiguiente())->toBe(CondicionDeEtapa::AccionDelPersonal);
});
