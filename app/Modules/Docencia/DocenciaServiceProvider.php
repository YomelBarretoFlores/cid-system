<?php

declare(strict_types=1);

namespace App\Modules\Docencia;

use App\Modules\Docencia\Actions\BuscarDocenteDelGrupo;
use App\Modules\Docencia\Actions\BuscarDocenteDelUsuario;
use App\Modules\Docencia\Actions\ConsultarEstadosDeSilabos;
use App\Modules\Docencia\Actions\GenerarSilaboPdf;
use App\Modules\Docencia\Actions\LeerNotasParcialesDelGrupo;
use App\Modules\Docencia\Actions\LeerSilaboDelGrupo;
use App\Modules\Docencia\Actions\NombrarDocentesDeGrupos;
use App\Modules\Docencia\Actions\PrepararLecturaDelSilabo;
use App\Modules\Docencia\Actions\ResumirAsistenciaDeMatricula;
use App\Modules\Docencia\Actions\VerificarCargaDelGrupo;
use App\Modules\Docencia\Contracts\AsistenciaDeMatricula;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Contracts\EstadosDeSilabos;
use App\Modules\Docencia\Contracts\LecturaDelSilabo;
use App\Modules\Docencia\Contracts\NotasParcialesDelGrupo;
use App\Modules\Docencia\Contracts\SilaboDelGrupo;
use App\Modules\Docencia\Contracts\SilaboEnPdf;
use App\Modules\Docencia\Contracts\VerificacionDeCargaDocente;
use App\Modules\Docencia\Livewire\ClasesPorConfirmarResumen;
use App\Shared\Support\ModuleServiceProvider;
use Livewire\Livewire;

final class DocenciaServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        VerificacionDeCargaDocente::class => VerificarCargaDelGrupo::class,
        DocentesDeGrupos::class => NombrarDocentesDeGrupos::class,
        DocenteDelUsuario::class => BuscarDocenteDelUsuario::class,
        DocenteDelGrupo::class => BuscarDocenteDelGrupo::class,
        NotasParcialesDelGrupo::class => LeerNotasParcialesDelGrupo::class,
        AsistenciaDeMatricula::class => ResumirAsistenciaDeMatricula::class,
        SilaboDelGrupo::class => LeerSilaboDelGrupo::class,
        EstadosDeSilabos::class => ConsultarEstadosDeSilabos::class,
        LecturaDelSilabo::class => PrepararLecturaDelSilabo::class,
        SilaboEnPdf::class => GenerarSilaboPdf::class,
    ];

    protected string $module = 'Docencia';

    public function boot(): void
    {
        parent::boot();

        Livewire::component('docencia.clases-por-confirmar-resumen', ClasesPorConfirmarResumen::class);
    }
}
