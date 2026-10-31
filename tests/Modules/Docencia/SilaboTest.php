<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\CopiarSilabo;
use App\Modules\Docencia\Actions\GuardarSilabo;
use App\Modules\Docencia\Actions\PublicarSilabo;
use App\Modules\Docencia\Contracts\EstadosDeSilabos;
use App\Modules\Docencia\Contracts\SilaboDelGrupo;
use App\Modules\Docencia\Data\EvaluacionDelSilabo;
use App\Modules\Docencia\Data\SemanaDelSilabo;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\EvaluacionDeSilabo;
use App\Modules\Docencia\Models\SemanaDeSilabo;
use App\Modules\Docencia\Models\Silabo;
use App\Modules\Docencia\Support\SilabosParaCopiar;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

const SILABO_CODIGOS = ['parcial', 'final', 'participacion'];

// ActasFinales::curso(): group ING-1-M of Inglés Básico I, period 1 to 31 March 2027 (five weeks), scheme 40/40/20.
beforeEach(function (): void {
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    $this->docente = $this->curso['docente'];
    $this->usuario = $this->curso['usuario'];
});

/**
 * @return array<string, mixed>
 */
function silaboDelCurso(string $prefijo = ''): array
{
    return PortalDocente::silaboCompleto(5, SILABO_CODIGOS, $prefijo);
}

/**
 * @return array<string, mixed>
 */
function silaboSinPartes(Closure $quitar): array
{
    $datos = silaboDelCurso();
    $quitar($datos);

    return $datos;
}

function silaboAuditado(string $tabla): int
{
    return DB::table('aud_registros')->where('tabla', $tabla)->count();
}

it('saves an incomplete draft as typed and reads it back with every week of the period and the scheme rows', function (): void {
    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, [
        'sumilla' => "  Curso básico.\r\nSegunda línea.  ", 'competencias' => '',
        'semanas' => [2 => ['saber' => 'Saludos', 'actividades' => ' ']],
        'evaluaciones' => ['final' => ['instrumento' => 'Examen escrito', 'semana' => '5']],
    ]);

    $silabo = app(SilaboDelGrupo::class)($this->grupo->id);

    expect($silabo->estado)->toBe(EstadoSilabo::Borrador)
        ->and($silabo->publicadoEn)->toBeNull()
        ->and([$silabo->sumilla, $silabo->competencias, $silabo->bibliografia])->toBe(["Curso básico.\nSegunda línea.", null, null])
        ->and(array_map(fn (SemanaDelSilabo $semana): array => [$semana->numero, $semana->desde->toDateString(), $semana->hasta->toDateString(), $semana->saber, $semana->actividades], $silabo->semanas))->toBe([
            [1, '2027-03-01', '2027-03-07', null, null], [2, '2027-03-08', '2027-03-14', 'Saludos', null], [3, '2027-03-15', '2027-03-21', null, null],
            [4, '2027-03-22', '2027-03-28', null, null], [5, '2027-03-29', '2027-03-31', null, null],
        ])
        ->and(array_map(fn (EvaluacionDelSilabo $fila): array => [$fila->componente, $fila->peso, $fila->instrumento, $fila->semana], $silabo->evaluaciones))->toBe([
            ['Examen parcial', '40.00', null, null], ['Examen final', '40.00', 'Examen escrito', 5], ['Participación', '20.00', null, null],
        ])
        ->and(SemanaDeSilabo::query()->pluck('semana')->all())->toBe([2])
        ->and(EvaluacionDeSilabo::query()->pluck('componente_codigo')->all())->toBe(['final'])
        ->and(app(EstadosDeSilabos::class)([$this->grupo->id, 999]))->toBe([$this->grupo->id => EstadoSilabo::Borrador])
        ->and([silaboAuditado('doc_silabos'), silaboAuditado('doc_silabo_semanas'), silaboAuditado('doc_silabo_evaluaciones')])->toBe([1, 1, 1]);
});

it('does not rewrite anything when the same syllabus is saved twice', function (): void {
    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, silaboDelCurso());
    $auditados = silaboAuditado('doc_silabos') + silaboAuditado('doc_silabo_semanas') + silaboAuditado('doc_silabo_evaluaciones');

    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, silaboDelCurso());

    expect(Silabo::query()->count())->toBe(1)
        ->and(silaboAuditado('doc_silabos') + silaboAuditado('doc_silabo_semanas') + silaboAuditado('doc_silabo_evaluaciones'))->toBe($auditados);
});

