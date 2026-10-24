<?php

declare(strict_types=1);

namespace App\Modules\Docencia;

use App\Modules\Docencia\Actions\BuscarDocenteDelUsuario;
use App\Modules\Docencia\Actions\NombrarDocentesDeGrupos;
use App\Modules\Docencia\Actions\VerificarCargaDelGrupo;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Contracts\VerificacionDeCargaDocente;
use App\Modules\Docencia\Http\Middleware\RequierePerfilDocente;
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
    ];

    protected string $module = 'Docencia';

    public function boot(): void
    {
        parent::boot();

        Livewire::addPersistentMiddleware([RequierePerfilDocente::class]);
    }
}
