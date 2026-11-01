<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Models\SemanaDeSilabo;
use App\Modules\Docencia\Models\Silabo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

final class TemaDeLaClase
{
    private const int LIMITE = 1000;

    public static function normalizar(?string $tema): ?string
    {
        $limpio = trim((string) preg_replace('/\s+/u', ' ', (string) $tema));

        if (mb_strlen($limpio) > self::LIMITE) {
            throw new ReglaDeNegocioIncumplida('El tema de la clase no puede pasar de '.self::LIMITE.' caracteres.');
        }

        return $limpio === '' ? null : $limpio;
    }

    public static function exigir(?string $tema): string
    {
        return $tema ?? throw new ReglaDeNegocioIncumplida('Escribe el tema que dictaste en esta clase.');
    }

    // The week of the group's published syllabus that covers the class date.
    public static function semanaDelSilabo(HorarioDeGrupo $grupo, CarbonImmutable $dia): ?SemanaDeSilabo
    {
        $silabo = Silabo::query()->where('grupo_id', $grupo->grupoId)->where('estado', EstadoSilabo::Publicado)->first();
        $semana = collect(SemanasDelPeriodo::rangos($grupo->periodoInicio, $grupo->periodoFin))
            ->search(fn (array $rango): bool => $dia->startOfDay()->betweenIncluded($rango[0], $rango[1]));

        return $silabo === null || $semana === false ? null : $silabo->semanas()->where('semana', $semana)->first();
    }

    public static function sugerido(?SemanaDeSilabo $semana): ?string
    {
        return self::normalizar(implode(': ', array_filter([$semana?->unidad, $semana?->saber], fn (?string $texto): bool => trim((string) $texto) !== '')));
    }
}
