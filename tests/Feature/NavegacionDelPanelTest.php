<?php

declare(strict_types=1);

use App\Modules\Tablero\Enums\PermisoTablero;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Spatie\Permission\Models\Permission;

beforeEach(fn () => Filament::setCurrentPanel('admin'));

it('orders the sidebar by daily work, with every section in its group', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo(Permission::all()));
    $this->get('/admin/tablero-gerencial')->assertOk();

    $menu = collect(Filament::getNavigation())->mapWithKeys(fn (NavigationGroup $grupo): array => [
        (string) ($grupo->getLabel() ?? '') => collect($grupo->getItems())->map(fn (NavigationItem $item): string => $item->getLabel())->values()->all(),
    ])->all();

    expect($menu)->toBe([
        '' => ['Tablero gerencial'],
        'Atención' => ['Matrículas', 'Solicitudes de clasificación', 'Pagos', 'Trámites', 'Personas'],
        'Oferta académica' => ['Periodos', 'Idiomas y ciclos', 'Grupos', 'Aulas'],
        'Docencia' => ['Docentes', 'Expediente de horas', 'Costos por hora'],
        'Certificación' => ['Actas finales', 'Constancias y certificados'],
        'Reportes' => ['Reporte de recaudación'],
        'Configuración' => ['Conceptos y tarifas', 'Tipos de trámite'],
    ]);
});

it('opens the panel on the dashboard for staff who can see it', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTablero::Ver->value]));

    $this->get('/admin')->assertRedirect('/admin/tablero-gerencial');
});

it('opens the panel on the first section an account can see when it has no dashboard', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'pagos.ver']));

    $this->get('/admin')->assertRedirect('/admin/pagos');
});

it('tells an account without sections that it has no permissions yet', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION));

    $this->get('/admin')->assertOk()->assertSee('Tu cuenta todavía no tiene secciones asignadas.');
});
