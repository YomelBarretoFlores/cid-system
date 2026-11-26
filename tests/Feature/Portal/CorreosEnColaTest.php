<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Notifications\CorreoDeRestablecimiento;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

it('sends the password reset through the queue, after commit and with retries', function (): void {
    Queue::fake();
    $usuario = User::factory()->create();

    $usuario->sendPasswordResetNotification('token-de-prueba');

    Queue::assertPushed(SendQueuedNotifications::class, 1);
    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $trabajo): bool => $trabajo->notification instanceof CorreoDeRestablecimiento
        && $trabajo->notification->token === 'token-de-prueba' && $trabajo->notification->afterCommit === true && $trabajo->tries === 3);
});

it('keeps the Spanish subject and link of the password reset e-mail', function (): void {
    $usuario = User::factory()->create();

    expect(new CorreoDeRestablecimiento('token-de-prueba')->toMail($usuario)->subject)->toBe('Restablece tu contraseña del CILN')
        ->and(new CorreoDeRestablecimiento('token-de-prueba')->toMail($usuario)->actionUrl)->toContain('/restablecer-clave/token-de-prueba');
});

it('encrypts the queued password reset, since it carries the plain token', function (): void {
    Queue::fake();
    $usuario = User::factory()->create();

    $usuario->sendPasswordResetNotification('token-de-prueba');

    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $trabajo): bool => $trabajo->shouldBeEncrypted === true);
});

it('builds the reset link with the configured application address, as the queue worker builds it', function (): void {
    config(['app.url' => 'https://cid.unasam.test']);
    URL::forceRootUrl('https://cid.unasam.test');

    $enlace = (string) new CorreoDeRestablecimiento('token-de-prueba')->toMail(User::factory()->create())->actionUrl;

    expect(parse_url($enlace, PHP_URL_HOST))->toBe('cid.unasam.test')
        ->and(parse_url($enlace, PHP_URL_PATH))->toBe('/restablecer-clave/token-de-prueba');
});

it('writes the framework parts of the e-mails, closing, link help and footer, in Spanish', function (): void {
    $usuario = User::factory()->create();

    foreach ([new CorreoDeRestablecimiento('token-de-prueba')->toMail($usuario)] as $correo) {
        $html = (string) $correo->render();

        expect($html)->toContain('Atentamente,')
            ->toContain('Si el botón «'.$correo->actionText.'» no funciona, copia y pega en tu navegador la siguiente dirección:')
            ->toContain('Todos los derechos reservados.')
            ->not->toContain('Regards')
            ->not->toContain('having trouble')
            ->not->toContain('All rights reserved');
    }
});
