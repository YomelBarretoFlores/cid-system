<?php

declare(strict_types=1);

namespace App\Modules\Matricula;

use App\Modules\Matricula\Actions\BuscarDocumentoDeMatricula;
use App\Modules\Matricula\Actions\DescribirContextoAcademicoDePagos;
use App\Modules\Matricula\Actions\ListarEstudiantesDelGrupo;
use App\Modules\Matricula\Actions\ListarMatriculasActivasDePersona;
use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Matricula\Contracts\DocumentoDeMatriculaPorId;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Matricula\Livewire\ResumenDelEstudiante;
use App\Shared\Support\ModuleServiceProvider;
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
    ];

    protected string $module = 'Matricula';

    public function boot(): void
    {
        parent::boot();

        Livewire::component('matricula.resumen-del-estudiante', ResumenDelEstudiante::class);
    }
}
