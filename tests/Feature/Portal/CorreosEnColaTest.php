<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Notifications\CorreoDeRestablecimiento;
use App\Shared\Auth\Notifications\CorreoDeVerificacion;
use Illuminate\Http\Request;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

it('sends the e-mail verification and the password reset through the queue, after commit and with retries', function (): void {
    Queue::fake();
    $usuario = User::factory()->unverified()->create();

    $usuario->sendEmailVerificationNotification();
    $usuario->sendPasswordResetNotification('token-de-prueba');

    Queue::assertPushed(SendQueuedNotifications::class, 2);
    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $trabajo): bool => $trabajo->notification instanceof CorreoDeVerificacion
        && $trabajo->notification->afterCommit === true && $trabajo->tries === 3);
    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $trabajo): bool => $trabajo->notification instanceof CorreoDeRestablecimiento
        && $trabajo->notification->token === 'token-de-prueba' && $trabajo->notification->afterCommit === true && $trabajo->tries === 3);
});

it('keeps the Spanish subject and link of both e-mails', function (): void {
    $usuario = User::factory()->unverified()->create();

    expect((new CorreoDeVerificacion)->toMail($usuario)->subject)->toBe('Verifica tu correo del CILN')
        ->and(new CorreoDeRestablecimiento('token-de-prueba')->toMail($usuario)->subject)->toBe('Restablece tu contraseña del CILN')
        ->and(new CorreoDeRestablecimiento('token-de-prueba')->toMail($usuario)->actionUrl)->toContain('/restablecer-clave/token-de-prueba');
});

it('encrypts the queued password reset, since it carries the plain token', function (): void {
    Queue::fake();
    $usuario = User::factory()->create();

    $usuario->sendPasswordResetNotification('token-de-prueba');

    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $trabajo): bool => $trabajo->shouldBeEncrypted === true);
});

it('signs the verification link with the configured application address, as the queue worker builds it', function (): void {
    config(['app.url' => 'https://cid.unasam.test']);
    URL::forceRootUrl('https://cid.unasam.test');
    $usuario = User::factory()->unverified()->create();

    $enlace = (string) (new CorreoDeVerificacion)->toMail($usuario)->actionUrl;

    expect(parse_url($enlace, PHP_URL_HOST))->toBe('cid.unasam.test')
        ->and(parse_url($enlace, PHP_URL_PATH))->toStartWith('/verificar-correo/')
        ->and(URL::hasValidSignature(Request::create($enlace)))->toBeTrue();
});
