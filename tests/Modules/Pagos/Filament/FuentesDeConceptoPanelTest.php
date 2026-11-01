<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Filament\Resources\Conceptos\Pages\CreateConcepto;
use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\FuenteDeConceptoResource;
use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\Pages\CreateFuenteDeConcepto;
use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\Pages\ListFuentesDeConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\FuenteDeConcepto;
use App\Shared\Auth\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->administrador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Configurar->value]);
});

function migracionDeFuentes(): Migration
{
    return require base_path('app/Modules/Pagos/Database/Migrations/2026_11_01_100300_turn_fee_sources_into_a_catalog.php');
}

it('starts with the TUPA and TUSNE sources', function (): void {
    expect(FuenteDeConcepto::query()->activos()->pluck('nombre', 'codigo')->all())->toBe(['tupa' => 'TUPA', 'tusne' => 'TUSNE']);
});

it('keeps the source screens away from users without the configuration permission', function (): void {
    $cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value]);

    $this->actingAs($cajero)->get(FuenteDeConceptoResource::getUrl('index'))->assertForbidden();
});

it('creates a source and offers it, while active, when creating a concept', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(CreateFuenteDeConcepto::class)
        ->fillForm(['nombre' => 'Tarifario interno', 'orden' => 3])
        ->call('create')
        ->assertHasNoFormErrors();
    $interno = FuenteDeConcepto::query()->where('codigo', 'tarifario_interno')->sole();

    Livewire::test(CreateConcepto::class)
        ->assertFormFieldExists('fuente_id', fn (Select $campo): bool => $campo->getOptions() === [FuenteDeConcepto::idDe('tupa') => 'TUPA', FuenteDeConcepto::idDe('tusne') => 'TUSNE', $interno->id => 'Tarifario interno'])
        ->fillForm(['codigo' => 'INT-01', 'nombre' => 'Taller de conversación', 'tipo' => TipoConcepto::Constancia->value, 'fuente_id' => $interno->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Concepto::query()->where('codigo', 'INT-01')->sole()->fuenteDeConcepto->nombre)->toBe('Tarifario interno');

    $interno->update(['activo' => false]);

    Livewire::test(CreateConcepto::class)
        ->assertFormFieldExists('fuente_id', fn (Select $campo): bool => ! array_key_exists($interno->id, $campo->getOptions()));
});

it('lists the sources with no way to delete them, and the database keeps a source in use', function (): void {
    $this->actingAs($this->administrador);
    $concepto = Concepto::factory()->create();

    Livewire::test(ListFuentesDeConcepto::class)
        ->assertCanSeeTableRecords(FuenteDeConcepto::query()->get())
        ->assertTableActionDoesNotExist('delete');

    expect(fn () => DB::table('pag_fuentes_concepto')->where('id', $concepto->fuente_id)->delete())->toThrow(QueryException::class);
});

it('keeps the source of the concepts stored before the catalog existed', function (): void {
    $concepto = Concepto::factory()->create(['fuente_id' => FuenteDeConcepto::idDe('tusne')]);
    migracionDeFuentes()->down();

    expect(DB::table('pag_conceptos')->where('id', $concepto->id)->value('fuente'))->toBe('tusne');

    migracionDeFuentes()->up();

    expect($concepto->refresh()->fuenteDeConcepto->codigo)->toBe('tusne');
});
