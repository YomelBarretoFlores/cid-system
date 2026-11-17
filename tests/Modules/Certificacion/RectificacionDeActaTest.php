<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\RectificarNotasFinales;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Certificacion\Models\Rectificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    $this->ana = ActasFinales::estudiante($this->grupo, 'Alva', '70000001');
    $this->beto = ActasFinales::estudiante($this->grupo, 'Bravo', '70000002');
    ActasFinales::preActa($this->ana, [$this->curso['parcial']->id => '15.00', $this->curso['final']->id => '12.00', $this->curso['oral']->id => '18.00']);
    ActasFinales::preActa($this->beto, [$this->curso['parcial']->id => '8.00', $this->curso['final']->id => '10.00']);
    app(CerrarActa::class)($this->grupo->id, $this->curso['docente']->id, $this->curso['usuario']);
    $this->acta = Acta::query()->sole();
    $this->administrador = User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value);
});

function rectificar(array $notas, ?int $docenteId = null): void
{
    app(RectificarNotasFinales::class)(test()->grupo->id, $docenteId ?? test()->curso['docente']->id, $notas);
}

function autorizar(string $motivo = 'Error al transcribir el examen final.'): void
{
    app(AutorizarRectificacion::class)(test()->acta->id, $motivo, test()->administrador);
}

it('reopens a closed acta when the administrator authorizes a rectification with a reason', function (): void {
    autorizar('  Error al transcribir el examen final.  ');

    expect($this->acta->refresh()->estado)->toBe(EstadoActa::Abierta)
        ->and(Rectificacion::query()->sole())
        ->acta_id->toBe($this->acta->id)
        ->motivo->toBe('Error al transcribir el examen final.')
        ->autorizada_por->toBe($this->administrador->id)
        ->cerrada_en->toBeNull()
        ->and(DB::table('aud_registros')->where('tabla', 'cer_rectificaciones')->count())->toBe(1);
});

it('refuses a rectification without a reason of 5 to 500 characters or on an acta already open', function (): void {
    expect(fn () => autorizar('Mal'))->toThrow(ReglaDeNegocioIncumplida::class, 'Indica el motivo de la rectificación, de 5 a 500 caracteres.')
        ->and(fn () => autorizar(str_repeat('a', 501)))->toThrow(ReglaDeNegocioIncumplida::class, 'Indica el motivo de la rectificación, de 5 a 500 caracteres.')
        ->and($this->acta->refresh()->estado)->toBe(EstadoActa::Cerrada);

    autorizar(str_repeat('a', 500));

    expect(fn () => autorizar())->toThrow(ReglaDeNegocioIncumplida::class, 'El acta final del grupo ING-1-M ya tiene una rectificación autorizada.')
        ->and(Rectificacion::query()->count())->toBe(1);
});

it('refuses to rectify grades of a closed acta', function (): void {
    expect(fn () => rectificar([$this->beto->id => [$this->curso['final']->id => '14']]))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El acta final del grupo ING-1-M está cerrada; para corregirla, el administrador debe autorizar una rectificación.')
        ->and(NotaFinal::query()->where('matricula_id', $this->beto->id)->sole()->promedio)->toBe('5.00');
});

it('rectifies grades after the authorization, even outside the grade calendar, recomputing only the changed rows', function (): void {
    autorizar();
    $this->travelTo(CarbonImmutable::parse('2027-05-10 09:00', 'America/Lima'));
    $auditadas = DB::table('aud_registros')->where('tabla', 'cer_notas_finales')->count();

    rectificar([
        $this->ana->id => [$this->curso['parcial']->id => '15'],
        $this->beto->id => [$this->curso['final']->id => '18,5', $this->curso['oral']->id => '15'],
    ]);

    $beto = NotaFinal::query()->where('matricula_id', $this->beto->id)->sole();

    expect($beto->notas)->toEqual([$this->curso['parcial']->id => '8.00', $this->curso['final']->id => '18.50', $this->curso['oral']->id => '15.00'])
        ->and($beto->promedio)->toBe('14.00')
        ->and($beto->estado_final)->toBe(EstadoFinal::Aprobado)
        ->and(DB::table('aud_registros')->where('tabla', 'cer_notas_finales')->count())->toBe($auditadas + 1);
});

