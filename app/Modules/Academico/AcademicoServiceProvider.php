<?php

declare(strict_types=1);

namespace App\Modules\Academico;

use App\Modules\Academico\Actions\DescribirGrupos;
use App\Modules\Academico\Actions\ListarGruposDelCiclo;
use App\Modules\Academico\Actions\ListarOfertaDeMatricula;
use App\Modules\Academico\Actions\ReservarVacante;
use App\Modules\Academico\Contracts\ConsultaGruposDelCiclo;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\OfertaDeMatricula;
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
        OfertaDeMatricula::class => ListarOfertaDeMatricula::class,
        DetalleDeGrupos::class => DescribirGrupos::class,
    ];

    protected string $module = 'Academico';
}
