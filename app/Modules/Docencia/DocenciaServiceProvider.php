<?php

declare(strict_types=1);

namespace App\Modules\Docencia;

use App\Modules\Docencia\Actions\BuscarDocenteDelGrupo;
use App\Modules\Docencia\Actions\BuscarDocenteDelUsuario;
use App\Modules\Docencia\Actions\ComprobarClasesDictadas;
use App\Modules\Docencia\Actions\ContarClasesPorConfirmarDelDelegado;
use App\Modules\Docencia\Actions\LeerNotasParcialesDelGrupo;
use App\Modules\Docencia\Actions\NombrarDocentesDeGrupos;
use App\Modules\Docencia\Actions\ResumirAsistenciaDeMatricula;
use App\Modules\Docencia\Actions\VerificarCargaDelGrupo;
use App\Modules\Docencia\Contracts\AsistenciaDeMatricula;
use App\Modules\Docencia\Contracts\ClasesDictadasDelGrupo;
use App\Modules\Docencia\Contracts\ClasesPorConfirmarDelDelegado;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Contracts\NotasParcialesDelGrupo;
use App\Modules\Docencia\Contracts\VerificacionDeCargaDocente;
use App\Modules\Docencia\Filament\Personas\AtajoDeDocente;
use App\Shared\Support\Filament\FichaDePersonaEnElPanel;
use App\Shared\Support\ModuleServiceProvider;

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
        ClasesPorConfirmarDelDelegado::class => ContarClasesPorConfirmarDelDelegado::class,
        ClasesDictadasDelGrupo::class => ComprobarClasesDictadas::class,
    ];

    #[\Override]
    protected string $module = 'Docencia';

    public function boot(): void
    {
        parent::boot();

        app(FichaDePersonaEnElPanel::class)->atajo('docente', AtajoDeDocente::para(...), 30);
    }
}
