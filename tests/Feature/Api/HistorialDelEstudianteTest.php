<?php

declare(strict_types=1);

use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-10 10:00', 'America/Lima'));
    $this->usuario = User::factory()->create();
    $this->rosa = Persona::factory()->create(['user_id' => $this->usuario->id, 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa']);
    $this->otra = Persona::factory()->create();
    $this->token = $this->usuario->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
});

it('refuses guests and unverified accounts on every student endpoint', function (string $ruta): void {
    $this->getJson($ruta)->assertUnauthorized();

    $sinVerificar = User::factory()->unverified()->create();
    Persona::factory()->create(['user_id' => $sinVerificar->id]);

    $this->withToken($sinVerificar->createToken('App')->plainTextToken)->getJson($ruta)
        ->assertForbidden()
        ->assertJsonPath('message', 'Verifica tu correo antes de usar estos datos.');
})->with([
    '/api/v1/estudiante/perfil', '/api/v1/estudiante/matriculas', '/api/v1/estudiante/pagos',
    '/api/v1/estudiante/notas', '/api/v1/estudiante/tramites', '/api/v1/estudiante/documentos',
    '/api/v1/estudiante/documentos/CIL-0001/pdf',
]);

it('gives the student their final grades by language, only from closed grade sheets', function (): void {
    ActasFinales::historial($this->rosa, 'Inglés', [[1, '14.00', 'aprobado'], [2, null, 'nsp']]);
    ActasFinales::historial($this->rosa, 'Francés', [[1, '12.00', 'aprobado']], 'abierta');
    ActasFinales::historial($this->otra, 'Quechua', [[1, '18.00', 'aprobado']]);

    $this->withToken($this->token)->getJson('/api/v1/estudiante/notas')
        ->assertOk()
        ->assertExactJson(['data' => [[
            'idioma' => 'Inglés',
            'ciclos' => [
                ['ciclo' => 1, 'nombre' => 'Básico 1', 'nivel' => 'Básico', 'periodo' => 'Periodo 1', 'horas_lectivas' => 60, 'promedio' => '14.00', 'estado_final' => 'aprobado', 'estado_final_etiqueta' => 'Aprobado'],
                ['ciclo' => 2, 'nombre' => 'Básico 2', 'nivel' => 'Básico', 'periodo' => 'Periodo 2', 'horas_lectivas' => 60, 'promedio' => null, 'estado_final' => 'nsp', 'estado_final_etiqueta' => 'NSP'],
            ],
        ]]]);
});

it('lists only the student\'s own trámites with their stage, progress and observation', function (): void {
    $etapas = [['nombre' => 'Solicitud recibida', 'avance' => 0, 'condicion' => 'solicitud'], ['nombre' => 'En revisión', 'avance' => 50, 'condicion' => 'accion_del_personal'], ['nombre' => 'Listo', 'avance' => 100, 'condicion' => 'accion_del_personal']];
    $tramite = Tramite::factory()->create(['persona_id' => $this->rosa->id, 'tipo_nombre' => 'Constancia de notas', 'etapas' => $etapas, 'etapa_actual' => 1, 'estado' => EstadoTramite::Observado, 'observacion' => 'Adjunta el recibo legible.']);
    Tramite::factory()->create(['persona_id' => $this->otra->id, 'tipo_nombre' => 'Traslado ajeno']);

    $this->withToken($this->token)->getJson('/api/v1/estudiante/tramites')
        ->assertOk()
        ->assertDontSee('Traslado ajeno')
        ->assertExactJson(['data' => [[
            'codigo' => $tramite->fresh()?->codigo,
            'tipo' => 'Constancia de notas',
            'estado' => 'observado',
            'estado_etiqueta' => EstadoTramite::Observado->etiqueta(),
            'avance' => 50,
            'etapa' => 'En revisión',
            'etapas' => [['nombre' => 'Solicitud recibida', 'avance' => 0], ['nombre' => 'En revisión', 'avance' => 50], ['nombre' => 'Listo', 'avance' => 100]],
            'observacion' => 'Adjunta el recibo legible.',
            'documento_emitido' => false,
            'solicitado_en' => '2027-04-10T10:00:00-05:00',
            'finalizado_en' => null,
        ]]]);
});

