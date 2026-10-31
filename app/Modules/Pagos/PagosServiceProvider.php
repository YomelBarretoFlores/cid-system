<?php

declare(strict_types=1);

namespace App\Modules\Pagos;

use App\Modules\Pagos\Actions\DescribirPago;
use App\Modules\Pagos\Actions\ListarPagosValidadosDePersona;
use App\Modules\Pagos\Actions\ListarTarifasVigentes;
use App\Modules\Pagos\Actions\ResumirPagosDePersona;
use App\Modules\Pagos\Actions\VerificarPagoParaServicio;
use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Contracts\ResumenDePagosDePersona;
use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Shared\Support\ModuleServiceProvider;

final class PagosServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        VerificacionDePago::class => VerificarPagoParaServicio::class,
        PagosValidadosDePersona::class => ListarPagosValidadosDePersona::class,
        ConsultaDePago::class => DescribirPago::class,
        TarifasVigentes::class => ListarTarifasVigentes::class,
        ResumenDePagosDePersona::class => ResumirPagosDePersona::class,
    ];

    protected string $module = 'Pagos';
}
