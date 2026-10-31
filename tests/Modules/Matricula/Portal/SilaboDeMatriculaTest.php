<?php

declare(strict_types=1);

use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ReportesDelEstudiante;
use Tests\Support\Silabos;

// Rosa Quispe is enrolled in ING-1-M (Inglés, ciclo 1, Marzo 2027) taught by Carlos Vega Ríos.
beforeEach(function (): void {
    $this->escenario = ReportesDelEstudiante::escenario();
    $this->matricula = $this->escenario['matricula'];
    $this->curso = $this->escenario['curso'];
});

it('offers the syllabus of an active enrollment only once the teacher publishes it', function (): void {
    $enlace = route('estudiante.matriculas.silabo', $this->matricula);

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.matriculas'))->assertOk()->assertDontSee($enlace);

    Silabos::borrador($this->curso['grupo'], $this->curso['docente']);
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.matriculas'))->assertOk()->assertDontSee($enlace);

    Silabos::publicar($this->curso['grupo'], $this->curso['docente'], $this->curso['usuario']);
    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.matriculas'))->assertOk()->assertSee($enlace)->assertSee('Sílabo');
});

it('shows the published syllabus with every section and a link to its PDF', function (): void {
    Silabos::publicar($this->curso['grupo'], $this->curso['docente'], $this->curso['usuario']);

    $this->actingAs($this->escenario['usuario'])
        ->get(route('estudiante.matriculas.silabo', $this->matricula))
        ->assertOk()
        ->assertSeeInOrder([
            'Sílabo', 'Inglés · Ciclo 1', route('estudiante.matriculas.silabo.pdf', $this->matricula),
            'I. Identificación', 'ING-1-M', 'Básico I', 'Marzo 2027 (1 mar 2027 al 31 mar 2027)', '60', 'Lun · Mié, 18:00–20:00', 'A-101 · Laboratorio 1', 'Vega Ríos, Carlos',
            'II. Sumilla, competencias y capacidades', 'Curso básico de inglés.', 'Se comunica en situaciones cotidianas.', 'Saluda y se presenta.',
            'III. Programación semanal', 'Semana 1', '1 – 7 mar', 'Unidad 1', 'Vocabulario 1', 'Diálogos 1', 'Semana 5', '29 – 31 mar',
            'IV. Evaluación', 'Examen parcial', '40 %', 'Indicadores de parcial', 'Rúbrica',
            'V. Metodología y bibliografía', 'Enfoque comunicativo.', 'Libro del curso.',
        ]);
});

it('gives the published syllabus as an inline PDF with every section', function (): void {
    Silabos::publicar($this->curso['grupo'], $this->curso['docente'], $this->curso['usuario']);
    Pdf::fake();

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.matriculas.silabo.pdf', $this->matricula))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->isInline() && $pdf->downloadName === 'silabo-ING-1-M.pdf' && $pdf->viewName === 'docencia::pdf.silabo'
        && collect([
            'SÍLABO', 'I. Identificación', 'ING-1-M', 'Inglés', 'Básico I', 'Marzo 2027 (1 mar 2027 al 31 mar 2027)', 'Vega Ríos, Carlos', 'A-101 · Laboratorio 1',
            'II. Sumilla, competencias y capacidades', 'Curso básico de inglés.', 'III. Programación semanal', 'Saber hacer', 'Saber ser', 'Recursos', '29 – 31 mar', 'Diálogos 5',
            'IV. Evaluación', 'Peso (%)', 'Indicadores de final', 'Rúbrica', 'V. Metodología', 'Enfoque comunicativo.', 'Bibliografía', 'Libro del curso.',
        ])->every(fn (string $texto): bool => $pdf->contains($texto))
        && ! $pdf->contains('BORRADOR'));
});

it('answers not found for a draft or a missing syllabus', function (string $ruta): void {
    Pdf::fake();
    $this->actingAs($this->escenario['usuario'])->get(route($ruta, $this->matricula))->assertNotFound();

    Silabos::borrador($this->curso['grupo'], $this->curso['docente']);
    $this->actingAs($this->escenario['usuario'])->get(route($ruta, $this->matricula))->assertNotFound();
})->with(['estudiante.matriculas.silabo', 'estudiante.matriculas.silabo.pdf']);

it('hides the syllabus from another student', function (string $ruta): void {
    Silabos::publicar($this->curso['grupo'], $this->curso['docente'], $this->curso['usuario']);
    $otro = User::factory()->create();
    Persona::factory()->create(['user_id' => $otro->id]);
    Pdf::fake();

    $this->actingAs($otro)->get(route($ruta, $this->matricula))->assertNotFound();
})->with(['estudiante.matriculas.silabo', 'estudiante.matriculas.silabo.pdf']);

it('leaves a withdrawn enrollment without the syllabus', function (string $ruta): void {
    Silabos::publicar($this->curso['grupo'], $this->curso['docente'], $this->curso['usuario']);
    Matricula::query()->whereKey($this->matricula->id)->update(['estado' => 'retirada', 'retirado_en' => now()]);
    Pdf::fake();

    $this->actingAs($this->escenario['usuario'])->get(route('estudiante.matriculas'))->assertOk()->assertDontSee(route('estudiante.matriculas.silabo', $this->matricula));
    $this->actingAs($this->escenario['usuario'])->get(route($ruta, $this->matricula))->assertNotFound();
})->with(['estudiante.matriculas.silabo', 'estudiante.matriculas.silabo.pdf']);

it('keeps the syllabus behind the portal login', function (string $ruta): void {
    $this->get(route($ruta, $this->matricula))->assertRedirect(route('ingresar'));
})->with(['estudiante.matriculas.silabo', 'estudiante.matriculas.silabo.pdf']);
