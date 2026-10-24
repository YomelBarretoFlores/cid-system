<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final class MovimientoDeMatricula
{
    private const int CANDADO_POR_PERSONA = 1;

    private const int MOTIVO_MAXIMO = 500;

    // Every enrollment change of one person takes this lock first: person, then enrollment or payment, then groups.
    public static function bloquearPersona(int $personaId): void
    {
        DB::select('select pg_advisory_xact_lock(?, ?)', [self::CANDADO_POR_PERSONA, $personaId]);
    }

    public static function bloquearMatriculaActiva(int $matriculaId): Matricula
    {
        self::bloquearPersona((int) Matricula::query()->whereKey($matriculaId)->value('persona_id'));

        $matricula = Matricula::query()->whereKey($matriculaId)->lockForUpdate()->firstOrFail();

        if ($matricula->estado !== EstadoMatricula::Activa) {
            throw new ReglaDeNegocioIncumplida('Solo se puede trasladar o retirar una matrícula activa.');
        }

        return $matricula;
    }

    public static function motivo(string $motivo): string
    {
        $motivo = trim($motivo);

        throw_if($motivo === '', ReglaDeNegocioIncumplida::class, 'Escribe el motivo del traslado o retiro.');
        throw_if(mb_strlen($motivo) > self::MOTIVO_MAXIMO, ReglaDeNegocioIncumplida::class, 'El motivo admite como máximo 500 caracteres.');

        return $motivo;
    }
}
