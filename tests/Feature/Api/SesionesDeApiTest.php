<?php

declare(strict_types=1);

use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-20 10:00', 'America/Lima'));
    $this->estudiante = User::factory()->create(['email' => 'rosa@ciln.test']);
    Persona::factory()->create(['user_id' => $this->estudiante->id]);
});

it('issues a 30-day personal token for valid credentials, named after the device', function (): void {
    $respuesta = $this->postJson('/api/v1/sesiones', ['email' => ' Rosa@CILN.test ', 'password' => 'password', 'dispositivo' => 'App móvil'])
        ->assertCreated()
        ->assertJsonPath('tipo', 'Bearer')
        ->assertJsonPath('expira_en', '2027-04-19T10:00:00-05:00')
        ->assertJsonPath('perfiles', ['estudiante' => true, 'docente' => false]);

    $token = PersonalAccessToken::findToken((string) $respuesta->json('token'));

    expect($token?->tokenable_id)->toBe($this->estudiante->id)
        ->and($token?->name)->toBe('App móvil')
        ->and($token?->expires_at?->toIso8601String())->toBe(CarbonImmutable::parse('2027-04-19 15:00', 'UTC')->toIso8601String());
});

it('refuses wrong credentials with the sign-in message and stops after five attempts', function (): void {
    foreach (range(1, 5) as $intento) {
        $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'otra-clave', 'dispositivo' => 'App'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', IniciarSesion::CREDENCIALES_INCORRECTAS);
    }

    $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'App'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', fn (string $mensaje): bool => str_starts_with($mensaje, 'Demasiados intentos.'));

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('shares the attempt limit with the web sign-in', function (): void {
    RateLimiter::increment('ingreso|rosa@ciln.test|127.0.0.1', amount: 5);

    $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'App'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', fn (string $mensaje): bool => str_starts_with($mensaje, 'Demasiados intentos.'));
});

it('requires the e-mail, the password and the device name', function (): void {
    $this->postJson('/api/v1/sesiones', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'dispositivo']);
});

it('tells the profiles of the account behind the token and refuses requests without one', function (): void {
    [$usuarioDocente] = PortalDocente::docente();
    $token = $usuarioDocente->createToken('Tableta', expiresAt: now()->addDays(30))->plainTextToken;

    $this->getJson('/api/v1/sesiones/actual')->assertUnauthorized();
    $this->withToken($token)->getJson('/api/v1/sesiones/actual')
        ->assertOk()
        ->assertJsonPath('perfiles', ['estudiante' => true, 'docente' => true])
        ->assertJsonPath('dispositivo', 'Tableta')
        ->assertJsonMissingPath('email');
});

it('revokes the current token so it no longer works', function (): void {
    $token = $this->estudiante->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;

    $this->withToken($token)->deleteJson('/api/v1/sesiones/actual')->assertNoContent();
    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/sesiones/actual')->assertUnauthorized();
    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('refuses an expired token', function (): void {
    $token = $this->estudiante->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;
    $this->travel(31)->days();

    $this->withToken($token)->getJson('/api/v1/sesiones/actual')->assertUnauthorized();
});

it('limits each client to 60 requests a minute', function (): void {
    $token = $this->estudiante->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;

    foreach (range(1, 60) as $pedido) {
        $this->withToken($token)->getJson('/api/v1/sesiones/actual')->assertOk();
    }

    $this->withToken($token)->getJson('/api/v1/sesiones/actual')->assertTooManyRequests();
});

it('shows the API documentation to staff with panel access only', function (): void {
    $personal = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->get('/docs/api')->assertForbidden();
    $this->actingAs($this->estudiante)->get('/docs/api')->assertForbidden();
    $this->actingAs($personal)->get('/docs/api')->assertOk();
    $this->actingAs($personal)->getJson('/docs/api.json')->assertOk()->assertJsonPath('info.title', 'API del CILN');
});

it('schedules the daily removal of expired tokens', function (): void {
    $tarea = collect(app(Schedule::class)->events())->first(fn ($evento): bool => str_contains((string) $evento->command, 'sanctum:prune-expired'));

    expect($tarea)->not->toBeNull()
        ->and($tarea?->expression)->toBe('20 0 * * *')
        ->and($tarea?->timezone)->toBe('America/Lima');
});

it('emits the failed sign-in event for wrong credentials on the API and on the web, as Auth::attempt did', function (): void {
    Event::fake([Attempting::class, Failed::class]);

    $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'otra-clave', 'dispositivo' => 'App'])->assertUnprocessable();
    Livewire::test(IniciarSesion::class)->set('email', 'rosa@ciln.test')->set('password', 'otra-clave')->call('ingresar');

    Event::assertDispatchedTimes(Attempting::class, 2);
    Event::assertDispatchedTimes(Failed::class, 2);
    Event::assertDispatched(Failed::class, fn (Failed $evento): bool => $evento->guard === 'web' && $evento->user?->getAuthIdentifier() === $this->estudiante->id);
});

it('upgrades an outdated password hash when the credentials are right', function (): void {
    User::query()->whereKey($this->estudiante->id)->update(['password' => Hash::make('password', ['rounds' => 5])]);

    $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'App'])->assertCreated();

    expect(Hash::needsRehash((string) $this->estudiante->fresh()?->password))->toBeFalse();
});

