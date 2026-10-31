<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Livewire\MisReportes;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\FakePdfBuilder;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;
use Tests\Support\ReportesDelEstudiante;

beforeEach(function (): void {
    $this->escenario = ReportesDelEstudiante::escenario();
    $this->matricula = $this->escenario['matricula'];
    $this->periodo = Periodo::query()->findOrFail($this->escenario['curso']['grupo']->periodo_id);
});

it('offers only the periods of the student, newest first, and shows the summaries of the newest one', function (): void {
    $anterior = Periodo::factory()->create(['nombre' => 'Octubre 2026', 'anio' => 2026, 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31', 'matricula_inicio' => '2026-09-15', 'matricula_fin' => '2026-09-30']);
    Matricula::factory()->create(['persona_id' => $this->escenario['persona']->id, 'grupo_id' => Grupo::factory()->create(['codigo' => 'FRA-2-T', 'periodo_id' => $anterior->id])->id]);
    Periodo::factory()->create(['nombre' => 'Mayo 2027', 'fecha_inicio' => '2027-05-01', 'fecha_fin' => '2027-05-31']);
    ActasFinales::historial($this->escenario['persona'], 'Quechua', [[1, '13.00', 'aprobado']], periodoId: $anterior->id);

    $this->actingAs($this->escenario['usuario'])
        ->get(route('estudiante.reportes'))
        ->assertOk()
        ->assertSeeInOrder(['Marzo 2027', 'Octubre 2026'])
        ->assertDontSee('Mayo 2027')
        ->assertSeeInOrder([
            'Avance de notas', 'Examen parcial', '15.00', 'Participación', 'Promedio parcial', '10.80',
            'Asistencia', '3 de 10', '67 %', '20 %',
            'Boleta de notas', 'ING-1-M', 'Acta abierta',
            'Horario semanal', 'Lun', '18:00', 'Vega Ríos, Carlos', 'A-101 · Laboratorio 1',
            'Récord por idioma', 'Quechua', '13.00', 'Faltan los ciclos 2, 3 y 4 del nivel Básico',
        ])
        ->assertSee(route('estudiante.reportes.avance', $this->matricula))
        ->assertSee(route('estudiante.reportes.asistencia', $this->matricula))
        ->assertSee(route('estudiante.reportes.boleta', $this->periodo->id))
        ->assertSee(route('estudiante.reportes.horario', $this->periodo->id))
        ->assertSee(route('estudiante.reportes.record', 'quechua'))
        ->assertDontSee('FRA-2-T');
});

it('switches the summaries to the chosen period', function (): void {
    $anterior = Periodo::factory()->create(['nombre' => 'Octubre 2026', 'anio' => 2026, 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31', 'matricula_inicio' => '2026-09-15', 'matricula_fin' => '2026-09-30']);
    Matricula::factory()->create(['persona_id' => $this->escenario['persona']->id, 'grupo_id' => Grupo::factory()->create(['codigo' => 'FRA-2-T', 'periodo_id' => $anterior->id])->id]);

    Livewire::actingAs($this->escenario['usuario'])
        ->test(MisReportes::class)
        ->assertSet('periodo', $this->periodo->id)
        ->set('periodo', $anterior->id)
        ->assertSee('FRA-2-T')
        ->assertDontSee('ING-1-M')
        ->set('periodo', Periodo::factory()->create()->id)
        ->assertSet('periodo', $this->periodo->id)
        ->assertSee('ING-1-M');
});

it('tells a student without enrollments that there are no reports yet', function (): void {
    $usuario = User::factory()->create();
    Persona::factory()->create(['user_id' => $usuario->id]);

    $this->actingAs($usuario)->get(route('estudiante.reportes'))->assertOk()->assertSee('Aún no tienes reportes.');
});

describe('PDF reports', function (): void {
    beforeEach(fn (): FakePdfBuilder => Pdf::fake());

    it('gives the grade progress of the enrollment', function (): void {
        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.avance', $this->matricula))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->isInline() && $pdf->downloadName === 'avance-de-notas-ING-1-M.pdf'
            && $pdf->contains(['AVANCE DE NOTAS', 'Quispe Mamani, Rosa', 'DNI 41234567', 'ING-1-M', 'Marzo 2027', 'Examen parcial', '40.00', '15.00', 'Promedio parcial', '10.80']));
    });

    it('gives the report card of the period', function (): void {
        ReportesDelEstudiante::cerrarActa($this->escenario['curso']);

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.boleta', $this->periodo->id))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'boleta-de-notas-marzo-2027.pdf'
            && $pdf->contains(['BOLETA DE NOTAS', 'Quispe Mamani, Rosa', 'Marzo 2027', 'ING-1-M', 'Inglés', 'Básico I', '60', '10.80', 'Desaprobado']));
    });

    it('gives the attendance of the enrollment with each class and the totals', function (): void {
        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.asistencia', $this->matricula))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'asistencia-ING-1-M.pdf'
            && $pdf->contains(['REPORTE DE ASISTENCIA', '01/03/2027', '03/03/2027', '08/03/2027', 'Tardanza', 'Falta', 'sin confirmar', '3', '10', '67 %', '20 %']));
    });

    it('gives the weekly schedule of the period as PDF and as printable page', function (): void {
        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.horario', $this->periodo->id))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'horario-marzo-2027.pdf'
            && $pdf->contains(['HORARIO SEMANAL', 'Lunes', 'Miércoles', '18:00 – 20:00', 'ING-1-M', 'Vega Ríos, Carlos', 'A-101 · Laboratorio 1', 'https://meet.example.test/ing-1-m']));

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.horario.imprimir', $this->periodo->id))
            ->assertOk()->assertSee('HORARIO SEMANAL')->assertSee('Vega Ríos, Carlos')->assertSee('window.print()', false);
    });

    it('gives the record of one language or of every language with the informative notice', function (): void {
        ActasFinales::historial($this->escenario['persona'], 'Quechua', [[1, '13.00', 'aprobado']]);
        ActasFinales::historial($this->escenario['persona'], 'Inglés', [[1, '17.00', 'aprobado']]);

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.record', 'quechua'))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'record-de-notas-quechua.pdf'
            && $pdf->contains(['RÉCORD DE NOTAS', 'Quechua', '13.00', 'Faltan los ciclos 2, 3 y 4 del nivel Básico', 'Documento informativo, sin valor oficial; para trámites solicite la constancia de notas'])
            && ! $pdf->contains('17.00'));

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.record'))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'record-de-notas.pdf' && $pdf->contains(['Inglés', '17.00', 'Quechua', '13.00']));
    });

    it('answers not found for a language without record', function (): void {
        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.record', 'quechua'))->assertNotFound();
        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.record'))->assertNotFound();
    });
});