it('turns a student into NSP when every grade is cleared', function (): void {
    autorizar();

    rectificar([$this->beto->id => [$this->curso['parcial']->id => '', $this->curso['final']->id => ' ']]);

    expect(NotaFinal::query()->where('matricula_id', $this->beto->id)->sole())
        ->notas->toBe([])
        ->promedio->toBeNull()
        ->estado_final->toBe(EstadoFinal::Nsp);
});

it('refuses rectified grades out of the scale, students or components not in the acta, and other teachers', function (Closure $notas, string $mensaje): void {
    autorizar();

    expect(fn () => rectificar($notas->call($this)))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(NotaFinal::query()->where('matricula_id', $this->beto->id)->sole()->promedio)->toBe('5.00');
})->with([
    'above the maximum' => [fn (): array => [$this->beto->id => [$this->curso['final']->id => '21']], 'La nota 21 no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'a student not in the acta' => [fn (): array => [ActasFinales::estudiante($this->grupo, 'Nuevo', '70000009')->id => [$this->curso['final']->id => '14']], 'La lista del acta cambió; vuelve a cargar la página.'],
    'a component not in the acta' => [fn (): array => [$this->beto->id => [999_999 => '14']], 'La lista del acta cambió; vuelve a cargar la página.'],
]);

it('lets only the teacher in charge rectify', function (): void {
    autorizar();
    [, $otro] = PortalDocente::docente();

    expect(fn () => rectificar([$this->beto->id => [$this->curso['final']->id => '14']], $otro->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No tienes a cargo el grupo ING-1-M.');
});

it('closes the rectified acta again, outside the grade calendar, and ends the rectification', function (): void {
    autorizar();
    rectificar([$this->beto->id => [$this->curso['final']->id => '18']]);
    $this->travelTo(CarbonImmutable::parse('2027-05-10 09:00', 'America/Lima'));
    $validador = User::factory()->create();

    app(CerrarActa::class)($this->grupo->id, $this->curso['docente']->id, $validador);

    expect($this->acta->refresh())
        ->estado->toBe(EstadoActa::Cerrada)
        ->validada_por->toBe($validador->id)
        ->and($this->acta->validada_en?->toDateString())->toBe('2027-05-10')
        ->and(Rectificacion::query()->sole()->cerrada_en)->not->toBeNull()
        ->and(NotaFinal::query()->count())->toBe(2);
});

it('lets only users with the actas permission authorize a rectification, and answers a missing acta', function (): void {
    expect(fn () => app(AutorizarRectificacion::class)($this->acta->id, 'Error al transcribir el examen final.', User::factory()->create()))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(AutorizarRectificacion::class)(999_999, 'Error al transcribir el examen final.', $this->administrador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El acta final no existe.')
        ->and($this->acta->refresh()->estado)->toBe(EstadoActa::Cerrada)
        ->and(Rectificacion::query()->count())->toBe(0);
});

it('keeps grades, reopening and rectifications locked in the database outside the Actions', function (Closure $cambio, string $mensaje): void {
    expect(fn (): mixed => $cambio->call($this))->toThrow(QueryException::class, $mensaje);
})->with([
    'moving a grade from the closed acta to an open one' => [function (): void {
        $abierta = DB::table('cer_actas')->insertGetId([...collect((array) DB::table('cer_actas')->first())->except(['id', 'grupo_id', 'validada_por', 'validada_en'])->all(),
            'grupo_id' => Grupo::factory()->create()->id, 'estado' => 'abierta']);
        DB::table('cer_notas_finales')->where('matricula_id', $this->ana->id)->update(['acta_id' => $abierta]);
    }, 'una nota final no cambia de acta ni de estudiante'],
    'reopening without a rectification' => [fn () => DB::table('cer_actas')->update(['estado' => 'abierta']), 'solo se reabre con una rectificación autorizada'],
    'editing the reason of a rectification' => [function (): void {
        autorizar();
        DB::table('cer_rectificaciones')->update(['motivo' => 'Otro motivo cualquiera.']);
    }, 'una rectificación solo registra cuándo se cerró el acta'],
    'deleting a rectification' => [function (): void {
        autorizar();
        DB::table('cer_rectificaciones')->delete();
    }, 'una rectificación no se elimina'],
]);
