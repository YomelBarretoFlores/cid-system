<?php

declare(strict_types=1);

use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\SolicitudClasificacionResource;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tablero\Enums\PermisoTablero;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\TramiteResource;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\ContadorDePendientes;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\DB;
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
        'Docencia' => ['Docentes', 'Expediente de horas', 'Costos por hora', 'Control de clases', 'Sílabos'],
        'Certificación' => ['Actas finales', 'Constancias y certificados'],
        'Reportes' => ['Reporte de recaudación', 'Matriculados', 'Asistencia por grupo', 'Notas por periodo', 'Carga docente'],
        'Configuración' => ['Conceptos y tarifas', 'Medios de pago', 'Tipos de trámite', 'Fuentes TUPA/TUSNE', 'Sistemas de evaluación', 'Condiciones laborales', 'Grados académicos', 'Firmantes'],
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

it('counts next to the menu item the payments, placement requests and procedures waiting for staff', function (): void {
    expect(PagoResource::getNavigationBadge())->toBeNull()
        ->and(SolicitudClasificacionResource::getNavigationBadge())->toBeNull()
        ->and(TramiteResource::getNavigationBadge())->toBeNull();

    Pago::factory()->pendiente()->count(2)->create();
    Pago::factory()->validado()->create();
    SolicitudClasificacion::factory()->create();
    SolicitudClasificacion::factory()->evaluada()->create();
    Tramite::factory()->count(3)->create();
    Tramite::factory()->create(['estado' => EstadoTramite::Observado, 'observacion' => 'Falta adjuntar el voucher.']);

    expect(PagoResource::getNavigationBadge())->toBe('2')
        ->and(PagoResource::getNavigationBadgeTooltip())->toBe('Pagos por validar')
        ->and(SolicitudClasificacionResource::getNavigationBadge())->toBe('1')
        ->and(SolicitudClasificacionResource::getNavigationBadgeTooltip())->toBe('Solicitudes por evaluar')
        ->and(TramiteResource::getNavigationBadge())->toBe('3')
        ->and(TramiteResource::getNavigationBadgeTooltip())->toBe('Trámites por atender');
});

it('shows the pending count in the sidebar', function (): void {
    Pago::factory()->pendiente()->count(2)->create();
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'pagos.ver']));

    expect($this->get('/admin/pagos')->assertOk()->getContent())
        ->toMatch('/fi-sidebar-item-badge-ctn.*?<span class="fi-badge-label">\s*2\s*<\/span>/s');
});

it('shows no badge in the sidebar when nothing is pending', function (): void {
    Pago::factory()->validado()->create();
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'pagos.ver']));

    $this->get('/admin/pagos')->assertOk()->assertDontSee('fi-sidebar-item-badge-ctn', escape: false);
});

it('caps the pending count at 99+', function (int $cantidad, ?string $insignia): void {
    $filas = Persona::query()->fromSub(DB::query()->selectRaw('generate_series(1, ?) as id', [$cantidad]), 'per_personas');

    expect(ContadorDePendientes::de($filas))->toBe($insignia);
})->with([
    'nothing pending' => [0, null],
    'ninety-nine' => [99, '99'],
    'one hundred' => [100, '99+'],
]);

it('swaps panel screens in place and prefetches them on hover, leaving the portals as full page loads', function (): void {
    $panel = Filament::getPanel('admin');
    $staff = User::factory()->create();
    $staff->givePermissionTo(Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION));

    expect($panel->hasSpaMode())->toBeTrue()
        ->and($panel->hasSpaPrefetching())->toBeTrue()
        ->and($panel->getSpaUrlExceptions())->toBe(['*/estudiante*', '*/docente*', '*/ingresar*']);
    $this->actingAs($staff)->get('/admin')->assertOk()->assertSee('wire:navigate.hover', false);
});

it('keeps panel dropdowns open when only the window height changes, as a phone keyboard does', function (): void {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee("nombre !== 'filamentDropdown'", false)
        ->assertSee('if (window.innerWidth === ancho)', false);
});
