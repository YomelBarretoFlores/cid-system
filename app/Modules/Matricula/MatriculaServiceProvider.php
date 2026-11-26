<?php

declare(strict_types=1);

namespace App\Modules\Matricula;

use App\Modules\Certificacion\Events\ActaCerrada;
use App\Modules\Matricula\Actions\BuscarDocumentoDeMatricula;
use App\Modules\Matricula\Actions\DescribirContextoAcademicoDePagos;
use App\Modules\Matricula\Actions\ListarEstudiantesDelGrupo;
use App\Modules\Matricula\Actions\ListarMatriculasActivasDePersona;
use App\Modules\Matricula\Actions\ListarProgramasDeLaPersona;
use App\Modules\Matricula\Actions\MoverMatriculaPorTramite;
use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Matricula\Contracts\DocumentoDeMatriculaPorId;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Matricula\Contracts\MovimientoPorTramite;
use App\Modules\Matricula\Contracts\ProgramasDeLaPersona;
use App\Modules\Matricula\Listeners\AlAnularPago;
use App\Modules\Matricula\Listeners\AlCerrarActa;
use App\Modules\Matricula\Livewire\ResumenDelEstudiante;
use App\Modules\Pagos\Events\PagoAnulado;
use App\Shared\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

final class MatriculaServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ContextoAcademicoDePagos::class => DescribirContextoAcademicoDePagos::class,
        EstudiantesDelGrupo::class => ListarEstudiantesDelGrupo::class,
        MatriculasActivasDePersona::class => ListarMatriculasActivasDePersona::class,
        DocumentoDeMatriculaPorId::class => BuscarDocumentoDeMatricula::class,
        MovimientoPorTramite::class => MoverMatriculaPorTramite::class,
        ProgramasDeLaPersona::class => ListarProgramasDeLaPersona::class,
    ];

    #[\Override]
    protected string $module = 'Matricula';

    public function boot(): void
    {
        parent::boot();

        Livewire::component('matricula.resumen-del-estudiante', ResumenDelEstudiante::class);
        Event::listen(ActaCerrada::class, AlCerrarActa::class);
        Event::listen(PagoAnulado::class, AlAnularPago::class);
    }
}
