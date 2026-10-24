<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ListPagos;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ViewPago;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function usuarioDelPanel(PermisoPago ...$permisos): User
{
    return User::factory()->create()->givePermissionTo([
        User::ADMIN_PANEL_PERMISSION,
        ...array_map(fn (PermisoPago $permiso): string => $permiso->value, $permisos),
    ]);
}

beforeEach(function (): void {
    $this->persona = Persona::factory()->create([
        'numero_documento' => '40123456', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Ana',
    ]);
    $this->pendiente = Pago::factory()->create(['persona_id' => $this->persona->id, 'numero_operacion' => 'OP-100']);
    $this->validado = Pago::factory()->validado()->create(['numero_operacion' => 'OP-200']);
});

it('keeps the payment screens away from users without permission to see payments', function (string $pagina): void {
    $this->actingAs(usuarioDelPanel(PermisoPago::Registrar, PermisoPago::Validar));

    $this->get(PagoResource::getUrl($pagina, $pagina === 'view' ? ['record' => $this->pendiente] : []))->assertForbidden();
})->with(['index', 'view']);

it('lists payments with the person, concept, amount and state', function (): void {
    $this->actingAs(usuarioDelPanel(PermisoPago::Ver));

    Livewire::test(ListPagos::class)
        ->assertCanSeeTableRecords([$this->pendiente, $this->validado])
        ->assertTableColumnFormattedStateSet('persona_id', 'DNI 40123456 · Quispe Mamani, Ana', $this->pendiente)
        ->assertTableColumnStateSet('concepto.nombre', $this->pendiente->concepto->nombre, $this->pendiente)
        ->assertTableColumnFormattedStateSet('estado', 'Pendiente', $this->pendiente)
        ->assertTableColumnFormattedStateSet('estado', 'Validado', $this->validado);
});

it('labels every person on the page with a single lookup', function (): void {
    Pago::factory()->count(5)->create();
    $this->actingAs(usuarioDelPanel(PermisoPago::Ver));
    DB::enableQueryLog();

    Livewire::test(ListPagos::class);

    $consultas = collect(DB::getQueryLog())->filter(fn (array $consulta): bool => str_contains($consulta['query'], '"per_personas"'));
    expect($consultas)->toHaveCount(1);
});

it('filters payments by state', function (): void {
    $this->actingAs(usuarioDelPanel(PermisoPago::Ver));

    Livewire::test(ListPagos::class)
        ->filterTable('estado', 'pendiente')
        ->assertCanSeeTableRecords([$this->pendiente])
        ->assertCanNotSeeTableRecords([$this->validado]);
});

it('finds payments by operation number, person document or person name', function (string $busqueda): void {
    $this->actingAs(usuarioDelPanel(PermisoPago::Ver));

    Livewire::test(ListPagos::class)
        ->searchTable($busqueda)
        ->assertCanSeeTableRecords([$this->pendiente])
        ->assertCanNotSeeTableRecords([$this->validado]);
})->with(['OP-100', '4012', 'quispe']);

it('shows who reviewed a payment and when', function (): void {
    $this->actingAs(usuarioDelPanel(PermisoPago::Ver));
    $this->validado->validador->update(['name' => 'Cajera Demo']);

    Livewire::test(ViewPago::class, ['record' => $this->validado->getRouteKey()])
        ->assertSee(['Revisión', 'Cajera Demo', $this->validado->validado_en->timezone('America/Lima')->format('d/m/Y H:i')]);
});

it('downloads the receipt from the private disk', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put($this->pendiente->recibo_path, '%PDF-1.4 recibo');
    $this->actingAs(usuarioDelPanel(PermisoPago::Ver));

    Livewire::test(ViewPago::class, ['record' => $this->pendiente->getRouteKey()])
        ->callAction('descargarRecibo')
        ->assertFileDownloaded('recibo-op-100.pdf');
});

it('explains a missing receipt file instead of failing', function (): void {
    Storage::fake('local');
    $this->actingAs(usuarioDelPanel(PermisoPago::Ver));

    Livewire::test(ViewPago::class, ['record' => $this->pendiente->getRouteKey()])
        ->callAction('descargarRecibo')
        ->assertNoFileDownloaded()
        ->assertNotified('No se encontró el archivo del recibo.');
});

it('finds payments of every matching person, beyond the picker limit', function (): void {
    $personas = Persona::factory()->count(60)->create(['apellido_paterno' => 'Huamán']);
    $pagos = $personas->map(fn (Persona $persona): Pago => Pago::factory()->create(['persona_id' => $persona->id]));
    $this->actingAs(usuarioDelPanel(PermisoPago::Ver));

    Livewire::test(ListPagos::class)
        ->set('tableRecordsPerPage', 100)
        ->searchTable('huamán')
        ->assertCanSeeTableRecords($pagos)
        ->assertCountTableRecords(60);
});

it('offers no screen to edit or delete payments', function (): void {
    expect(array_keys(PagoResource::getPages()))->not->toContain('edit')
        ->and(PagoResource::canDelete($this->pendiente))->toBeFalse();
});
