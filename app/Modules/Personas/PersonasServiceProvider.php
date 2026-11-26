<?php

declare(strict_types=1);

namespace App\Modules\Personas;

use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Personas\Actions\BuscarPersonas;
use App\Modules\Personas\Actions\EtiquetarPersonas;
use App\Modules\Personas\Actions\ListarCatalogoDeDocumentos;
use App\Modules\Personas\Actions\ListarCatalogoDeVinculos;
use App\Modules\Personas\Actions\ObtenerFichaDelUsuario;
use App\Modules\Personas\Actions\ObtenerFichaDePersona;
use App\Modules\Personas\Actions\ObtenerFichasDePersonas;
use App\Modules\Personas\Actions\ObtenerFotoDePersona;
use App\Modules\Personas\Actions\ObtenerVinculo;
use App\Modules\Personas\Actions\SeleccionarIdsDePersonas;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\CatalogoDeDocumentos;
use App\Modules\Personas\Contracts\CatalogoDeVinculos;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Contracts\FichaDePersona;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Personas\Contracts\FotoDePersona;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use App\Modules\Personas\Http\Middleware\RequiereCuentaHabilitada;
use App\Modules\Personas\Http\Middleware\RequiereFichaDeEstudiante;
use App\Modules\Personas\Listeners\AlValidarPago;
use App\Shared\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

final class PersonasServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ConsultaDeVinculo::class => ObtenerVinculo::class,
        CatalogoDeVinculos::class => ListarCatalogoDeVinculos::class,
        CatalogoDeDocumentos::class => ListarCatalogoDeDocumentos::class,
        EtiquetasDePersonas::class => EtiquetarPersonas::class,
        BusquedaDePersonas::class => BuscarPersonas::class,
        SubconsultaDePersonas::class => SeleccionarIdsDePersonas::class,
        FichaDelUsuario::class => ObtenerFichaDelUsuario::class,
        FichaDePersona::class => ObtenerFichaDePersona::class,
        FichasDePersonas::class => ObtenerFichasDePersonas::class,
        FotoDePersona::class => ObtenerFotoDePersona::class,
    ];

    #[\Override]
    protected string $module = 'Personas';

    public function boot(): void
    {
        parent::boot();

        Livewire::addPersistentMiddleware([RequiereFichaDeEstudiante::class, RequiereCuentaHabilitada::class]);
        Event::listen(PagoValidado::class, AlValidarPago::class);
    }
}
