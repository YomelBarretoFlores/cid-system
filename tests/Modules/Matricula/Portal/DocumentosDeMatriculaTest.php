<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\FakePdfBuilder;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * @return array{0: User, 1: Matricula}
 */
function estudianteMatriculado(string $estadoDelPago = 'validado', string $estado = 'activa'): array
{
    $user = User::factory()->create();
    $persona = Persona::factory()->create([
        'user_id' => $user->id, 'numero_documento' => '41234567',
        'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa Elena',
    ]);
    $ciclo = Ciclo::factory()->for(Idioma::factory()->state(['nombre' => 'Inglés']))->create(['numero' => 2]);
    $grupo = Grupo::factory()->for($ciclo)->for(Periodo::factory()->state(['nombre' => 'Enero 2027']))->create(['codigo' => 'ING-II-A']);
    Horario::factory()->for($grupo)->create(['dia_semana' => 2, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    $pago = Pago::factory()->{$estadoDelPago}()->for(Concepto::factory()->state(['nombre' => 'Derecho de matrícula']))
        ->create(['persona_id' => $persona->id, 'numero_operacion' => 'OP-778899', 'monto' => '80.00']);

    $matricula = Matricula::factory()->{$estado}()->create([
        'persona_id' => $persona->id, 'grupo_id' => $grupo->id, 'pago_id' => $pago->id,
        'condicion' => CondicionMatricula::Regular, 'matriculado_en' => '2026-10-20 15:30:00+00',
    ]);

    return [$user, $matricula];
}

beforeEach(fn (): FakePdfBuilder => Pdf::fake());

it('gives the student the enrollment record with student, group, date, condition and payment state', function (): void {
    [$user, $matricula] = estudianteMatriculado();

    $this->actingAs($user)->get(route('estudiante.matriculas.ficha', $matricula))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->isInline()
        && $pdf->downloadName === 'ficha-de-matricula-ING-II-A.pdf'
        && $pdf->contains([
            'FICHA DE MATRÍCULA', $matricula->ulid,
            'Quispe Mamani, Rosa Elena', 'DNI 41234567',
            'ING-II-A', 'Inglés', 'Ciclo 2', 'Enero 2027', 'Martes de 18:00 a 20:00',
            '20/10/2026 10:30', 'Regular', 'Activa',
            'Verificado', 'Derecho de matrícula', 'OP-778899', 'S/ 80.00',
        ]));
});

it('shows the current state of a payment voided after the enrollment', function (): void {
    [$user, $matricula] = estudianteMatriculado(estadoDelPago: 'anulado');

    $this->actingAs($user)->get(route('estudiante.matriculas.ficha', $matricula))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains('Anulado') && ! $pdf->contains('Verificado'));
});

it('gives the student a certificate of an active enrollment', function (): void {
    $this->travelTo(now()->setTimezone('America/Lima')->setDate(2026, 10, 24)->setTime(9, 0));
    [$user, $matricula] = estudianteMatriculado();

    $this->actingAs($user)->get(route('estudiante.matriculas.constancia', $matricula))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'constancia-de-inscripcion-ING-II-A.pdf'
        && $pdf->contains([
            'CONSTANCIA DE INSCRIPCIÓN', 'Quispe Mamani, Rosa Elena', 'DNI 41234567',
            'ING-II-A', 'Inglés', 'Ciclo 2', 'Enero 2027', 'Huaraz, 24 de octubre de 2026', $matricula->ulid,
        ]));
});

it('refuses a certificate for a withdrawn enrollment', function (): void {
    [$user, $matricula] = estudianteMatriculado(estado: 'retirada');

    $this->actingAs($user)
        ->get(route('estudiante.matriculas.constancia', $matricula))
        ->assertRedirect(route('estudiante.matriculas'))
        ->assertSessionHas('error', 'La constancia de inscripción solo se emite para matrículas activas.');

    Pdf::assertNotQueued();
});

it('hides the documents of another student', function (string $ruta): void {
    [, $ajena] = estudianteMatriculado();
    $otro = User::factory()->create();
    Persona::factory()->create(['user_id' => $otro->id]);

    $this->actingAs($otro)->get(route($ruta, $ajena))->assertNotFound();
})->with(['estudiante.matriculas.ficha', 'estudiante.matriculas.constancia']);

it('keeps the documents behind the portal login', function (): void {
    [, $matricula] = estudianteMatriculado();

    $this->get(route('estudiante.matriculas.ficha', $matricula))->assertRedirect(route('ingresar'));
});

it('links the record for every enrollment and the certificate only for active ones', function (): void {
    [$user, $activa] = estudianteMatriculado();
    $retirada = Matricula::factory()->retirada()->create(['persona_id' => $activa->persona_id]);

    $this->actingAs($user)
        ->get(route('estudiante.matriculas'))
        ->assertOk()
        ->assertSee(route('estudiante.matriculas.ficha', $activa))
        ->assertSee(route('estudiante.matriculas.constancia', $activa))
        ->assertSee(route('estudiante.matriculas.ficha', $retirada))
        ->assertDontSee(route('estudiante.matriculas.constancia', $retirada));
});

it('renders both documents as real PDF files', function (string $ruta): void {
    Pdf::swap(app(PdfBuilder::class));
    [$user, $matricula] = estudianteMatriculado();

    expect($this->actingAs($user)->get(route($ruta, $matricula))->assertOk()->getContent())->toStartWith('%PDF');
})->with(['estudiante.matriculas.ficha', 'estudiante.matriculas.constancia']);
