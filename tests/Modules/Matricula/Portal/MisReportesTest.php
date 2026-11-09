<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Models\DenominacionDelAnio;
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

it('offers only the periods of the student, newest first, and opens on every report of the period grouped like the SGA', function (): void {
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
            'Periodo seleccionado', 'Ficha de matrícula', 'Inglés 1 · ING-1-M', 'Constancia de inscripción', 'Horario personalizado', 'Boleta de notas', 'Sílabos',
            'Generales e históricos', 'Récord de notas', 'Historial de matrículas', 'Documentos emitidos',
            'Seguimiento académico', 'Avance de notas', 'Asistencia', 'Avance por idioma', 'Registro de pagos',
        ])
        ->assertSee(route('estudiante.matriculas.ficha', $this->matricula))
        ->assertSee(route('estudiante.matriculas.constancia', $this->matricula))
        ->assertSee(route('estudiante.reportes.boleta', $this->periodo->id))
        ->assertSee(route('estudiante.reportes.record'))
        ->assertSee(route('estudiante.reportes', ['ver' => 'avance']), false)
        ->assertDontSee(['Promedio parcial', 'FRA-2-T']);

    $this->get(route('estudiante.reportes', ['ver' => 'avance']))
        ->assertOk()
        ->assertSeeInOrder([
            'Todos los reportes',
            'Inglés · Ciclo 1', 'ING-1-M · Prof. Vega Ríos, Carlos', 'Acta abierta',
            'Notas', 'Examen parcial', '15.00', 'Participación', 'Promedio parcial', '10.80',
            'Asistencia', '67 %', '3 de 10 clases dictadas · 1 falta', '20 % sobre las clases programadas',
            'Descargar en PDF', 'Boleta de notas', 'Todos tus cursos del periodo', 'Avance de notas', 'Inglés 1 · por componente',
            'Asistencia', 'Inglés 1 · clase por clase', 'Horario', 'Récord de Quechua', 'Todos los ciclos', 'Récord completo',
        ])
        ->assertSee(route('estudiante.reportes.avance', $this->matricula))
        ->assertSee(route('estudiante.reportes.asistencia', $this->matricula))
        ->assertSee(route('estudiante.reportes.horario', $this->periodo->id))
        ->assertSee(route('estudiante.reportes.horario.imprimir', $this->periodo->id))
        ->assertSee(route('estudiante.reportes.record', 'quechua'));
});

it('shows the profile summary at the side of every report and the profile and edit cards below, like the SGA', function (): void {
    $this->escenario['persona']->update(['correo' => 'alumno@example.test']);

    $this->actingAs($this->escenario['usuario'])
        ->get(route('estudiante.reportes'))
        ->assertOk()
        ->assertSeeInOrder([
            mb_strtoupper(str_replace(',', '', $this->escenario['persona']->nombre_completo)), 'Datos personales', 'alumno@example.test', 'Estado',
            'Reportes', 'Aquí encuentras todos tus reportes académicos', 'Periodo', 'Periodo seleccionado', 'Seguimiento académico',
            'Perfil', 'Ir a mi perfil', 'Editar datos', 'Modificar datos',
        ])
        ->assertSee(route('estudiante.perfil.datos'));
});

it('falls back to the newest period when the period in the address is not a number', function (): void {
    $this->actingAs($this->escenario['usuario'])
        ->get(route('estudiante.reportes', ['periodo' => 'abc']))
        ->assertOk()
        ->assertSee('Inglés 1 · ING-1-M');
});

it('disables the reports that do not apply yet and says why, instead of leading to an error', function (): void {
    $this->actingAs($this->escenario['usuario'])
        ->get(route('estudiante.reportes'))
        ->assertOk()
        ->assertSeeInOrder(['Récord de notas', 'Aún no apruebas ningún ciclo'])
        ->assertDontSee(route('estudiante.reportes.record'));
});

it('lists the syllabus of each course of the period', function (): void {
    $this->actingAs($this->escenario['usuario'])
        ->get(route('estudiante.reportes', ['ver' => 'silabos']))
        ->assertOk()
        ->assertSeeInOrder(['Sílabos', 'Inglés · Ciclo 1', 'ING-1-M · Prof. Vega Ríos, Carlos', 'Ver sílabo'])
        ->assertSee(route('estudiante.matriculas.silabo', $this->matricula));
});

it('shows the schedule and the approved cycles in their own tabs, reachable by link', function (): void {
    ActasFinales::historial($this->escenario['persona'], 'Quechua', [[1, '13.00', 'aprobado']]);

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes', ['ver' => 'horario']))
        ->assertOk()
        ->assertSeeInOrder(['Horario personalizado', 'Vega Ríos, Carlos', 'A-101 · Laboratorio 1', 'LUNES', '18:00 - 18:30'])
        ->assertDontSee('Promedio parcial');

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes', ['ver' => 'aprobado']))
        ->assertOk()
        ->assertSeeInOrder(['Quechua', 'Promedio de ciclos aprobados', '13.00', 'Ciclo 1', 'Básico', '13.00', 'Aprobado', 'Faltan los ciclos 2, 3 y 4 del nivel Básico', 'Informativo, sin valor oficial'])
        ->assertDontSee('Horario personalizado');

    Livewire::actingAs($this->escenario['usuario'])
        ->test(MisReportes::class)
        ->set('pestana', 'otra')
        ->assertSet('pestana', 'todos')
        ->assertSee('Periodo seleccionado');
});

