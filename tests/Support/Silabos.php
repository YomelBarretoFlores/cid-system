<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Actions\GuardarSilabo;
use App\Modules\Docencia\Actions\PublicarSilabo;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;

// Syllabi of the ActasFinales course (five weeks, components parcial, final and participacion), written through the teacher Actions.
final class Silabos
{
    public const array CODIGOS = ['parcial', 'final', 'participacion'];

    public static function publicar(Grupo $grupo, Docente $docente, User $usuario, string $prefijo = ''): void
    {
        app(PublicarSilabo::class)($grupo->id, $docente->id, PortalDocente::silaboCompleto(5, self::CODIGOS, $prefijo), $usuario);
    }

    public static function borrador(Grupo $grupo, Docente $docente, string $prefijo = ''): void
    {
        app(GuardarSilabo::class)($grupo->id, $docente->id, PortalDocente::silaboCompleto(5, self::CODIGOS, $prefijo));
    }
}