it('publishes a complete syllabus and records when and by whom', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-02-25 09:30', 'America/Lima'));

    app(PublicarSilabo::class)($this->grupo->id, $this->docente->id, silaboDelCurso(), $this->usuario);

    $silabo = Silabo::query()->sole();
    expect($silabo->estado)->toBe(EstadoSilabo::Publicado)
        ->and($silabo->publicado_por)->toBe($this->usuario->id)
        ->and($silabo->publicado_en?->toIso8601String())->toBe('2027-02-25T14:30:00+00:00')
        ->and(SemanaDeSilabo::query()->count())->toBe(5)
        ->and(EvaluacionDeSilabo::query()->count())->toBe(3)
        ->and(app(SilaboDelGrupo::class)($this->grupo->id)?->estaPublicado())->toBeTrue();
});

it('refuses to publish while a section, week or evaluation row is incomplete and keeps the draft', function (Closure $quitar, string $mensaje): void {
    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, ['sumilla' => 'Borrador anterior.']);

    expect(fn () => app(PublicarSilabo::class)($this->grupo->id, $this->docente->id, silaboSinPartes($quitar), $this->usuario))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);

    expect(Silabo::query()->sole()->only(['estado', 'sumilla', 'publicado_en']))->toBe(['estado' => EstadoSilabo::Borrador, 'sumilla' => 'Borrador anterior.', 'publicado_en' => null])
        ->and(SemanaDeSilabo::query()->count())->toBe(0)
        ->and(EvaluacionDeSilabo::query()->count())->toBe(0);
})->with([
    'sumilla' => [fn (array &$datos): string => $datos['sumilla'] = '  ', 'Para publicar el sílabo completa: Sumilla.'],
    'bibliografía' => [fn (array &$datos): null => $datos['bibliografia'] = null, 'Para publicar el sílabo completa: Bibliografía.'],
    'semana' => [function (array &$datos): void {
        $datos['semanas'][3]['actividades'] = '';
        unset($datos['semanas'][5]);
    }, 'Para publicar el sílabo completa: Semana 3: Actividades; Semana 5: Saber y Actividades.'],
    'evaluación' => [fn (array &$datos): array => $datos['evaluaciones']['final'] = ['indicadores' => 'Comprende textos', 'instrumento' => null, 'semana' => null], 'Para publicar el sílabo completa: Evaluación Examen final: Instrumento.'],
]);

it('refuses to publish while the group has no evaluation scheme but keeps saving drafts', function (): void {
    EsquemaEvaluacion::query()->update(['vigente_desde' => '2027-04-01']);

    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, ['sumilla' => 'Curso básico.']);

    expect(fn () => app(PublicarSilabo::class)($this->grupo->id, $this->docente->id, [...silaboDelCurso(), 'evaluaciones' => []], $this->usuario))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre antes de publicar el sílabo.');
    expect(Silabo::query()->sole()->estado)->toBe(EstadoSilabo::Borrador);
});

it('keeps a corrected syllabus published, with its first date, and audits the change', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-02-25 09:30', 'America/Lima'));
    app(PublicarSilabo::class)($this->grupo->id, $this->docente->id, silaboDelCurso(), $this->usuario);
    $this->travelTo(CarbonImmutable::parse('2027-03-10 09:30', 'America/Lima'));
    $auditados = silaboAuditado('doc_silabo_semanas');

    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, silaboSinPartes(fn (array &$datos): string => $datos['semanas'][2]['saber'] = 'Números y colores'));
    app(PublicarSilabo::class)($this->grupo->id, $this->docente->id, silaboSinPartes(fn (array &$datos): string => $datos['semanas'][2]['saber'] = 'Números y colores'), $this->usuario);

    $silabo = Silabo::query()->sole();
    expect($silabo->estado)->toBe(EstadoSilabo::Publicado)
        ->and($silabo->publicado_en?->toIso8601String())->toBe('2027-02-25T14:30:00+00:00')
        ->and(SemanaDeSilabo::query()->where('semana', 2)->value('saber'))->toBe('Números y colores')
        ->and(silaboAuditado('doc_silabo_semanas'))->toBe($auditados + 1)
        ->and(DB::table('aud_registros')->where('tabla', 'doc_silabo_semanas')->latest('id')->value('operacion'))->toBe('UPDATE');
});

