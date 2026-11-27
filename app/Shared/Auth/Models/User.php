<?php

declare(strict_types=1);

namespace App\Shared\Auth\Models;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Shared\Auth\Enums\TemaDeInterfaz;
use App\Shared\Auth\Notifications\CorreoDeRestablecimiento;
use App\Shared\Auth\Policies\UsuarioPolicy;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string|null $email
 * @property string|null $usuario
 * @property Carbon|null $desactivado_en
 * @property TemaDeInterfaz $tema
 */
#[Fillable(['name', 'email', 'usuario', 'password', 'tema'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
#[UsePolicy(UsuarioPolicy::class)]
final class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    public const string ADMIN_PANEL_PERMISSION = 'admin-panel.access';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable;

    // A freshly created account has no second factor yet; strict mode would refuse the unloaded columns.
    #[\Override]
    protected $attributes = [
        'app_authentication_secret' => null,
        'app_authentication_recovery_codes' => null,
        'desactivado_en' => null,
        'tema' => 'sistema',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->desactivado_en !== null) {
            return false;
        }

        return match ($panel->getId()) {
            'docente' => app(DocenteDelUsuario::class)($this->id) !== null,
            default => $this->can(self::ADMIN_PANEL_PERMISSION),
        };
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CorreoDeRestablecimiento($token));
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => self::normalizarCorreo($value));
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function usuario(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => self::normalizarUsuario($value));
    }

    public static function normalizarCorreo(?string $correo): ?string
    {
        $correo = mb_strtolower(trim((string) $correo));

        return $correo === '' ? null : $correo;
    }

    public static function normalizarUsuario(?string $usuario): ?string
    {
        $usuario = mb_strtoupper(trim((string) $usuario));

        return $usuario === '' ? null : $usuario;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'desactivado_en' => 'datetime',
            'password' => 'hashed',
            'tema' => TemaDeInterfaz::class,
        ];
    }
}