it('blocks an address that tries many accounts through the API, and a success clears only the account key', function (): void {
    RateLimiter::increment('ingreso|rosa@ciln.test|127.0.0.1', amount: 2);
    RateLimiter::increment('ingreso|127.0.0.1', amount: 2);

    $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'App'])->assertCreated();

    expect(RateLimiter::attempts('ingreso|rosa@ciln.test|127.0.0.1'))->toBe(0)
        ->and(RateLimiter::attempts('ingreso|127.0.0.1'))->toBe(2);

    foreach (range(3, 20) as $intento) {
        $this->postJson('/api/v1/sesiones', ['email' => "persona{$intento}@ciln.test", 'password' => 'mala-clave1', 'dispositivo' => 'App'])->assertUnprocessable();
    }

    $this->postJson('/api/v1/sesiones', ['email' => 'otra@ciln.test', 'password' => 'mala-clave1', 'dispositivo' => 'App'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', fn (string $mensaje): bool => str_starts_with($mensaje, 'Demasiados intentos.'));
});

it('creates no token when the profiles of the account cannot be read', function (): void {
    $this->mock(FichaDelUsuario::class)->shouldReceive('__invoke')->andThrow(new RuntimeException('falla de lectura'));

    $this->withoutExceptionHandling();

    expect(fn () => $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'App']))->toThrow(RuntimeException::class)
        ->and(PersonalAccessToken::query()->count())->toBe(0);
});

it('says when a session without a token is asked to revoke it instead of pretending', function (): void {
    $this->actingAs($this->estudiante, 'sanctum');

    $this->deleteJson('/api/v1/sesiones/actual')
        ->assertConflict()
        ->assertJsonPath('message', 'Esta sesión no usa un token que se pueda revocar.');
});

it('replaces the previous token of the same device instead of piling up live tokens', function (): void {
    $primero = $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'App móvil'])->json('token');
    $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'Tableta'])->assertCreated();
    $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'App móvil'])->assertCreated();

    expect(PersonalAccessToken::query()->orderBy('name')->pluck('name')->all())->toBe(['App móvil', 'Tableta'])
        ->and(PersonalAccessToken::findToken((string) $primero))->toBeNull();
});

it('never revokes or describes a token of another account', function (): void {
    $ajena = User::factory()->create();
    $tokenAjeno = $ajena->createToken('Ajeno', expiresAt: now()->addDays(30))->plainTextToken;
    $this->actingAs($this->estudiante, 'sanctum');

    $this->withToken($tokenAjeno)->deleteJson('/api/v1/sesiones/actual')->assertConflict();
    $this->withToken($tokenAjeno)->getJson('/api/v1/sesiones/actual')->assertOk()->assertJsonPath('dispositivo', null);

    expect(PersonalAccessToken::findToken($tokenAjeno))->not->toBeNull();
});
