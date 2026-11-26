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
use App\Shared\Ui\Filament\Clusters\Configuracion;
use App\Shared\Ui\Filament\Clusters\Reportes;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(fn () => Filament::setCurrentPanel('admin'));

it('orders the sidebar by TdR module, with every section in its group', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo(Permission::all()));
    $this->get('/admin/tablero-gerencial')->assertOk();

    $menu = collect(Filament::getNavigation())->mapWithKeys(fn (NavigationGroup $grupo): array => [
        (string) ($grupo->getLabel() ?? '') => collect($grupo->getItems())->map(fn (NavigationItem $item): string => $item->getLabel())->values()->all(),
    ])->all();

    expect($menu)->toBe([
        '' => ['Tablero gerencial'],
        'Matrícula' => ['Personas', 'Solicitudes de clasificación', 'Matrículas', 'Ciclos habilitados'],
        'Oferta académica' => ['Periodos', 'Idiomas y ciclos', 'Grupos', 'Aulas', 'Programas'],
        'Pagos' => ['Pagos'],
        'Docencia' => ['Docentes', 'Control de clases', 'Expediente de horas'],
        'Certificación' => ['Actas pendientes', 'Actas finales', 'Constancias y certificados'],
        'Trámites' => ['Trámites'],
        'Administración' => ['Reportes', 'Configuración'],
        'Seguridad' => ['Usuarios del personal', 'Roles', 'Auditoría'],
    ]);
});

it('gathers the reports and every catalog, setting and editable text under two menu entries', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo(Permission::all()));

    $contenido = fn (string $cluster): array => collect($cluster::getClusteredComponents())
        ->groupBy(fn (string $pantalla): string => (string) ($pantalla::getNavigationGroup() ?? ''))
        ->map(fn ($pantallas): array => $pantallas->map(fn (string $pantalla): string => (string) $pantalla::getNavigationLabel())->sort()->values()->all())
        ->sortKeys()->all();

    expect($contenido(Reportes::class))->toBe(['' => ['Asistencia por grupo', 'Carga docente', 'Matriculados', 'Notas por periodo', 'Reporte de recaudación']])
        ->and($contenido(Configuracion::class))->toBe([
            'Académico' => ['Modalidades', 'Sistemas de evaluación'],
            'Docencia' => ['Bancos', 'Condiciones laborales', 'Costos por hora', 'Grados académicos'],
            'Institución' => ['Contenido de la portada', 'Datos institucionales', 'Denominación del año', 'Firmantes', 'Parámetros del sistema', 'Política de privacidad', 'Textos de documentos'],
            'Pagos' => ['Conceptos y tarifas', 'Configuración de pagos', 'Fuentes TUPA/TUSNE', 'Medios de pago'],
            'Personas' => ['Tipos de documento', 'Tipos de vínculo'],
            'Trámites' => ['Grados del FUT', 'Tipos de trámite'],
        ]);
    $this->get(Configuracion::getUrl())->assertRedirect();
    $this->get(Reportes::getUrl())->assertRedirect();
});

it('opens every moved screen at its cluster address for staff allowed to use it', function (string $cluster, string $prefijo): void {
    $this->actingAs(User::factory()->create()->givePermissionTo(Permission::all()));

    foreach ($cluster::getClusteredComponents() as $pantalla) {
        $url = is_subclass_of($pantalla, Resource::class) ? $pantalla::getUrl('index') : $pantalla::getUrl();

        expect(parse_url($url, PHP_URL_PATH))->toStartWith($prefijo);
        $this->get($url)->assertOk();
    }
})->with([
    'configuration' => [Configuracion::class, '/admin/configuracion/'],
    'reports' => [Reportes::class, '/admin/reportes/'],
]);

it('hides the configuration entry from staff without any catalog permission', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'pagos.ver']));
    $this->get('/admin/pagos')->assertOk();

    $entradas = collect(Filament::getNavigation())->flatMap(fn (NavigationGroup $grupo): array => collect($grupo->getItems())->map(fn (NavigationItem $item): string => $item->getLabel())->all());

    expect($entradas)->not->toContain('Configuración');
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
