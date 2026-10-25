<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Data\TokenDeApi;
use App\Shared\Auth\Support\PerfilesDelUsuario;
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

        return new TokenDeApi($usuario->createToken($dispositivo, ['*'], $expiraEn)->plainTextToken, $expiraEn, $perfiles);
    }
}
