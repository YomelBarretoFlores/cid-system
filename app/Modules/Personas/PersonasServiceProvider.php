<?php

declare(strict_types=1);

namespace App\Modules\Personas;

use App\Modules\Personas\Actions\ObtenerVinculo;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Shared\Support\ModuleServiceProvider;

final class PersonasServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ConsultaDeVinculo::class => ObtenerVinculo::class,
    ];

    protected string $module = 'Personas';
}
