<?php

declare(strict_types=1);

namespace App\Shared\Auth;

use App\Shared\Auth\Filament\SalidaDelPanel;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\RolPolicy;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LogoutResponse::class, SalidaDelPanel::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        Gate::policy(Role::class, RolPolicy::class);

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)->by((string) ($request->user('sanctum')?->getAuthIdentifier() ?? $request->ip())));
        Gate::define('viewApiDocs', fn (?User $usuario): bool => $usuario?->can(User::ADMIN_PANEL_PERMISSION) ?? false);

        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers());
        // Livewire re-runs only persistent middleware on component updates, so the email gate must be listed here too.
        Livewire::addPersistentMiddleware([EnsureEmailIsVerified::class]);

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
