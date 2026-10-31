<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\ArmarHorarioSemanal;
use App\Modules\Matricula\Actions\BuscarMatriculasDelPeriodo;
use App\Modules\Matricula\Data\CeldaDelHorario;
use App\Modules\Matricula\Data\FranjaDelHorario;
use App\Modules\Matricula\Data\GrupoDelHorario;
use App\Modules\Matricula\Data\HorarioSemanal;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Ui\PaletaCiln;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ReportesDelEstudiante;

// ING-1-M (Mon and Wed 18:00–20:00, A-101) plus a virtual ITA-1-M without teacher (Tue and Thu 07:00–08:30, Sat 09:00–10:00).
beforeEach(function (): void {
    $this->escenario = ReportesDelEstudiante::escenario();
    $this->periodo = Periodo::query()->findOrFail($this->escenario['curso']['grupo']->periodo_id);
    $italiano = Grupo::factory()->for($this->periodo)->create(['codigo' => 'ITA-1-M', 'modalidad' => Modalidad::Virtual, 'aula_id' => null, 'enlace_virtual' => 'https://meet.example.test/ita-1-m']);
    foreach ([[2, '07:00', '08:30'], [4, '07:00', '08:30'], [6, '09:00', '10:00']] as [$dia, $inicio, $fin]) {
        Horario::factory()->for($italiano)->create(['dia_semana' => $dia, 'hora_inicio' => $inicio, 'hora_fin' => $fin]);
    }
    Matricula::factory()->activa()->create(['persona_id' => $this->escenario['persona']->id, 'grupo_id' => $italiano->id]);
});

function horarioDelPeriodo(): HorarioSemanal
{
    return app(ArmarHorarioSemanal::class)(app(BuscarMatriculasDelPeriodo::class)(test()->periodo->id, test()->escenario['usuario']->id));
}

/**
 * @return list<string>
 */
function franjasLeidas(HorarioSemanal $horario): array
{
    return array_map(fn (FranjaDelHorario $franja): string => $franja->sinClases ? 'SIN CLASES' : $franja->horas.' '.collect($franja->celdas)
        ->map(fn (array $celdas, int $dia): string => $dia.'='.implode('+', array_map(fn (CeldaDelHorario $celda): string => $celda->etiqueta(), $celdas)))
        ->implode(' '), $horario->franjas);
}

