<?php

declare(strict_types=1);

namespace App\Modules\Pagos;

use App\Modules\Pagos\Actions\ConsultarRecargoDeMatricula;
use App\Modules\Pagos\Actions\DescribirPago;
use App\Modules\Pagos\Actions\ListarCatalogoDeConceptos;
use App\Modules\Pagos\Actions\ListarMediosDePago;
use App\Modules\Pagos\Actions\ListarPagosValidadosDePersona;
use App\Modules\Pagos\Actions\ListarTarifasVigentes;
use App\Modules\Pagos\Actions\RegistrarPagoAnteriorAlSistema;
use App\Modules\Pagos\Actions\ResumirPagosDePersona;
use App\Modules\Pagos\Actions\VerificarPagoParaServicio;
use App\Modules\Pagos\Contracts\CatalogoDeConceptos;
use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Pagos\Contracts\FormularioDePagoAnterior;
use App\Modules\Pagos\Contracts\MediosDePago;
use App\Modules\Pagos\Contracts\PagosAnterioresPermitidos;
use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Contracts\RecargoDeMatricula;
use App\Modules\Pagos\Contracts\RegistroDePagoAnterior;
use App\Modules\Pagos\Contracts\ResumenDePagosDePersona;
use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Filament\Personas\PagosDeLaPersona;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Modules\Pagos\Filament\Schemas\FormularioDePagoAnteriorAlSistema;
use App\Modules\Pagos\Support\InterruptorDePagosAnteriores;
use App\Shared\Support\Filament\FichaDePersonaEnElPanel;
use App\Shared\Support\ModuleServiceProvider;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

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
        CatalogoDeConceptos::class => ListarCatalogoDeConceptos::class,
        RecargoDeMatricula::class => ConsultarRecargoDeMatricula::class,
        MediosDePago::class => ListarMediosDePago::class,
        RegistroDePagoAnterior::class => RegistrarPagoAnteriorAlSistema::class,
        PagosAnterioresPermitidos::class => InterruptorDePagosAnteriores::class,
        FormularioDePagoAnterior::class => FormularioDePagoAnteriorAlSistema::class,
    ];

    #[\Override]
    protected string $module = 'Pagos';

    public function boot(): void
    {
        parent::boot();

        $ficha = app(FichaDePersonaEnElPanel::class);
        $ficha->pestana('pagos', 'Pagos', PagosDeLaPersona::class, PermisoPago::Ver->value, 20);
        $ficha->atajo('registrar-pago', fn (int $personaId): ?Action => auth()->user()?->can(PermisoPago::Registrar->value) === true
            ? Action::make('registrarPago')->label('Registrar pago')->icon(Heroicon::OutlinedBanknotes)->color('gray')->url(PagoResource::getUrl('create', ['persona' => $personaId]))
            : null, 20);
    }
}
