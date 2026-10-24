<?php

declare(strict_types=1);

namespace App\Modules\Personas;

use App\Modules\Personas\Actions\BuscarPersonas;
use App\Modules\Personas\Actions\EtiquetarPersonas;
use App\Modules\Personas\Actions\ObtenerFichaDelUsuario;
use App\Modules\Personas\Actions\ObtenerFichaDePersona;
use App\Modules\Personas\Actions\ObtenerVinculo;
use App\Modules\Personas\Actions\SeleccionarIdsDePersonas;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Contracts\FichaDePersona;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use App\Modules\Personas\Http\Middleware\RequiereFichaDeEstudiante;
use App\Shared\Support\ModuleServiceProvider;
use Livewire\Livewire;

final class PersonasServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ConsultaDeVinculo::class => ObtenerVinculo::class,
        EtiquetasDePersonas::class => EtiquetarPersonas::class,
        BusquedaDePersonas::class => BuscarPersonas::class,
        SubconsultaDePersonas::class => SeleccionarIdsDePersonas::class,
        FichaDelUsuario::class => ObtenerFichaDelUsuario::class,
        FichaDePersona::class => ObtenerFichaDePersona::class,
    ];

    protected string $module = 'Personas';

    public function boot(): void
    {
        parent::boot();

        Livewire::addPersistentMiddleware([RequiereFichaDeEstudiante::class]);
    }
}
