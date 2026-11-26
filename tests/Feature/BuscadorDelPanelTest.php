<?php

declare(strict_types=1);

use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Academico\Filament\Resources\Idiomas\IdiomaResource;
use App\Modules\Academico\Filament\Resources\Idiomas\Pages\ListIdiomas;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Filament\Resources\Tramites\TramiteResource;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

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

it('finds people by surname or document for staff who can see people, with their link to the CILN', function (): void {
    $persona = Persona::factory()->create(['apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa', 'numero_documento' => '41234567']);
    Persona::factory()->create(['apellido_paterno' => 'Torres', 'apellido_materno' => 'Salas', 'nombres' => 'Luis', 'numero_documento' => '87654321']);
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value]));
    $recurso = PersonaResource::class;

    foreach (['quispe mam', '4123'] as $termino) {
        $resultados = $recurso::getGlobalSearchResults($termino);

        expect($resultados)->toHaveCount(1)
            ->and($resultados->first()->title)->toBe($persona->etiqueta)
            ->and($resultados->first()->details)->toBe(['Vínculo' => $persona->vinculo->nombre])
            ->and($resultados->first()->url)->toBe($recurso::getUrl('view', ['record' => $persona]));
    }

    expect($recurso::canGloballySearch())->toBeTrue();
});

it('keeps people out of the panel search for staff without the people permission', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'pagos.ver']));

    expect(PersonaResource::canGloballySearch())->toBeFalse();
});

it('finds languages in the panel search and tables without typing accents or capitals', function (): void {
    $frances = Idioma::factory()->create(['codigo' => 'FR', 'nombre' => 'Francés']);
    $ingles = Idioma::factory()->create(['codigo' => 'EN', 'nombre' => 'Inglés']);
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'academico.ver']));

    expect(IdiomaResource::getGlobalSearchResults('frances')->pluck('title')->all())->toBe(['Francés'])
        ->and(IdiomaResource::getGlobalSearchResults('INGLÉS')->pluck('title')->all())->toBe(['Inglés']);

    Livewire::test(ListIdiomas::class)
        ->searchTable('ingles')
        ->assertCanSeeTableRecords([$ingles])
        ->assertCanNotSeeTableRecords([$frances]);
});

it('finds people without typing the accents or the ñ of their names', function (): void {
    $persona = Persona::factory()->create(['apellido_paterno' => 'Núñez', 'apellido_materno' => 'Peña', 'nombres' => 'José', 'numero_documento' => '41234567']);
    Persona::factory()->create(['apellido_paterno' => 'Torres', 'apellido_materno' => 'Salas', 'nombres' => 'Luis', 'numero_documento' => '87654321']);
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value]));

    foreach (['nunez pena', 'NUÑEZ', 'jose'] as $termino) {
        expect(PersonaResource::getGlobalSearchResults($termino)->pluck('title')->all())->toBe([$persona->etiqueta]);
    }
});
