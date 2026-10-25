<?php

declare(strict_types=1);

use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Tramites\Filament\Resources\Tramites\TramiteResource;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;

beforeEach(fn () => Filament::setCurrentPanel('admin'));

it('opens the panel search with Ctrl+K or Cmd+K and shows the shortcut in the box', function (): void {
    $panel = Filament::getPanel('admin');
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

    expect($panel->getGlobalSearchKeyBindings())->toBe(['mod+k'])
        ->and($panel->getGlobalSearchFieldSuffix())->toBe('CTRL+K');
});

it('tells apart groups with the same code by language, cycle and period', function (): void {
    $grupo = Grupo::factory()->create()->load('periodo', 'ciclo.idioma');

    expect(GrupoResource::getGlobalSearchResultDetails($grupo))->toBe([
        'Idioma' => $grupo->ciclo->idioma->nombre.' · '.$grupo->ciclo->nombre,
        'Periodo' => $grupo->periodo->nombre,
    ]);
});

it('shows the concept, amount, date and status of a payment found by its operation number', function (): void {
    $pago = Pago::factory()->create()->load('concepto');

    expect(PagoResource::getGlobalSearchResultDetails($pago))->toBe([
        'Concepto' => $pago->concepto->nombre,
        'Monto' => 'S/ '.number_format((float) $pago->monto, 2),
        'Pagado el' => $pago->fecha_pago->format('d/m/Y'),
        'Estado' => $pago->estado->etiqueta(),
    ]);
});

it('shows the type and status of a procedure found by its code', function (): void {
    $tramite = Tramite::factory()->create();

    expect(TramiteResource::getGlobalSearchResultDetails($tramite))->toBe([
        'Tipo' => $tramite->tipo_nombre,
        'Estado' => $tramite->estado->etiqueta(),
    ]);
});

it('collapses the sidebar with Ctrl+B or Cmd+B and names the shortcut on the button', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'pagos.ver']));

    $this->get('/admin/pagos')->assertOk()
        ->assertSee('data-atajo="mod+b"', escape: false)
        ->assertSee('Contraer el menú (Ctrl o ⌘ + B)');
});