it('refuses a correction that leaves a published syllabus incomplete', function (): void {
    app(PublicarSilabo::class)($this->grupo->id, $this->docente->id, silaboDelCurso(), $this->usuario);

    expect(fn () => app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, silaboSinPartes(fn (array &$datos): string => $datos['metodologia'] = '')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Para publicar el sílabo completa: Metodología.');

    expect(Silabo::query()->sole()->metodologia)->toBe('Enfoque comunicativo.');
});

it('refuses texts over their limit and weeks or components the group does not have', function (array $datos, string $mensaje): void {
    expect(fn () => app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, $datos))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);

    expect(Silabo::query()->count())->toBe(0);
})->with([
    'sumilla' => [['sumilla' => str_repeat('a', 2001)], 'Sumilla: usa como máximo 2000 caracteres.'],
    'celda de semana' => [['semanas' => [4 => ['recursos' => str_repeat('a', 501)]]], 'Semana 4: Recursos: usa como máximo 500 caracteres.'],
    'semana de evaluación' => [['evaluaciones' => ['parcial' => ['semana' => '6']]], 'Examen parcial: la semana debe estar entre 1 y 5.'],
    'semana fuera del periodo' => [['semanas' => [6 => ['saber' => 'Repaso']]], 'Las semanas del periodo cambiaron; vuelve a cargar la página.'],
    'componente ajeno' => [['evaluaciones' => ['oral' => ['indicadores' => 'Habla']]], 'El sistema de evaluación del grupo cambió; vuelve a cargar la página.'],
]);

it('lets only the teacher in charge save, publish or copy, and only while the group is not cancelled or finished', function (Closure $preparar, string $mensaje): void {
    $docenteId = $preparar->call($this) ?? $this->docente->id;
    $otro = Grupo::factory()->create(['ciclo_id' => $this->grupo->ciclo_id, 'estado' => EstadoGrupo::Abierto]);
    PortalDocente::asignar($otro, $ajeno = Docente::factory()->create());
    $origen = app(PublicarSilabo::class)($otro->id, $ajeno->id, silaboDelCurso(), $this->usuario);

    foreach ([
        fn () => app(GuardarSilabo::class)($this->grupo->id, $docenteId, silaboDelCurso()),
        fn () => app(PublicarSilabo::class)($this->grupo->id, $docenteId, silaboDelCurso(), $this->usuario),
        fn () => app(CopiarSilabo::class)($this->grupo->id, $docenteId, $origen->id),
    ] as $accion) {
        expect($accion)->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    }

    expect(Silabo::query()->where('grupo_id', $this->grupo->id)->exists())->toBeFalse();
})->with([
    'otro docente' => [fn (): int => Docente::factory()->create()->id, 'No tienes a cargo el grupo ING-1-M.'],
    'grupo cancelado' => [function (): null {
        $this->grupo->update(['estado' => EstadoGrupo::Cancelado]);

        return null;
    }, 'El grupo ING-1-M está Cancelado; el sílabo solo se edita en grupos programados, abiertos o cerrados.'],
]);

it('lets the teacher write the syllabus of a group that has not started yet', function (): void {
    $this->grupo->update(['estado' => EstadoGrupo::Programado]);

    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, ['sumilla' => 'Curso básico.']);

    expect(Silabo::query()->sole()->sumilla)->toBe('Curso básico.');
});

