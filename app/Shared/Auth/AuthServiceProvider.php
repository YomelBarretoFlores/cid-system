<?php

declare(strict_types=1);

namespace App\Shared\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers());

        VerifyEmail::toMailUsing(fn (object $notifiable, string $url): MailMessage => (new MailMessage)
            ->subject('Verifica tu correo del CILN')
            ->greeting('¡Hola!')
            ->line('Confirma que este correo es tuyo para usar el portal del estudiante del CILN.')
            ->action('Verificar mi correo', $url)
            ->line('Si no creaste una cuenta en el CILN, ignora este mensaje.'));

        ResetPassword::toMailUsing(fn (CanResetPassword $notifiable, string $token): MailMessage => (new MailMessage)
            ->subject('Restablece tu contraseña del CILN')
            ->greeting('¡Hola!')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta del CILN.')
            ->action('Restablecer mi contraseña', route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]))
            ->line('El enlace vence en '.config('auth.passwords.users.expire').' minutos. Si no lo pediste, ignora este mensaje.'));
    }
}
