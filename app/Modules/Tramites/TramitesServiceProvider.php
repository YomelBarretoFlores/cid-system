<?php

declare(strict_types=1);

namespace App\Modules\Tramites;

use App\Modules\Tramites\Actions\ContarTramitesObservados;
use App\Modules\Tramites\Contracts\TramitesObservados;
use App\Shared\Support\ModuleServiceProvider;

final class TramitesServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TramitesObservados::class => ContarTramitesObservados::class,
    ];

    protected string $module = 'Tramites';
}
