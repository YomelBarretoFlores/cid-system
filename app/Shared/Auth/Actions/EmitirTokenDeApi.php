<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Data\TokenDeApi;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\PerfilesDelUsuario;
use App\Shared\Support\Database\Transaccion;
use Carbon\CarbonImmutable;

final readonly class EmitirTokenDeApi
{
    public const int DIAS_DE_VIGENCIA = 30;

    public function __construct(
        private AutenticarUsuario $autenticarUsuario,
        private PerfilesDelUsuario $perfiles,
    ) {}

    public function __invoke(string $email, string $password, string $direccion, string $dispositivo): TokenDeApi
    {
        $usuario = ($this->autenticarUsuario)($email, $password, $direccion);
        // Read before the token exists, so a failure here never leaves a live token the client never received.
        $perfiles = $this->perfiles->de($usuario->id);
        $expiraEn = CarbonImmutable::now()->addDays(self::DIAS_DE_VIGENCIA);

        // One live token per device: signing in again from it replaces the previous token instead of piling up.
        $token = Transaccion::ejecutar(function () use ($usuario, $dispositivo, $expiraEn): string {
            // Locking the account serializes two sign-ins from the same device, so the second one sees and replaces the first token.
            User::query()->whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            $usuario->tokens()->where('name', $dispositivo)->delete();

            return $usuario->createToken($dispositivo, ['*'], $expiraEn)->plainTextToken;
        });

        return new TokenDeApi($token, $expiraEn, $perfiles);
    }
}
