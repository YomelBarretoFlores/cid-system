<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use Carbon\CarbonImmutable;
use Laravel\Sanctum\NewAccessToken;

final readonly class EmitirTokenDeApi
{
    public const int DIAS_DE_VIGENCIA = 30;

    public function __construct(private AutenticarUsuario $autenticarUsuario) {}

    public function __invoke(string $email, string $password, string $direccion, string $dispositivo): NewAccessToken
    {
        $usuario = ($this->autenticarUsuario)($email, $password, $direccion);

        return $usuario->createToken($dispositivo, ['*'], CarbonImmutable::now()->addDays(self::DIAS_DE_VIGENCIA));
    }
}
