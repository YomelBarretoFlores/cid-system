<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\Tramite;
use Illuminate\Support\Facades\DB;

// Every change of state or stage leaves one history row and one notification, both after the change is committed.
final class RegistroDeMovimiento
{
    private const int CANDADO_POR_PERSONA = 6;

    public static function bloquearPersona(int $personaId): void
    {
        DB::select('select pg_advisory_xact_lock(?, ?)', [self::CANDADO_POR_PERSONA, $personaId]);
    }

    public static function registrar(Tramite $tramite, ?int $actorId = null, ?string $comentario = null): Movimiento
    {
        $movimiento = Movimiento::query()->create([
            'tramite_id' => $tramite->id,
            'estado' => $tramite->estado,
            'etapa' => $tramite->etapa_actual,
            'etapa_nombre' => $tramite->nombreDeEtapa(),
            'comentario' => $comentario,
            'actor_id' => $actorId,
        ]);

        TramiteActualizado::dispatch($tramite->id, $movimiento->id);

        return $movimiento;
    }
}
