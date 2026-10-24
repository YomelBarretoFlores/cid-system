<?php

declare(strict_types=1);

use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Monolog\Handler\TestHandler;

beforeEach(function (): void {
    Route::middleware('web')->get('/_prueba/solicitud', fn (): string => (string) Context::get('solicitud_id'));
    Route::middleware('web')->get('/_prueba/auditada', fn (): string => (string) Docente::factory()->create()->id);
});

it('gives every request an ID that the response returns and every log line carries, next to the proxy origin', function (): void {
    config(['logging.channels.prueba' => ['driver' => 'monolog', 'handler' => TestHandler::class], 'logging.default' => 'prueba']);
    Route::middleware('web')->get('/_prueba/log', function (): string {
        Log::info('Línea de prueba.');

        return 'ok';
    });

    $id = $this->withHeader('X-Request-Id', 'proxy-0001-abcd')->get('/_prueba/log')->assertOk()->headers->get('X-Request-Id');
    $registro = collect(Log::channel('prueba')->getLogger()->getHandlers()[0]->getRecords())->firstWhere('message', 'Línea de prueba.');

    expect(Str::isUlid((string) $id))->toBeTrue()
        ->and($registro->extra['solicitud_id'] ?? null)->toBe($id)
        ->and($registro->extra['solicitud_origen'] ?? null)->toBe('proxy-0001-abcd');
});

it('generates its own ID even when a client sends one, and keeps a well-formed incoming ID only as the origin', function (string $enviado, ?string $origen): void {
    Route::middleware('web')->get('/_prueba/origen', fn (): string => (string) Context::get('solicitud_origen'));

    $respuesta = $this->withHeader('X-Request-Id', $enviado)->get('/_prueba/origen');
    $id = (string) $respuesta->headers->get('X-Request-Id');

    expect(Str::isUlid($id))->toBeTrue()
        ->and($id)->not->toBe($enviado)
        ->and($respuesta->getContent())->toBe((string) $origen);
})->with([
    'uuid from a proxy' => ['3f1c2a9e-8b7d-4c6e-9a51-0d2e4f6a8b1c', '3f1c2a9e-8b7d-4c6e-9a51-0d2e4f6a8b1c'],
    'too long' => [str_repeat('a', 65), null],
    'with spaces' => ['id con espacios', null],
    'with a newline' => ["abc12345\nX-Inyectada: 1", null],
    'too short' => ['abc', null],
]);

it('gives two requests that send the same ID different audit IDs', function (): void {
    $primera = $this->withHeader('X-Request-Id', 'misma-solicitud-1')->get('/_prueba/solicitud')->headers->get('X-Request-Id');
    $segunda = $this->withHeader('X-Request-Id', 'misma-solicitud-1')->get('/_prueba/solicitud')->headers->get('X-Request-Id');

    expect($primera)->not->toBe($segunda);
});

it('stores the request ID in each audit row the request writes', function (): void {
    $respuesta = $this->get('/_prueba/auditada')->assertOk();

    expect(DB::table('aud_registros')->where('tabla', 'doc_docentes')->where('registro_id', (int) $respuesta->getContent())->value('solicitud_id'))
        ->toBe($respuesta->headers->get('X-Request-Id'));
});

it('leaves the request ID empty in audit rows written outside a request', function (): void {
    $docente = Docente::factory()->create();

    expect(DB::table('aud_registros')->where('tabla', 'doc_docentes')->where('registro_id', $docente->id)->value('solicitud_id'))->toBeNull();
});

it('gives panel page requests an ID too', function (): void {
    $admin = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $id = $this->actingAs($admin)->get('/admin')->assertOk()->headers->get('X-Request-Id');

    expect(Str::isUlid((string) $id))->toBeTrue();
});
