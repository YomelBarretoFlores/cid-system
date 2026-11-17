<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\PrepararActaDelGrupo;
use App\Modules\Certificacion\Actions\RectificarNotasFinales;
use App\Modules\Certificacion\Data\FilaDelActa;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    [$parcial, $final, $oral] = [$this->curso['parcial']->id, $this->curso['final']->id, $this->curso['oral']->id];
    $this->ana = ActasFinales::estudiante($this->grupo, 'Alva', '70000001');
    $this->beto = ActasFinales::estudiante($this->grupo, 'Bravo', '70000002');
    $this->ciro = ActasFinales::estudiante($this->grupo, 'Castro', '70000003');
    ActasFinales::preActa($this->ana, [$parcial => '15.00', $final => '12.00', $oral => '18.00']);
    ActasFinales::preActa($this->beto, [$parcial => '8.00', $final => '10.00']);
    Matricula::factory()->retirada()->create(['grupo_id' => $this->grupo->id]);
});

function cerrarActa(?int $docenteId = null): void
{
    app(CerrarActa::class)(test()->grupo->id, $docenteId ?? test()->curso['docente']->id, test()->curso['usuario']);
}

function filasDelActa(): array
{
    return NotaFinal::query()->orderBy('estudiante_nombre')->get()
        ->map(fn (NotaFinal $nota): array => [$nota->matricula_id, $nota->estudiante_documento, $nota->promedio, $nota->estado_final])->all();
}

it('closes the acta with a copy of the course, teacher and scheme, the grades, averages and final states of the active students', function (): void {
    cerrarActa();

    $acta = Acta::query()->sole();

    expect($acta)
        ->estado->toBe(EstadoActa::Cerrada)
        ->grupo_codigo->toBe('ING-1-M')
        ->idioma->toBe('Inglés')
        ->nivel->toBe(NivelCurricular::Basico)
        ->ciclo_numero->toBe(1)
        ->ciclo_nombre->toBe('Básico I')
        ->horas_lectivas->toBe(60)
        ->periodo->toBe('Marzo 2027')
        ->docente_id->toBe($this->curso['docente']->id)
        ->docente_nombre->toBe('Vega Ríos, Carlos')
        ->esquema_nombre->toBe('Esquema general')
        ->nota_minima_aprobatoria->toBe('11.00')
        ->validada_por->toBe($this->curso['usuario']->id)
        ->and($acta->validada_en?->toIso8601String())->toBe(CarbonImmutable::now()->toIso8601String())
        ->and(array_column($acta->componentes, 'grupo', 'codigo'))->toBe(['oral' => 'habilidades', 'parcial' => 'examenes', 'final' => 'examenes'])
        ->and(filasDelActa())->toBe([
            [$this->ana->id, 'DNI 70000001', '16.00', EstadoFinal::Aprobado],
            [$this->beto->id, 'DNI 70000002', '5.00', EstadoFinal::Desaprobado],
            [$this->ciro->id, 'DNI 70000003', null, EstadoFinal::Nsp],
        ])
        ->and(NotaFinal::query()->where('matricula_id', $this->ana->id)->sole()->notas)->toEqual([$this->curso['parcial']->id => '15.00', $this->curso['final']->id => '12.00', $this->curso['oral']->id => '18.00'])
        ->and(DB::table('aud_registros')->where('tabla', 'cer_actas')->count())->toBe(2)
        ->and(DB::table('aud_registros')->where('tabla', 'cer_notas_finales')->count())->toBe(3);
});

it('previews the acta from the pre-acta without storing anything, then shows the stored one once closed', function (): void {
    $previa = app(PrepararActaDelGrupo::class)($this->grupo->id);

    expect($previa->estado)->toBeNull()
        ->and($previa->docente)->toBe('Vega Ríos, Carlos')
        ->and(array_map(fn (FilaDelActa $fila): array => [$fila->estudiante, $fila->promedioHabilidades, $fila->promedioExamenes, $fila->promedio, $fila->estadoFinal], $previa->filas))->toBe([
            ['Alva Prueba, Ana', '18.00', '13.50', '16.00', EstadoFinal::Aprobado],
            ['Bravo Prueba, Ana', '0.00', '9.00', '5.00', EstadoFinal::Desaprobado],
            ['Castro Prueba, Ana', null, null, null, EstadoFinal::Nsp],
        ])
        ->and(Acta::query()->count())->toBe(0);

    cerrarActa();
    ActasFinales::preActa($this->ciro, [$this->curso['final']->id => '20.00']);

    $cerrada = app(PrepararActaDelGrupo::class)($this->grupo->id);

    expect($cerrada->estaCerrada())->toBeTrue()
        ->and($cerrada->filas[2]->estadoFinal)->toBe(EstadoFinal::Nsp);
});

