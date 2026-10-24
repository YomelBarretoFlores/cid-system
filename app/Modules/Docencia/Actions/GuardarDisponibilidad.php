<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Models\Disponibilidad;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class GuardarDisponibilidad
{
    public function __invoke(int $docenteId, int $diaSemana, string $horaInicio, string $horaFin, ?int $disponibilidadId = null): Disponibilidad
    {
        $inicio = self::normalizarHora($horaInicio);
        $fin = self::normalizarHora($horaFin);
        $this->asegurarFranjaValida($diaSemana, $inicio, $fin);

        return DB::transaction(function () use ($docenteId, $diaSemana, $inicio, $fin, $disponibilidadId): Disponibilidad {
            $docente = Docente::query()->whereKey($docenteId)->lockForUpdate()->firstOrFail();
            $franja = $disponibilidadId === null
                ? new Disponibilidad(['docente_id' => $docente->id])
                : $docente->disponibilidades()->findOrFail($disponibilidadId);

            $cruce = $docente->disponibilidades()
                ->when($franja->exists, fn ($query) => $query->whereKeyNot($franja->getKey()))
                ->seCruzaCon($diaSemana, $inicio, $fin)
                ->first();

            if ($cruce !== null) {
                throw new ReglaDeNegocioIncumplida("La disponibilidad se cruza con otra franja del docente: {$cruce->descripcion}.");
            }

            $franja->fill(['dia_semana' => $diaSemana, 'hora_inicio' => $inicio, 'hora_fin' => $fin])->save();

            return $franja;
        });
    }

    public static function normalizarHora(string $hora): string
    {
        // Times are compared as strings, so every value must share the zero-padded HH:MM:SS shape.
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora) !== 1) {
            throw new ReglaDeNegocioIncumplida('Escribe la hora en formato HH:MM.');
        }

        return strlen($hora) === 5 ? "{$hora}:00" : $hora;
    }

    private function asegurarFranjaValida(int $diaSemana, string $inicio, string $fin): void
    {
        if (! array_key_exists($diaSemana, Disponibilidad::DIAS)) {
            throw new ReglaDeNegocioIncumplida('El día debe estar entre 1 (lunes) y 7 (domingo).');
        }

        if ($fin <= $inicio) {
            throw new ReglaDeNegocioIncumplida('La hora de fin debe ser posterior a la de inicio.');
        }
    }
}
