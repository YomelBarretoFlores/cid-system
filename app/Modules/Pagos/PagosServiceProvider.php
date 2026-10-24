<?php

declare(strict_types=1);

namespace App\Modules\Pagos;

use App\Modules\Pagos\Actions\VerificarPagoParaServicio;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Shared\Support\ModuleServiceProvider;

final class PagosServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        VerificacionDePago::class => VerificarPagoParaServicio::class,
    ];

    protected string $module = 'Pagos';
}
