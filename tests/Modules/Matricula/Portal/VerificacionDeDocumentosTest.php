<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Testing\TestResponse;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\FakePdfBuilder;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * @return array{0: User, 1: Matricula}
 */
function verifEstudiante(array $matricula = [], array $grupo = [], ?string $estadoPago = 'validado', bool $verificado = true): array
{
    $user = $verificado ? User::factory()->create() : User::factory()->unverified()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);
    $pagoId = $estadoPago === null ? null : Pago::factory()->{$estadoPago}()->create(['persona_id' => $persona->id])->id;

    return [$user, Matricula::factory()->create([
        'persona_id' => $persona->id, 'grupo_id' => Grupo::factory()->create($grupo)->id, 'pago_id' => $pagoId, ...$matricula,
    ])];
}

function verifSnapshot(TestResponse $pagina): string
{
    preg_match('/wire:snapshot="([^"]+)"/', $pagina->getContent(), $coincidencia);

    return html_entity_decode($coincidencia[1], ENT_QUOTES);
}

beforeEach(fn (): FakePdfBuilder => Pdf::fake());

it('P1 hides another student documents with 404', function (string $ruta): void {
    [, $ajena] = verifEstudiante();
    [$otro] = verifEstudiante();

    $this->actingAs($otro)->get(route($ruta, $ajena))->assertNotFound();
    Pdf::assertNotQueued();
})->with(['estudiante.matriculas.ficha', 'estudiante.matriculas.constancia']);

it('P2 sends an unverified account to the verification notice', function (string $ruta): void {
    [$user, $matricula] = verifEstudiante(verificado: false);

    $this->actingAs($user)->get(route($ruta, $matricula))->assertRedirect(route('verification.notice'));
})->with(['estudiante.matriculas.ficha', 'estudiante.matriculas.constancia']);

it('P3 refuses an account without a person record', function (string $ruta): void {
    [, $matricula] = verifEstudiante();

    $this->actingAs(User::factory()->create())->get(route($ruta, $matricula))->assertForbidden();
})->with(['estudiante.matriculas.ficha', 'estudiante.matriculas.constancia']);

it('P4 redirects a guest to the portal login', function (string $ruta): void {
    [, $matricula] = verifEstudiante();

    $this->get(route($ruta, $matricula))->assertRedirect(route('ingresar'));
})->with(['estudiante.matriculas.ficha', 'estudiante.matriculas.constancia']);

it('P5 refuses a constancia for a withdrawn enrollment and shows the error flash', function (): void {
    [$user, $matricula] = verifEstudiante(['estado' => 'retirada', 'retirado_en' => now()]);

    $this->actingAs($user)->followingRedirects()->get(route('estudiante.matriculas.constancia', $matricula))
        ->assertOk()->assertSee('La constancia de inscripción solo se emite para matrículas activas.');
    Pdf::assertNotQueued();
});

it('P6 gives the ficha of a withdrawn enrollment with its state', function (): void {
    [$user, $matricula] = verifEstudiante(['estado' => 'retirada', 'retirado_en' => now()]);

    $this->actingAs($user)->get(route('estudiante.matriculas.ficha', $matricula))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains('Retirada') && ! $pdf->contains('Activa'));
});

it('P7 shows an enrollment without payment as Sin pago registrado', function (): void {
    [$user, $matricula] = verifEstudiante(estadoPago: null);

    $this->actingAs($user)->get(route('estudiante.matriculas.ficha', $matricula))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains('Sin pago registrado') && ! $pdf->contains('Número de operación'));
});

it('P8 shows payment states', function (string $estado, string $etiqueta): void {
    [$user, $matricula] = verifEstudiante(estadoPago: $estado);

    $this->actingAs($user)->get(route('estudiante.matriculas.ficha', $matricula))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains($etiqueta));
})->with([['anulado', 'Anulado'], ['pendiente', 'Pendiente'], ['validado', 'Verificado']]);

it('P9 shows the link of a virtual group', function (): void {
    [$user, $matricula] = verifEstudiante(grupo: ['modalidad' => Modalidad::Virtual, 'enlace_virtual' => 'https://meet.example.test/abc-def']);

    $this->actingAs($user)->get(route('estudiante.matriculas.ficha', $matricula))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains(['Clase virtual', 'https://meet.example.test/abc-def', 'Virtual']));
});

it('P10 prints the enrollment time in America/Lima across a date boundary', function (string $ruta, string $texto): void {
    [$user, $matricula] = verifEstudiante(['matriculado_en' => '2026-10-20 03:30:00+00']);

    $this->actingAs($user)->get(route($ruta, $matricula))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains($texto) && ! $pdf->contains('20/10/2026'));
})->with([['estudiante.matriculas.ficha', '19/10/2026 22:30'], ['estudiante.matriculas.constancia', 'registrada el 19/10/2026']]);

it('P11 answers a real inline PDF', function (string $ruta): void {
    Pdf::swap(app(PdfBuilder::class));
    [$user, $matricula] = verifEstudiante();

    $respuesta = $this->actingAs($user)->get(route($ruta, $matricula))->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect($respuesta->getContent())->toStartWith('%PDF')
        ->and($respuesta->headers->get('Content-Disposition'))->toStartWith('inline');
})->with(['estudiante.matriculas.ficha', 'estudiante.matriculas.constancia']);

it('P12 answers 404 for a numeric id, a garbage key and a case-changed ulid', function (string $ruta): void {
    [$user, $matricula] = verifEstudiante();

    expect(route($ruta, $matricula))->toContain($matricula->ulid);
    $this->actingAs($user)->get("/estudiante/matriculas/{$matricula->id}/".str($ruta)->afterLast('.'))->assertNotFound();
    $this->actingAs($user)->get('/estudiante/matriculas/%27%20or%201=1--/'.str($ruta)->afterLast('.'))->assertNotFound();
    $otraCaja = $matricula->ulid === strtolower($matricula->ulid) ? strtoupper($matricula->ulid) : strtolower($matricula->ulid);
    $this->actingAs($user)->get("/estudiante/matriculas/{$otraCaja}/".str($ruta)->afterLast('.'))->assertNotFound();
})->with(['estudiante.matriculas.ficha', 'estudiante.matriculas.constancia']);

it('P13 does not leak document links to an unverified account replaying the Mis matriculas snapshot', function (): void {
    [$verificado] = verifEstudiante();
    $snapshot = verifSnapshot($this->actingAs($verificado)->get(route('estudiante.matriculas'))->assertOk());
    [$sinVerificar, $victima] = verifEstudiante(verificado: false);

    $this->actingAs($sinVerificar);
    $respuesta = test()->withHeaders(['X-Livewire' => '1'])
        ->postJson(route('default-livewire.update'), ['components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => [['method' => '$refresh', 'params' => [], 'metadata' => []]]]]]);

    expect($respuesta->getContent())->not->toContain($victima->ulid);
});

it('P14 escapes person data in the PDF', function (): void {
    [$user, $matricula] = verifEstudiante();
    Persona::query()->whereKey($matricula->persona_id)->update(['nombres' => '<script>x</script>']);

    $this->actingAs($user)->get(route('estudiante.matriculas.ficha', $matricula))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains('&lt;script&gt;') && ! $pdf->contains('<script>x'));
});
