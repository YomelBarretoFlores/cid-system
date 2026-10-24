<?php

declare(strict_types=1);

namespace App\Modules\Academico;

use App\Modules\Academico\Actions\ListarGruposDelCiclo;
use App\Modules\Academico\Actions\ReservarVacante;
use App\Modules\Academico\Contracts\ConsultaGruposDelCiclo;
use App\Modules\Academico\Contracts\ReservaDeVacante;
use App\Shared\Support\ModuleServiceProvider;

final class AcademicoServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ReservaDeVacante::class => ReservarVacante::class,
        ConsultaGruposDelCiclo::class => ListarGruposDelCiclo::class,
    ];

    protected string $module = 'Academico';
}
