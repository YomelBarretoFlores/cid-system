<?php

declare(strict_types=1);

namespace App\Shared\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

// Lets the owner notice a change they did not make, even when someone else knew the old password.
#[Backoff([30, 120])]
#[Tries(3)]
final class AvisoDeCambioDeContrasena extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu contraseña del CILN cambió')
            ->greeting('¡Hola!')
            ->line('La contraseña de tu cuenta del CILN acaba de cambiar y cerramos las sesiones abiertas en tus otros dispositivos.')
            ->line('Si no fuiste tú, restablece tu contraseña ahora y avisa a la oficina del CILN.')
            ->action('Restablecer mi contraseña', route('password.request'));
    }
}