it('hides the reports of another student', function (string $ruta, string $parametro): void {
    $otro = User::factory()->create();
    Persona::factory()->create(['user_id' => $otro->id]);
    Pdf::fake();

    $this->actingAs($otro)->get(route($ruta, $parametro === 'matricula' ? $this->matricula : $this->periodo->id))->assertNotFound();
})->with([
    ['estudiante.reportes.avance', 'matricula'],
    ['estudiante.reportes.asistencia', 'matricula'],
    ['estudiante.reportes.boleta', 'periodo'],
    ['estudiante.reportes.horario', 'periodo'],
    ['estudiante.reportes.horario.imprimir', 'periodo'],
]);

it('keeps the reports behind the portal login', function (string $ruta, string $parametro): void {
    $this->get(route($ruta, $parametro === 'matricula' ? $this->matricula : $this->periodo->id))->assertRedirect(route('ingresar'));
})->with([
    ['estudiante.reportes.avance', 'matricula'],
    ['estudiante.reportes.asistencia', 'matricula'],
    ['estudiante.reportes.boleta', 'periodo'],
    ['estudiante.reportes.horario', 'periodo'],
]);

it('keeps the reports page and the record behind the portal login', function (): void {
    $this->get(route('estudiante.reportes'))->assertRedirect(route('ingresar'));
    $this->get(route('estudiante.reportes.record'))->assertRedirect(route('ingresar'));
});

it('adds the reports to the portal menu', function (): void {
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.matriculas'))->assertOk()->assertSee(route('estudiante.reportes'));
});
