<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;

$politica = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; worker-src 'self' blob:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'";

$cabecerasFijas = [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
];

// The suite's Vite fake reads the real public/hot, which exists whenever a developer runs the dev server.
function servidorDeVite(bool $enCaliente): void
{
    test()->swap(Vite::class, new class($enCaliente) extends Vite
    {
        public function __construct(private readonly bool $enCaliente) {}

        public function isRunningHot(): bool
        {
            return $this->enCaliente;
        }

        public function __invoke($entrypoints, $buildDirectory = null): HtmlString
        {
            return new HtmlString('');
        }

        public function fonts($aliases = null): HtmlString
        {
            return new HtmlString('');
        }

        public function asset($asset, $buildDirectory = null): string
        {
            return '';
        }
    });
}

beforeEach(fn () => servidorDeVite(false));

it('sends the security headers and the content policy on portal pages, the API and the staff panel', function (string $ruta) use ($politica, $cabecerasFijas): void {
    $respuesta = $this->get($ruta)->assertOk();

    foreach ($cabecerasFijas as $cabecera => $valor) {
        $respuesta->assertHeader($cabecera, $valor);
    }

    $respuesta->assertHeader('Content-Security-Policy', $politica)->assertHeaderMissing('Strict-Transport-Security');
})->with([
    'public homepage' => ['/'],
    'portal sign-in' => ['/ingresar'],
    'API' => ['/api/v1/tarifas'],
    'staff panel sign-in' => ['/admin/login'],
]);

it('asks browsers to keep using https only on secure requests', function (): void {
    $this->get('https://localhost/ingresar')->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    $this->get('http://localhost/ingresar')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
});

it('keeps a header the response already set', function (): void {
    Route::middleware('web')->get('/_prueba/incrustable', fn () => response('ok')->header('X-Frame-Options', 'DENY'));

    $this->get('/_prueba/incrustable')->assertHeader('X-Frame-Options', 'DENY');
});

it('leaves the content policy out of the API documentation, which loads its viewer from a CDN, and keeps the other headers', function () use ($cabecerasFijas): void {
    $personal = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $respuesta = $this->actingAs($personal)->get('/docs/api')->assertOk()->assertHeaderMissing('Content-Security-Policy');

    foreach ($cabecerasFijas as $cabecera => $valor) {
        $respuesta->assertHeader($cabecera, $valor);
    }
});

it('leaves the content policy out while the Vite dev server serves the assets', function (): void {
    servidorDeVite(true);

    $this->get('/ingresar')->assertOk()->assertHeaderMissing('Content-Security-Policy')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});