it('tells a student who has not approved a cycle yet that the approved tab is empty', function (): void {
    Livewire::actingAs($this->escenario['usuario'])
        ->test(MisReportes::class)
        ->set('pestana', 'aprobado')
        ->assertSee('Aún no apruebas ningún ciclo.');
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
            && collect(['AVANCE DE NOTAS', 'Quispe Mamani, Rosa', 'DNI 41234567', 'ING-1-M', 'Marzo 2027', 'Examen parcial', '40.00', '15.00', 'Promedio parcial', '10.80'])->every(fn (string $texto): bool => $pdf->contains($texto)));
    });

    it('gives the report card of the period', function (): void {
        ReportesDelEstudiante::cerrarActa($this->escenario['curso']);

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.boleta', $this->periodo->id))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'boleta-de-notas-marzo-2027.pdf'
            && collect(['BOLETA DE NOTAS', 'Quispe Mamani, Rosa', 'Marzo 2027', 'ING-1-M', 'Inglés', 'Básico I', '60', '10.80', 'Desaprobado'])->every(fn (string $texto): bool => $pdf->contains($texto)));
    });

    it('prints the denomination of the year on the student reports', function (): void {
        DenominacionDelAnio::query()->create(['anio' => 2027, 'texto' => 'Año de la Prueba Sintética']);

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.avance', $this->matricula))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => collect(['«Año de la Prueba Sintética»', 'AVANCE DE NOTAS'])->every(fn (string $texto): bool => $pdf->contains($texto)));
    });

    it('gives the attendance of the enrollment with each class and the totals', function (): void {
        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.asistencia', $this->matricula))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'asistencia-ING-1-M.pdf'
            && collect(['REPORTE DE ASISTENCIA', '01/03/2027', '03/03/2027', '08/03/2027', 'Tema', 'Saludos y presentaciones', 'Presente', 'Ausente', 'sin confirmar', '67 %', '20 %'])->every(fn (string $texto): bool => $pdf->contains($texto)));
    });

    it('gives the weekly schedule of the period as PDF and as printable page', function (): void {
        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.horario', $this->periodo->id))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'horario-marzo-2027.pdf'
            && collect(['Horario personalizado', 'LUNES', 'MIÉRCOLES', '18:00 - 18:30', 'ING-1-M', 'Vega Ríos, Carlos', 'A-101 · Laboratorio 1', 'https://meet.example.test/ing-1-m'])->every(fn (string $texto): bool => $pdf->contains($texto)));

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.horario.imprimir', $this->periodo->id))
            ->assertOk()->assertSee('Horario personalizado')->assertSee('Vega Ríos, Carlos')->assertSee('window.print()', false);
    });

    it('gives the record of one language or of every language with the informative notice', function (): void {
        ActasFinales::historial($this->escenario['persona'], 'Quechua', [[1, '13.00', 'aprobado']]);
        ActasFinales::historial($this->escenario['persona'], 'Inglés', [[1, '17.00', 'aprobado']]);

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.record', 'quechua'))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'record-de-notas-quechua.pdf'
            && collect(['RÉCORD DE NOTAS', 'Quechua', '13.00', 'Faltan los ciclos 2, 3 y 4 del nivel Básico', 'Documento informativo, sin valor oficial; para trámites solicite la constancia de notas'])->every(fn (string $texto): bool => $pdf->contains($texto))
            && ! $pdf->contains('17.00'));

        $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.record'))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'record-de-notas.pdf' && collect(['Inglés', '17.00', 'Quechua', '13.00'])->every(fn (string $texto): bool => $pdf->contains($texto)));
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

it('leaves a withdrawn enrollment out of the reports, like the acta does', function (): void {
    $retirada = Matricula::factory()->retirada()->create(['persona_id' => $this->escenario['persona']->id, 'grupo_id' => Grupo::factory()->create(['codigo' => 'QUE-1-R', 'periodo_id' => $this->periodo->id])->id]);
    Pdf::fake();

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes'))->assertOk()->assertSee('ING-1-M')->assertDontSee('QUE-1-R');
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.avance', $retirada))->assertNotFound();
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.asistencia', $retirada))->assertNotFound();
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.boleta', $this->periodo->id))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => ! str_contains(json_encode($pdf->viewData, JSON_UNESCAPED_UNICODE) ?: '', 'QUE-1-R'));
});

it('hides a period where every enrollment of the student was withdrawn', function (): void {
    $otroPeriodo = Periodo::factory()->create(['nombre' => 'Abril 2027', 'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30']);
    Matricula::factory()->retirada()->create(['persona_id' => $this->escenario['persona']->id, 'grupo_id' => Grupo::factory()->create(['periodo_id' => $otroPeriodo->id])->id]);

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes'))->assertOk()->assertDontSee('Abril 2027');
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.reportes.boleta', $otroPeriodo->id))->assertNotFound();
});

it('renders one root element in every view so changing the period keeps the selector working', function (string $ver): void {
    $html = Livewire::actingAs($this->escenario['usuario'])->withQueryParams(['ver' => $ver])->test(MisReportes::class)->html();

    expect(ltrim($html))->toStartWith('<div')->not->toStartWith('<!--');
})->with(['todos', 'horario', 'avance', 'aprobado', 'silabos']);
