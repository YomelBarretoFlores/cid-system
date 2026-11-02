<?php

declare(strict_types=1);

namespace App\Shared\Auth\Data;

// The email is only read when the account is created; it never changes afterwards.
final readonly class DatosDeUsuarioDelPersonal
{
    /**
     * @param  list<int>  $roles
     * @param  list<string>  $permisos
     */
    public function __construct(
        public string $nombre,
        public ?string $correo,
        public array $roles,
        public array $permisos,
    ) {}
}
