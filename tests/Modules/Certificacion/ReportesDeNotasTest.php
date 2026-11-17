<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\GrupoDeComponente;
use App\Modules\Certificacion\Contracts\AvanceDeNotas;
use App\Modules\Certificacion\Contracts\BoletaDeNotas;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Certificacion\Data\CicloDelRecord;
use App\Modules\Certificacion\Data\FaltantesDelNivel;
use App\Modules\Certificacion\Data\FilaDeLaBoleta;
use App\Modules\Certificacion\Data\NotaDelComponente;
use App\Modules\Certificacion\Data\RecordDelIdioma;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Personas\Models\Persona;
use Carbon\CarbonImmutable;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;
use Tests\Support\ReportesDelEstudiante;

it('shows the grade progress from the pre-acta with the partial average while the acta is not closed', function (): void {
    $escenario = ReportesDelEstudiante::escenario();

    $avance = app(AvanceDeNotas::class)($escenario['matricula']->grupo_id, $escenario['matricula']->id);

    expect($avance)
        ->disponible->toBeTrue()
        ->actaCerrada->toBeFalse()
        ->promedioHabilidades->toBe('0.00')
        ->promedioExamenes->toBe('13.50')
        ->promedio->toBe('7.00')
        ->estadoFinal->toBeNull()
        ->notaMinimaAprobatoria->toBe('11.00')
        ->and(array_map(fn (NotaDelComponente $nota): array => [$nota->nombre, $nota->grupo, $nota->nota], $avance->componentes))
        ->toBe([['Expresión oral', GrupoDeComponente::Habilidades, null], ['Examen parcial', GrupoDeComponente::Examenes, '15.00'], ['Examen final', GrupoDeComponente::Examenes, '12.00']]);
});

it('shows the final grades once the acta is closed', function (): void {
    $escenario = ReportesDelEstudiante::escenario();
    ReportesDelEstudiante::cerrarActa($escenario['curso']);

    $avance = app(AvanceDeNotas::class)($escenario['matricula']->grupo_id, $escenario['matricula']->id);

    expect($avance)->actaCerrada->toBeTrue()->promedio->toBe('7.00')->estadoFinal->toBe(EstadoFinal::Desaprobado);
});

it('reports no grade progress for a group without a grading scheme', function (): void {
    [, $docente] = PortalDocente::docente();
    [$grupo, [$matricula]] = PortalDocente::grupoConEstudiantes($docente, 'Alva');

    $avance = app(AvanceDeNotas::class)($grupo->id, $matricula->id);

    expect($avance)->disponible->toBeFalse()->componentes->toBe([])->promedio->toBeNull()
        ->aviso->toBe('Las notas de este grupo aún no están disponibles.');
});

it('lists the report card with acta abierta until the acta is closed and then the final grade', function (): void {
    $escenario = ReportesDelEstudiante::escenario();
    $boleta = fn (): array => array_map(
        fn (FilaDeLaBoleta $fila): array => [$fila->grupoCodigo, $fila->idioma, $fila->cicloNumero, $fila->horasLectivas, $fila->promedio, $fila->estado()],
        app(BoletaDeNotas::class)([$escenario['matricula']->id => $escenario['matricula']->grupo_id]),
    );

    expect($boleta())->toBe([['ING-1-M', 'Inglés', 1, 60, null, 'Acta abierta']]);

    ReportesDelEstudiante::cerrarActa($escenario['curso']);

    expect($boleta())->toBe([['ING-1-M', 'Inglés', 1, 60, '7.00', 'Desaprobado']]);
});

it('builds the record per language with the times each cycle was taken, the average and the cycles missing for the level', function (): void {
    $persona = Persona::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2027-01-10 10:00', 'America/Lima'));
    ActasFinales::historial($persona, 'Inglés', [[1, '08.00', 'desaprobado']]);
    $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00', 'America/Lima'));
    ActasFinales::historial($persona, 'Inglés', [[1, '14.00', 'aprobado'], [2, '16.00', 'aprobado']]);
    ActasFinales::historial($persona, 'Quechua', [[1, null, 'nsp']]);
    ActasFinales::historial($persona, 'Francés', [[2, '15.00', 'aprobado']], estadoActa: 'abierta');

    $records = app(RecordDeNotas::class)($persona->id);

    expect(array_map(fn (RecordDelIdioma $record): array => [
        $record->idioma,
        array_map(fn (CicloDelRecord $ciclo): array => [$ciclo->resultado->cicloNumero, $ciclo->resultado->grupoCodigo, $ciclo->resultado->promedio, $ciclo->resultado->estadoFinal, $ciclo->veces], $record->ciclos),
        $record->promedio,
        array_map(fn (FaltantesDelNivel $nivel): array => [$nivel->nivel, $nivel->ciclos], $record->faltantes),
    ], $records))->toBe([
        ['Inglés', [[1, 'G-1', '14.00', EstadoFinal::Aprobado, 2], [2, 'G-2', '16.00', EstadoFinal::Aprobado, 1]], '15.00', [['Básico', [3, 4]]]],
        ['Quechua', [[1, 'G-1', null, EstadoFinal::Nsp, 1]], null, [['Básico', [1, 2, 3, 4]]]],
    ]);
});