it('lays the week out in 30-minute rows from the first to the last class, repeating each class on every row it covers', function (): void {
    $horario = horarioDelPeriodo();

    expect($horario->dias)->toBe([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'])
        ->and(franjasLeidas($horario))->toBe([
            '07:00 - 07:30 2=[02] [VIRTUAL] 4=[02] [VIRTUAL]',
            '07:30 - 08:00 2=[02] [VIRTUAL] 4=[02] [VIRTUAL]',
            '08:00 - 08:30 2=[02] [VIRTUAL] 4=[02] [VIRTUAL]',
            '08:30 - 09:00 ',
            '09:00 - 09:30 6=[02] [VIRTUAL]',
            '09:30 - 10:00 6=[02] [VIRTUAL]',
            'SIN CLASES',
            '18:00 - 18:30 1=[01] [A-101] 3=[01] [A-101]',
            '18:30 - 19:00 1=[01] [A-101] 3=[01] [A-101]',
            '19:00 - 19:30 1=[01] [A-101] 3=[01] [A-101]',
            '19:30 - 20:00 1=[01] [A-101] 3=[01] [A-101]',
        ]);
});

it('numbers and colors each course the same in the grid and in the course list', function (): void {
    $horario = horarioDelPeriodo();
    $colorDeCelda = fn (int $franja, int $dia): int => $horario->franjas[$franja]->celdas[$dia][0]->color;

    expect(array_map(fn (GrupoDelHorario $grupo): array => [$grupo->numero, $grupo->codigo, $grupo->idioma, $grupo->cicloNumero, $grupo->docente, $grupo->lugar(), $grupo->modalidad], $horario->grupos))->toBe([
        ['01', 'ING-1-M', 'Inglés', 1, 'Vega Ríos, Carlos', 'A-101 · Laboratorio 1', 'Presencial'],
        ['02', 'ITA-1-M', $horario->grupos[1]->idioma, $horario->grupos[1]->cicloNumero, 'Por asignar', 'Virtual', 'Virtual'],
    ])
        ->and([$horario->grupos[0]->color, $horario->grupos[1]->color])->toBe([1, 2])
        ->and([$colorDeCelda(7, 1), $colorDeCelda(0, 2)])->toBe([1, 2])
        ->and(PaletaCiln::color(9))->toBe(PaletaCiln::color(1));
});

it('adds Sunday only when a class falls on it and keeps short gaps as empty rows', function (): void {
    Horario::factory()->for($this->escenario['curso']['grupo'])->create(['dia_semana' => 7, 'hora_inicio' => '10:30', 'hora_fin' => '11:00']);

    $horario = horarioDelPeriodo();

    expect(array_key_last($horario->dias))->toBe(7)
        ->and(array_slice(franjasLeidas($horario), 5, 3))->toBe(['09:30 - 10:00 6=[02] [VIRTUAL]', '10:00 - 10:30 ', '10:30 - 11:00 7=[01] [A-101]']);
});

it('names a course without classroom or link the same way in the course list and in the grid', function (): void {
    Grupo::query()->where('codigo', 'ITA-1-M')->update(['enlace_virtual' => null]);

    $horario = horarioDelPeriodo();

    expect($horario->grupos[1]->lugar())->toBe('Por asignar')
        ->and($horario->franjas[0]->celdas[2][0]->etiqueta())->toBe('[02] [POR ASIGNAR]');
});

it('keeps the course list and says there are no classes when the groups have no schedule yet', function (): void {
    Horario::query()->delete();

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes'))
        ->assertOk()
        ->assertSeeInOrder(['Horario personalizado', 'Cursos y docentes', '[01]', 'Mi horario', 'No tiene clases programadas en este periodo.'])
        ->assertDontSee('SIN CLASES');
});

it('prints the course list and the grid in the PDF and the print view, with the student and the issue time', function (): void {
    Pdf::fake();
    $textos = [
        'Horario personalizado', 'Marzo 2027', 'Quispe Mamani, Rosa', '41234567', '30/03/2027 10:00',
        'Cursos y docentes', '[01]', 'INGLÉS · CICLO 1', 'ING-1-M', 'Vega Ríos, Carlos', 'A-101 · Laboratorio 1', 'https://meet.example.test/ing-1-m', '[02]', 'Por asignar', 'Virtual',
        'Mi horario', 'HORA', 'LUNES', 'SÁBADO', '07:00 - 07:30', '[02] [VIRTUAL]', 'SIN CLASES', '19:30 - 20:00', '[01] [A-101]',
    ];

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.horario', $this->periodo->id))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'horario-marzo-2027.pdf' && collect($textos)->every(fn (string $texto): bool => $pdf->contains($texto)));

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.horario.imprimir', $this->periodo->id))
        ->assertOk()
        ->assertSeeInOrder($textos)
        ->assertSee('background: '.PaletaCiln::color(2), false)
        ->assertSee('window.print()', false)
        ->assertDontSee('DOMINGO');
});

it('shows the same course list and grid on the reports page, with the PDF and print links', function (): void {
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes'))
        ->assertOk()
        ->assertSeeInOrder([
            'Horario personalizado', 'Marzo 2027', route('estudiante.reportes.horario', $this->periodo->id), route('estudiante.reportes.horario.imprimir', $this->periodo->id),
            'Cursos y docentes', '[01]', 'INGLÉS · CICLO 1', 'Vega Ríos, Carlos', 'Mi horario', 'LUNES', '07:00 - 07:30', '[02] [VIRTUAL]', 'SIN CLASES', '[01] [A-101]',
        ])
        ->assertSee('background: var(--color-curso-1)', false);
});

it('links the full schedule from the home schedule', function (): void {
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Ver horario completo')
        ->assertSee(route('estudiante.reportes'));
});
