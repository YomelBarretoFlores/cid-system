<?php

declare(strict_types=1);

namespace App\Modules\Tramites;

use App\Modules\Pagos\Events\PagoAnulado;
use App\Modules\Pagos\Events\PagoRechazado;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Tramites\Actions\ContarTramitesEnProceso;
use App\Modules\Tramites\Actions\ContarTramitesObservados;
use App\Modules\Tramites\Contracts\TramitesEnProceso;
use App\Modules\Tramites\Contracts\TramitesObservados;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Listeners\AlAnularPago;
use App\Modules\Tramites\Listeners\AlRechazarPago;
use App\Modules\Tramites\Listeners\AlValidarPago;
use App\Modules\Tramites\Listeners\NotificarCambioDeTramite;
use App\Shared\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;

final class TramitesServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TramitesObservados::class => ContarTramitesObservados::class,
        TramitesEnProceso::class => ContarTramitesEnProceso::class,
    ];

    protected string $module = 'Tramites';

    public function boot(): void
    {
        parent::boot();

        Event::listen(PagoValidado::class, AlValidarPago::class);
        Event::listen(PagoRechazado::class, AlRechazarPago::class);
        Event::listen(PagoAnulado::class, AlAnularPago::class);
        Event::listen(TramiteActualizado::class, NotificarCambioDeTramite::class);
    }
}
