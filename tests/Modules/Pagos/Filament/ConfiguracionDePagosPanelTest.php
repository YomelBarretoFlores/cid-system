<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Filament\Resources\Conceptos\ConceptoResource;
use App\Modules\Pagos\Filament\Resources\Conceptos\Pages\CreateConcepto;
use App\Modules\Pagos\Filament\Resources\Conceptos\Pages\EditConcepto;
use App\Modules\Pagos\Filament\Resources\Conceptos\Pages\ListConceptos;
use App\Modules\Pagos\Filament\Resources\Conceptos\RelationManagers\TarifasRelationManager;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->administrador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Configurar->value]);
    $this->concepto = Concepto::factory()->create(['codigo' => 'TUPA-010', 'nombre' => 'Derecho de matrícula']);
    $this->vigente = Tarifa::factory()->for($this->concepto)->create([
        'tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '150.00', 'vigente_desde' => '2027-01-01',
    ]);
});

function tarifasDe(Concepto $concepto): Testable
{
    return Livewire::test(TarifasRelationManager::class, ['ownerRecord' => $concepto, 'pageClass' => EditConcepto::class]);
}

it('keeps the configuration screens away from users without the configuration permission', function (): void {
    $cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Validar->value]);

    $this->actingAs($cajero)->get(ConceptoResource::getUrl('index'))->assertForbidden();
    $this->actingAs($cajero)->get(ConceptoResource::getUrl('edit', ['record' => $this->concepto]))->assertForbidden();
});

it('lists concepts with their source, type and status', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(ListConceptos::class)
        ->assertCanSeeTableRecords([$this->concepto])
        ->assertTableColumnFormattedStateSet('tipo', 'Matrícula', $this->concepto)
        ->assertTableColumnFormattedStateSet('fuente', 'TUPA', $this->concepto);
});

it('creates a concept with a normalized code', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(CreateConcepto::class)
        ->fillForm(['codigo' => ' tusne-07 ', 'nombre' => 'Constancia de estudios', 'tipo' => TipoConcepto::Constancia->value, 'fuente' => FuenteConcepto::Tusne->value])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Concepto::query()->where('codigo', 'TUSNE-07')->sole())
        ->tipo->toBe(TipoConcepto::Constancia)
        ->activo->toBeTrue();
});

it('refuses a repeated concept code', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(CreateConcepto::class)
        ->fillForm(['codigo' => 'tupa-010', 'nombre' => 'Otro', 'tipo' => TipoConcepto::Otro->value, 'fuente' => FuenteConcepto::Tupa->value])
        ->call('create')
        ->assertHasFormErrors(['codigo']);

    expect(Concepto::query()->count())->toBe(1);
});

it('reports a code created by someone else at the same time as a form error', function (): void {
    $this->actingAs($this->administrador);
    Concepto::creating(function (Concepto $nuevo): void {
        DB::table('pag_conceptos')->insert(['codigo' => $nuevo->codigo, 'nombre' => 'Simultáneo', 'tipo' => 'otro', 'fuente' => 'tupa', 'activo' => true]);
    });

    Livewire::test(CreateConcepto::class)
        ->fillForm(['codigo' => 'tupa-099', 'nombre' => 'Otro', 'tipo' => TipoConcepto::Otro->value, 'fuente' => FuenteConcepto::Tupa->value])
        ->call('create')
        ->assertHasFormErrors(['codigo']);
});

it('deactivates a concept instead of deleting it, keeping its code', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(EditConcepto::class, ['record' => $this->concepto->getRouteKey()])
        ->assertActionDoesNotExist('delete')
        ->assertSchemaComponentStateSet('codigo', 'TUPA-010')
        ->fillForm(['nombre' => 'Derecho de matrícula regular', 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->concepto->refresh())
        ->nombre->toBe('Derecho de matrícula regular')
        ->codigo->toBe('TUPA-010')
        ->activo->toBeFalse();
});

it('lists the fees of a concept with their validity', function (): void {
    $this->actingAs($this->administrador);

    tarifasDe($this->concepto)
        ->assertCanSeeTableRecords([$this->vigente])
        ->assertTableColumnFormattedStateSet('tipo_vinculo', 'Público general', $this->vigente);
});

it('registers a new fee from a date, closing the open one the day before', function (): void {
    $this->actingAs($this->administrador);

    tarifasDe($this->concepto)
        ->callAction(TestAction::make('registrarTarifa')->table(), data: [
            'tipo_vinculo' => TipoVinculo::PublicoGeneral->value, 'monto' => '175.50', 'vigente_desde' => '2027-07-01',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('Tarifa registrada');

    expect($this->vigente->refresh()->vigente_hasta->toDateString())->toBe('2027-06-30')
        ->and(Tarifa::query()->where('concepto_id', $this->concepto->id)->latest('id')->first()->monto)->toBe('175.50');
});

it('closes a fee on a date and explains when it cannot', function (): void {
    Pago::factory()->create(['concepto_id' => $this->concepto->id, 'tarifa_id' => $this->vigente->id, 'fecha_pago' => '2027-03-10']);
    $this->actingAs($this->administrador);

    tarifasDe($this->concepto)
        ->callAction(TestAction::make('cerrar')->table($this->vigente), data: ['vigente_hasta' => '2027-03-01'])
        ->assertNotified('La tarifa se aplicó a un pago del 10/03/2027; no puede cerrarse antes de esa fecha.');
    expect($this->vigente->refresh()->vigente_hasta)->toBeNull();

    tarifasDe($this->concepto)
        ->callAction(TestAction::make('cerrar')->table($this->vigente), data: ['vigente_hasta' => '2027-03-31'])
        ->assertNotified('Tarifa cerrada');
    expect($this->vigente->refresh()->vigente_hasta->toDateString())->toBe('2027-03-31');
});

it('deletes only fees that no payment applied', function (): void {
    $sinUso = Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'vigente_desde' => '2027-01-01']);
    Pago::factory()->create(['concepto_id' => $this->concepto->id, 'tarifa_id' => $this->vigente->id]);
    $this->actingAs($this->administrador);

    tarifasDe($this->concepto)
        ->assertActionHidden(TestAction::make('delete')->table($this->vigente))
        ->callAction(TestAction::make('delete')->table($sinUso));

    expect(Tarifa::query()->whereKey($sinUso->id)->exists())->toBeFalse()
        ->and(Tarifa::query()->whereKey($this->vigente->id)->exists())->toBeTrue();
});
