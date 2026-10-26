<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Ficha;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function (): void {
    Pdf::fake();
    $this->usuario = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $this->usuario->id, 'numero_documento' => '41234567', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa']);
    $grupo = Grupo::factory()->for(Ciclo::factory()->for(Idioma::factory()->state(['nombre' => 'Inglés'])))->for(Periodo::factory()->state(['nombre' => 'Enero 2027']))
        ->create(['codigo' => 'ING-I-A', 'modalidad' => Modalidad::Virtual, 'aula_id' => null, 'enlace_virtual' => 'https://meet.example.test/clase']);
    $this->operacion = 'OP-PRIVADA-1';
    $this->matricula = Matricula::factory()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id, 'pago_id' => Pago::factory()->validado()->create(['persona_id' => $persona->id, 'numero_operacion' => $this->operacion])->id]);
});

it('prints on the record the verification address and the QR that opens it again', function (): void {
    $this->actingAs($this->usuario)->get(route('estudiante.matriculas.ficha', $this->matricula))->assertOk();
    $codigo = Ficha::query()->value('codigo');

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains(route('fichas.verificar', $codigo))
        && $pdf->contains('Puede verificar la validez de esta ficha')
        && $pdf->contains('data:image/png;base64,')
        && $pdf->contains('DNI 41234567')
        && $pdf->contains('OP-PRIVADA-1')
        && $pdf->contains('https://meet.example.test/clase'));
});

it('opens the same record from its verification address without signing in, with the document number masked', function (): void {
    $this->actingAs($this->usuario)->get(route('estudiante.matriculas.ficha', $this->matricula))->assertOk();
    auth()->logout();
    $codigo = Ficha::query()->value('codigo');

    $this->get(route('fichas.verificar', $codigo))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->isInline()
        && $pdf->contains('Ficha de Matrícula')
        && $pdf->contains('QUISPE MAMANI, ROSA')
        && $pdf->contains('DNI ••••4567')
        && $pdf->contains('Verificado')
        && ! $pdf->contains('41234567')
        && ! $pdf->contains($this->operacion)
        && ! $pdf->contains('meet.example.test'));
});

it('answers the public copy as a real PDF that search engines do not index', function (): void {
    Pdf::swap(app(PdfBuilder::class));
    $this->actingAs($this->usuario)->get(route('estudiante.matriculas.ficha', $this->matricula))->assertOk();
    auth()->logout();

    $respuesta = $this->get(route('fichas.verificar', Ficha::query()->value('codigo')))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect($respuesta->getContent())->toStartWith('%PDF');
});

it('answers not found for an unknown or malformed code', function (string $codigo): void {
    $this->get('/fichas/'.$codigo)->assertNotFound();
})->with(['unknown' => '01KX0000000000000000000000', 'malformed' => 'no-es-un-codigo']);

it('limits how often the verification address answers', function (): void {
    foreach (range(1, 30) as $intento) {
        $this->get('/fichas/01KX0000000000000000000000')->assertNotFound();
    }

    $this->get('/fichas/01KX0000000000000000000000')->assertTooManyRequests();
});
