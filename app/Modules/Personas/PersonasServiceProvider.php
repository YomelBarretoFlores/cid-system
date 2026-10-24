<?php

declare(strict_types=1);

namespace App\Modules\Personas;

use App\Modules\Personas\Actions\BuscarPersonas;
use App\Modules\Personas\Actions\EtiquetarPersonas;
use App\Modules\Personas\Actions\ObtenerVinculo;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Shared\Support\ModuleServiceProvider;

final class PersonasServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ConsultaDeVinculo::class => ObtenerVinculo::class,
        EtiquetasDePersonas::class => EtiquetarPersonas::class,
        BusquedaDePersonas::class => BuscarPersonas::class,
    ];

    protected string $module = 'Personas';
}