it('refuses to close the acta outside the rules and stores nothing', function (Closure $preparar, string $mensaje): void {
    $preparar->call($this);

    expect(fn () => cerrarActa())->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Acta::query()->count())->toBe(0)
        ->and(NotaFinal::query()->count())->toBe(0);
})->with([
    'the day before the grade calendar' => [fn () => $this->travelTo(CarbonImmutable::parse('2027-03-28 23:59', 'America/Lima')), 'El acta final del grupo ING-1-M se valida del 29 mar 2027 al 5 abr 2027.'],
    'the day after the grade calendar' => [fn () => $this->travelTo(CarbonImmutable::parse('2027-04-06 00:00', 'America/Lima')), 'El acta final del grupo ING-1-M se valida del 29 mar 2027 al 5 abr 2027.'],
    'a period without grade calendar' => [fn () => Periodo::query()->whereKey($this->grupo->periodo_id)->update(['actas_inicio' => null, 'actas_fin' => null]), 'El periodo Marzo 2027 no tiene fechas para actas finales; pide a coordinación que las registre.'],
    'a cancelled group' => [fn () => $this->grupo->update(['estado' => EstadoGrupo::Cancelado]), 'El grupo ING-1-M está Cancelado; su acta final no se puede validar.'],
    'a scheduled group' => [fn () => $this->grupo->update(['estado' => EstadoGrupo::Programado]), 'El grupo ING-1-M está Programado; su acta final no se puede validar.'],
    'a group without scheme' => [fn () => DB::table('aca_esquemas_evaluacion')->update(['vigente_desde' => '2028-01-01']), 'El grupo ING-1-M no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.'],
    'a group without active students' => [fn () => Matricula::query()->update(['estado' => 'retirada', 'retirado_en' => now()]), 'El grupo ING-1-M no tiene estudiantes activos para el acta final.'],
    'a group without teacher' => [fn () => CargaAcademica::query()->delete(), 'No tienes a cargo el grupo ING-1-M.'],
]);

it('closes the acta of a finished group inside the grade calendar', function (): void {
    $this->grupo->update(['estado' => EstadoGrupo::Finalizado]);

    cerrarActa();

    expect(Acta::query()->sole()->estado)->toBe(EstadoActa::Cerrada);
});

it('lets only the teacher in charge close the acta', function (): void {
    [, $otro] = PortalDocente::docente();

    expect(fn () => cerrarActa($otro->id))->toThrow(ReglaDeNegocioIncumplida::class, 'No tienes a cargo el grupo ING-1-M.')
        ->and(Acta::query()->count())->toBe(0);
});

it('refuses to close an acta that is already closed', function (): void {
    cerrarActa();

    expect(fn () => cerrarActa())->toThrow(ReglaDeNegocioIncumplida::class, 'El acta final del grupo ING-1-M ya está cerrada.')
        ->and(NotaFinal::query()->count())->toBe(3);
});

it('keeps a closed acta and its grades locked in the database', function (Closure $cambio, string $mensaje): void {
    cerrarActa();

    expect(fn () => $cambio())->toThrow(QueryException::class, $mensaje);
})->with([
    'changing a grade' => [fn () => DB::table('cer_notas_finales')->update(['promedio' => '20.00', 'estado_final' => 'aprobado']), 'el acta'],
    'adding a student' => [fn () => DB::table('cer_notas_finales')->insert([...(array) DB::table('cer_notas_finales')->first(['acta_id', 'persona_id', 'estudiante_nombre', 'estudiante_documento', 'notas', 'promedio', 'estado_final']), 'matricula_id' => Matricula::factory()->create()->id]), 'el acta'],
    'deleting a grade' => [fn () => DB::table('cer_notas_finales')->delete(), 'una nota final no se elimina'],
    'changing the course data' => [fn () => DB::table('cer_actas')->update(['docente_nombre' => 'Otro']), 'un acta cerrada no admite cambios en sus datos'],
    'deleting the acta' => [fn () => DB::table('cer_actas')->delete(), 'un acta no se elimina'],
]);

it('refuses to rectify grades of a group whose acta was never closed', function (): void {
    expect(fn () => app(RectificarNotasFinales::class)($this->grupo->id, $this->curso['docente']->id, [$this->ana->id => [$this->curso['final']->id => '14']]))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El grupo ING-1-M aún no tiene acta final.')
        ->and(Acta::query()->count())->toBe(0);
});
