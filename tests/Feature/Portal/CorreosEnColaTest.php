<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Notifications\CorreoDeRestablecimiento;
use App\Shared\Auth\Notifications\CorreoDeVerificacion;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;

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
        ->and((new CorreoDeRestablecimiento('token-de-prueba'))->toMail($usuario)->subject)->toBe('Restablece tu contraseña del CILN')
        ->and((new CorreoDeRestablecimiento('token-de-prueba'))->toMail($usuario)->actionUrl)->toContain('/restablecer-clave/token-de-prueba');
});
