<?php

declare(strict_types=1);

namespace App\Shared\Auth\Enums;

use Illuminate\Http\Request;

enum EntradaDelPortal: string
{
    case Estudiante = 'estudiante';
    case Docente = 'docente';

    public static function desde(?string $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::Estudiante;
    }

    public static function delPedido(Request $request): self
    {
        return $request->routeIs('docente.*') || $request->is('docente', 'docente/*') ? self::Docente : self::Estudiante;
    }

    public function titulo(): string
    {
        return match ($this) {
            self::Estudiante => 'Ingresar al portal del estudiante',
            self::Docente => 'Ingresar al portal docente',
        };
    }

    public function ruta(): string
    {
        return "{$this->value}.ingresar";
    }
}
