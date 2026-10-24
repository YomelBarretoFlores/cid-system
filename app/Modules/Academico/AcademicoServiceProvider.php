<?php

declare(strict_types=1);

namespace App\Modules\Academico;

use App\Modules\Academico\Actions\BloquearGrupo;
use App\Modules\Academico\Actions\DescribirGrupos;
use App\Modules\Academico\Actions\DescribirHorariosDeGrupos;
use App\Modules\Academico\Actions\LiberarVacante;
use App\Modules\Academico\Actions\ListarCatalogoDeGrupos;
use App\Modules\Academico\Actions\ListarCatalogoDeIdiomas;
use App\Modules\Academico\Actions\ListarCatalogoDePeriodos;
use App\Modules\Academico\Actions\ListarGruposDelCiclo;
use App\Modules\Academico\Actions\ListarGruposParaTraslado;
use App\Modules\Academico\Actions\ListarOfertaDeMatricula;
use App\Modules\Academico\Actions\MoverVacante;
use App\Modules\Academico\Actions\ReservarVacante;
use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Contracts\ConsultaGruposDelCiclo;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\GruposParaTraslado;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Contracts\LiberacionDeVacante;
use App\Modules\Academico\Contracts\OfertaDeMatricula;
use App\Modules\Academico\Contracts\ReservaDeVacante;
use App\Modules\Academico\Contracts\TrasladoDeVacante;
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
        TrasladoDeVacante::class => MoverVacante::class,
        LiberacionDeVacante::class => LiberarVacante::class,
        CatalogoDeGrupos::class => ListarCatalogoDeGrupos::class,
        CatalogoDeIdiomas::class => ListarCatalogoDeIdiomas::class,
        CatalogoDePeriodos::class => ListarCatalogoDePeriodos::class,
        GruposParaTraslado::class => ListarGruposParaTraslado::class,
        HorariosDeGrupos::class => DescribirHorariosDeGrupos::class,
        BloqueoDeGrupo::class => BloquearGrupo::class,
    ];

    protected string $module = 'Academico';
}