it('lists the student\'s own issued documents and downloads only their own valid PDFs', function (): void {
    ActasFinales::historial($this->rosa, 'Inglés', [[1, '14.00', 'aprobado']]);
    ActasFinales::historial($this->otra, 'Inglés', [[1, '15.00', 'aprobado']]);
    $propia = app(EmitirConstanciaDeNotas::class)($this->rosa->id, 'Inglés', $this->emisor);
    $anulada = app(EmitirConstanciaDeNotas::class)($this->rosa->id, 'Inglés', $this->emisor);
    app(AnularDocumento::class)($anulada->id, 'Se emitió dos veces por error.', $this->emisor);
    $ajena = app(EmitirConstanciaDeNotas::class)($this->otra->id, 'Inglés', $this->emisor);

    $respuesta = $this->withToken($this->token)->getJson('/api/v1/estudiante/documentos')->assertOk()->assertDontSee($ajena->codigo);

    expect($respuesta->json('data.*.codigo'))->toEqualCanonicalizing([$propia->codigo, $anulada->codigo])
        ->and(collect($respuesta->json('data'))->firstWhere('codigo', $propia->codigo))->toBe([
            'codigo' => $propia->codigo, 'tipo' => 'constancia_notas', 'tipo_etiqueta' => $propia->tipo->etiqueta(), 'idioma' => 'Inglés', 'nivel' => null,
            'emitido_el' => '2027-04-10', 'vigente' => true, 'anulado_el' => null, 'verificacion' => route('verificacion.documento', $propia->codigo),
        ])
        ->and(collect($respuesta->json('data'))->firstWhere('codigo', $anulada->codigo)['vigente'])->toBeFalse();

    Pdf::fake();
    $this->withToken($this->token)->get("/api/v1/estudiante/documentos/{$propia->codigo}/pdf")->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === "constancia-de-notas-{$propia->codigo}.pdf");

    $this->withToken($this->token)->getJson("/api/v1/estudiante/documentos/{$ajena->codigo}/pdf")->assertNotFound();
    $this->withToken($this->token)->getJson("/api/v1/estudiante/documentos/{$anulada->codigo}/pdf")->assertNotFound();
});

it('lists trámites and documents newest first', function (): void {
    $antiguo = Tramite::factory()->create(['persona_id' => $this->rosa->id]);
    ActasFinales::historial($this->rosa, 'Inglés', [[1, '14.00', 'aprobado']]);
    $primera = app(EmitirConstanciaDeNotas::class)($this->rosa->id, 'Inglés', $this->emisor);
    $this->travel(1)->hour();
    $reciente = Tramite::factory()->create(['persona_id' => $this->rosa->id]);
    $segunda = app(EmitirConstanciaDeNotas::class)($this->rosa->id, 'Inglés', $this->emisor);

    expect($this->withToken($this->token)->getJson('/api/v1/estudiante/tramites')->json('data.*.codigo'))->toBe([$reciente->fresh()?->codigo, $antiguo->fresh()?->codigo])
        ->and($this->withToken($this->token)->getJson('/api/v1/estudiante/documentos')->json('data.*.codigo'))->toBe([$segunda->codigo, $primera->codigo]);
});

it('limits PDF downloads to 10 a minute per student', function (): void {
    foreach (range(1, 10) as $pedido) {
        $this->withToken($this->token)->getJson('/api/v1/estudiante/documentos/CIL-0001/pdf')->assertNotFound();
    }

    $this->withToken($this->token)->getJson('/api/v1/estudiante/documentos/CIL-0001/pdf')->assertTooManyRequests();
});

it('logs why a PDF of the student was refused, while still answering 404', function (): void {
    ActasFinales::historial($this->rosa, 'Inglés', [[1, '14.00', 'aprobado']]);
    $anulada = app(EmitirConstanciaDeNotas::class)($this->rosa->id, 'Inglés', $this->emisor);
    app(AnularDocumento::class)($anulada->id, 'Se emitió por error.', $this->emisor);
    Log::spy();

    $this->withToken($this->token)->getJson("/api/v1/estudiante/documentos/{$anulada->codigo}/pdf")->assertNotFound();

    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', Mockery::on(fn (array $contexto): bool => str_contains($contexto['regla'], 'está anulado')))->once();
});
