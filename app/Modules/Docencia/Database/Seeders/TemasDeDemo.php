<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Support\SemanasDelPeriodo;
use Carbon\CarbonImmutable;

// The topics a demo teacher writes for the classes of a first basic cycle; the last week of the period is always the review.
final class TemasDeDemo
{
    private const array SEMANAS = [
        'Unidad I: Saludos y presentaciones: Saludos formales e informales. El alfabeto. Pronombres personales.',
        'Unidad II: Números y datos personales: Números del 0 al 100. La hora. Días de la semana y meses del año.',
        'Unidad III: La familia: Vocabulario de la familia. Adjetivos calificativos. Posesivos.',
        'Unidad IV: La rutina diaria: Presente simple. Verbos de rutina. Adverbios de frecuencia.',
    ];

    private const string REPASO = 'Integración: Contenidos de las unidades I a IV.';

    public static function temaDeLaClase(HorarioDeGrupo $horario, CarbonImmutable $fecha): string
    {
        $rangos = SemanasDelPeriodo::rangos($horario->periodoInicio, $horario->periodoFin);
        $semana = (int) collect($rangos)->search(fn (array $rango): bool => $fecha->startOfDay()->betweenIncluded($rango[0], $rango[1]));

        return $semana === count($rangos) ? self::REPASO : self::SEMANAS[max(0, $semana - 1) % count(self::SEMANAS)];
    }
}
