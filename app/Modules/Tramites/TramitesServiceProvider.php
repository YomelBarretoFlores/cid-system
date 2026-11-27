<?php

declare(strict_types=1);

namespace App\Modules\Tramites;

use App\Modules\Pagos\Events\PagoAnulado;
use App\Modules\Pagos\Events\PagoRechazado;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Tramites\Actions\ContarTramitesEnProceso;
use App\Modules\Tramites\Actions\ContarTramitesObservados;
use App\Modules\Tramites\Actions\DescribirTramiteDelPago;
use App\Modules\Tramites\Actions\ListarTitulosDeGrados;
use App\Modules\Tramites\Contracts\TitulosDeGrados;
use App\Modules\Tramites\Contracts\TramiteDelPago;
use App\Modules\Tramites\Contracts\TramitesEnProceso;
use App\Modules\Tramites\Contracts\TramitesObservados;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Listeners\AlAnularPago;
use App\Modules\Tramites\Listeners\AlRechazarPago;
use App\Modules\Tramites\Listeners\AlValidarPago;
use App\Modules\Tramites\Listeners\NotificarCambioDeTramite;
use App\Modules\Tramites\Support\MarcoDelSitio;
use App\Shared\Support\ModuleServiceProvider;
use Illuminate\Contracts\View\View as Vista;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;

final class TramitesServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TramitesObservados::class => ContarTramitesObservados::class,
        TramitesEnProceso::class => ContarTramitesEnProceso::class,
        TramiteDelPago::class => DescribirTramiteDelPago::class,
        TitulosDeGrados::class => ListarTitulosDeGrados::class,
    ];

    #[\Override]
    protected string $module = 'Tramites';

    #[\Override]
    public function register(): void
    {
        $this->app->scoped(MarcoDelSitio::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Every public page reads its header menu and footer from the edited home content; the object queries only when a page leaves them out.
        View::composer(['components.publico.cabecera', 'components.publico.pie'], fn (Vista $vista) => $vista->with('marcoDelSitio', $this->app->make(MarcoDelSitio::class)));

        Event::listen(PagoValidado::class, AlValidarPago::class);
        Event::listen(PagoRechazado::class, AlRechazarPago::class);
        Event::listen(PagoAnulado::class, AlAnularPago::class);
        Event::listen(TramiteActualizado::class, NotificarCambioDeTramite::class);
    }
}
