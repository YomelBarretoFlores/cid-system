<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Contracts;

use App\Modules\Matricula\Enums\OrigenHabilitacion;

interface HabilitacionPorTramite
{
    /**
     * Enables the cycle for the person in its language, under the same person lock as enrollment.
     *
     * @param  bool  $soloAvanzar  true never lowers a cycle the person already reached (proficiency); false sets exactly that cycle (reclassification)
     * @return bool whether the enabled cycle changed
     */
    public function __invoke(int $personaId, int $cicloId, OrigenHabilitacion $origen, int $usuarioId, bool $soloAvanzar): bool;
}