it('copies a published syllabus of the same cycle from any teacher, replacing the draft and matching weeks and components', function (): void {
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-05-03', 'fecha_fin' => '2027-06-13', 'matricula_inicio' => '2027-04-15', 'matricula_fin' => '2027-05-05']);
    $anterior = Grupo::factory()->create(['codigo' => 'ING-1-MAY', 'ciclo_id' => $this->grupo->ciclo_id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    PortalDocente::asignar($anterior, $otroDocente = Docente::factory()->create());
    $datos = PortalDocente::silaboCompleto(6, SILABO_CODIGOS, 'Anterior: ');
    $datos['evaluaciones']['final']['semana'] = '6';
    $datos['evaluaciones']['parcial']['semana'] = '3';
    $origen = app(PublicarSilabo::class)($anterior->id, $otroDocente->id, $datos, $this->usuario);
    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, ['sumilla' => 'Mi borrador.', 'semanas' => [1 => ['recursos' => 'Mis fichas']]]);

    app(CopiarSilabo::class)($this->grupo->id, $this->docente->id, $origen->id);

    $copia = app(SilaboDelGrupo::class)($this->grupo->id);
    expect($copia->estado)->toBe(EstadoSilabo::Borrador)
        ->and([$copia->sumilla, $copia->bibliografia])->toBe(['Anterior: Curso básico de inglés.', 'Anterior: Libro del curso.'])
        ->and(array_map(fn (SemanaDelSilabo $semana): array => [$semana->saber, $semana->recursos], $copia->semanas))->toBe([
            ['Anterior: Vocabulario 1', null], ['Anterior: Vocabulario 2', null], ['Anterior: Vocabulario 3', null], ['Anterior: Vocabulario 4', null], ['Anterior: Vocabulario 5', null],
        ])
        ->and(array_map(fn (EvaluacionDelSilabo $fila): array => [$fila->componenteCodigo, $fila->indicadores, $fila->semana], $copia->evaluaciones))->toBe([
            ['parcial', 'Anterior: Indicadores de parcial', 3], ['final', 'Anterior: Indicadores de final', null], ['participacion', 'Anterior: Indicadores de participacion', null],
        ])
        ->and(SemanaDeSilabo::query()->where('silabo_id', $origen->id)->count())->toBe(6);
});

it('refuses to copy a draft, the own syllabus or one of another cycle', function (Closure $origen, string $mensaje): void {
    app(GuardarSilabo::class)($this->grupo->id, $this->docente->id, ['sumilla' => 'Mi borrador.']);
    $silaboId = $origen->call($this);
    $antes = Silabo::query()->where('grupo_id', $this->grupo->id)->value('sumilla');

    expect(fn () => app(CopiarSilabo::class)($this->grupo->id, $this->docente->id, $silaboId))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Silabo::query()->where('grupo_id', $this->grupo->id)->value('sumilla'))->toBe($antes);
})->with([
    'borrador' => [function (): int {
        $otro = Grupo::factory()->create(['ciclo_id' => $this->grupo->ciclo_id]);
        PortalDocente::asignar($otro, $docente = Docente::factory()->create());

        return app(GuardarSilabo::class)($otro->id, $docente->id, silaboDelCurso())->id;
    }, 'Elige un sílabo publicado para copiarlo.'],
    'propio' => [fn (): int => app(PublicarSilabo::class)($this->grupo->id, $this->docente->id, silaboDelCurso(), $this->usuario)->id, 'Solo se copia el sílabo publicado de otro grupo del mismo ciclo.'],
    'otro ciclo' => [function (): int {
        $otro = Grupo::factory()->create(['ciclo_id' => Ciclo::factory()->create(['idioma_id' => $this->grupo->ciclo->idioma_id, 'numero' => 2])->id]);
        PortalDocente::asignar($otro, $docente = Docente::factory()->create());

        return app(PublicarSilabo::class)($otro->id, $docente->id, silaboDelCurso(), $this->usuario)->id;
    }, 'Solo se copia el sílabo publicado de otro grupo del mismo ciclo.'],
]);

it('offers to copy only published syllabi of other groups of the same cycle', function (): void {
    $mismoCiclo = Grupo::factory()->create(['codigo' => 'ING-1-B', 'ciclo_id' => $this->grupo->ciclo_id, 'periodo_id' => $this->grupo->periodo_id]);
    $borrador = Grupo::factory()->create(['codigo' => 'ING-1-C', 'ciclo_id' => $this->grupo->ciclo_id]);
    $otroCiclo = Grupo::factory()->create(['codigo' => 'ING-2-A', 'ciclo_id' => Ciclo::factory()->create(['idioma_id' => $this->grupo->ciclo->idioma_id, 'numero' => 2])->id]);
    foreach ([$mismoCiclo, $borrador, $otroCiclo] as $grupo) {
        PortalDocente::asignar($grupo, $this->docente);
    }
    $publicado = app(PublicarSilabo::class)($mismoCiclo->id, $this->docente->id, silaboDelCurso(), $this->usuario);
    app(GuardarSilabo::class)($borrador->id, $this->docente->id, silaboDelCurso());
    app(PublicarSilabo::class)($otroCiclo->id, $this->docente->id, silaboDelCurso(), $this->usuario);
    app(PublicarSilabo::class)($this->grupo->id, $this->docente->id, silaboDelCurso(), $this->usuario);

    expect(app(SilabosParaCopiar::class)($this->grupo->id))->toBe([$publicado->id => 'Grupo ING-1-B · Marzo 2027 · Vega Ríos, Carlos']);
});
