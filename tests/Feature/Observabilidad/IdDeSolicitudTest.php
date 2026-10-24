<?php

declare(strict_types=1);

use App\Modules\Docencia\Models\Docente;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Route::middleware('web')->get('/_prueba/solicitud', fn (): string => (string) Context::get('solicitud_id'));
    Route::middleware('web')->get('/_prueba/auditada', fn (): string => (string) Docente::factory()->create()->id);
});

it('gives every request an ID that the response returns and the logs carry', function (): void {
    $respuesta = $this->get('/_prueba/solicitud')->assertOk();
    $id = $respuesta->headers->get('X-Request-Id');

    expect(Str::isUlid((string) $id))->toBeTrue()
        ->and($respuesta->getContent())->toBe($id);
});

it('keeps a valid request ID sent by a proxy and replaces an unsafe one', function (string $enviado, bool $seConserva): void {
    $id = $this->withHeader('X-Request-Id', $enviado)->get('/_prueba/solicitud')->headers->get('X-Request-Id');

    expect($id === $enviado)->toBe($seConserva)
        ->and($seConserva || Str::isUlid((string) $id))->toBeTrue();
})->with([
    'uuid from a proxy' => ['3f1c2a9e-8b7d-4c6e-9a51-0d2e4f6a8b1c', true],
    'too long' => [str_repeat('a', 65), false],
    'with spaces' => ['id con espacios', false],
    'with a newline' => ["abc12345\nX-Inyectada: 1", false],
    'too short' => ['abc', false],
]);

it('stores the request ID in each audit row the request writes', function (): void {
    $respuesta = $this->get('/_prueba/auditada')->assertOk();

    expect(DB::table('aud_registros')->where('tabla', 'doc_docentes')->where('registro_id', (int) $respuesta->getContent())->value('solicitud_id'))
        ->toBe($respuesta->headers->get('X-Request-Id'));
});

it('leaves the request ID empty in audit rows written outside a request', function (): void {
    $docente = Docente::factory()->create();

    expect(DB::table('aud_registros')->where('tabla', 'doc_docentes')->where('registro_id', $docente->id)->value('solicitud_id'))->toBeNull();
});
