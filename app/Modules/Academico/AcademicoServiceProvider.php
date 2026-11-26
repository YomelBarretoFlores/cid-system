<?php

declare(strict_types=1);

namespace App\Modules\Academico;

use App\Modules\Academico\Actions\BloquearGrupo;
use App\Modules\Academico\Actions\BuscarEsquemaDelGrupo;
use App\Modules\Academico\Actions\BuscarPeriodoEnCurso;
use App\Modules\Academico\Actions\BuscarProgramaPorDefecto;
use App\Modules\Academico\Actions\DescribirCursoDelGrupo;
use App\Modules\Academico\Actions\DescribirGrupos;
use App\Modules\Academico\Actions\DescribirHorariosDeGrupos;
use App\Modules\Academico\Actions\LiberarVacante;
use App\Modules\Academico\Actions\ListarCatalogoDeGrupos;
use App\Modules\Academico\Actions\ListarCatalogoDeIdiomas;
use App\Modules\Academico\Actions\ListarCatalogoDePeriodos;
use App\Modules\Academico\Actions\ListarCatalogoDeProgramas;
use App\Modules\Academico\Actions\ListarCiclosAnteriores;
use App\Modules\Academico\Actions\ListarCiclosDeGrupos;
use App\Modules\Academico\Actions\ListarCiclosDelNivel;
use App\Modules\Academico\Actions\ListarCursosDelPeriodo;
use App\Modules\Academico\Actions\ListarGruposDelCiclo;
use App\Modules\Academico\Actions\ListarGruposParaTraslado;
use App\Modules\Academico\Actions\ListarIniciosDePeriodos;
use App\Modules\Academico\Actions\ListarOfertaDeMatricula;
use App\Modules\Academico\Actions\ListarOfertaPublica;
use App\Modules\Academico\Actions\ListarProgramasDeGrupos;
use App\Modules\Academico\Actions\ListarVentanasDeMatricula;
use App\Modules\Academico\Actions\MoverVacante;
use App\Modules\Academico\Actions\ReservarVacante;
use App\Modules\Academico\Actions\VerificarMatriculaExtemporanea;
use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Contracts\CiclosAnteriores;
use App\Modules\Academico\Contracts\CiclosDeGrupos;
use App\Modules\Academico\Contracts\CiclosDelNivel;
use App\Modules\Academico\Contracts\ConsultaGruposDelCiclo;
use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Contracts\CursosDelPeriodo;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Contracts\GruposParaTraslado;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Contracts\IniciosDePeriodos;
use App\Modules\Academico\Contracts\LiberacionDeVacante;
use App\Modules\Academico\Contracts\MatriculaExtemporanea;
use App\Modules\Academico\Contracts\OfertaDeMatricula;
use App\Modules\Academico\Contracts\OfertaPublica;
use App\Modules\Academico\Contracts\PeriodoEnCurso;
use App\Modules\Academico\Contracts\ProgramaPorDefecto;
use App\Modules\Academico\Contracts\ProgramasDeGrupos;
use App\Modules\Academico\Contracts\ReservaDeVacante;
use App\Modules\Academico\Contracts\TrasladoDeVacante;
use App\Modules\Academico\Contracts\VentanasDeMatricula;
use App\Modules\Academico\Support\BanderasRegistradas;
use App\Shared\Support\ModuleServiceProvider;
use App\Shared\Ui\BanderasDeIdiomas;

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
        EsquemaDelGrupo::class => BuscarEsquemaDelGrupo::class,
        CursoDelGrupo::class => DescribirCursoDelGrupo::class,
        OfertaPublica::class => ListarOfertaPublica::class,
        CiclosDeGrupos::class => ListarCiclosDeGrupos::class,
        VentanasDeMatricula::class => ListarVentanasDeMatricula::class,
        CiclosDelNivel::class => ListarCiclosDelNivel::class,
        CursosDelPeriodo::class => ListarCursosDelPeriodo::class,
        PeriodoEnCurso::class => BuscarPeriodoEnCurso::class,
        CiclosAnteriores::class => ListarCiclosAnteriores::class,
        IniciosDePeriodos::class => ListarIniciosDePeriodos::class,
        ProgramaPorDefecto::class => BuscarProgramaPorDefecto::class,
        CatalogoDeProgramas::class => ListarCatalogoDeProgramas::class,
        ProgramasDeGrupos::class => ListarProgramasDeGrupos::class,
        MatriculaExtemporanea::class => VerificarMatriculaExtemporanea::class,
    ];

    #[\Override]
    protected string $module = 'Academico';

    public function register(): void
    {
        parent::register();

        $this->app->scoped(BanderasDeIdiomas::class, BanderasRegistradas::class);
    }
}
