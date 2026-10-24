<?php

declare(strict_types=1);

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\CostosPorHora\CostoPorHoraResource;
use App\Modules\Docencia\Filament\Resources\CostosPorHora\Pages\ListCostosPorHora;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-05-10 12:00', 'America/Lima'));
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Costos->value]);
    $this->vigente = CostoPorHora::query()->create(['condicion_laboral' => 'contratado', 'costo_hora' => '40.00', 'vigente_desde' => '2027-01-01', 'registrado_por' => $this->gestor->id]);
});

it('keeps the hourly costs away from users without the costs permission', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value, PermisoDocencia::Gestionar->value]);

    $this->actingAs($lector)->get(CostoPorHoraResource::getUrl())->assertForbidden();
});

it('lists the costs with their category, amount and validity', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListCostosPorHora::class)
        ->assertCanSeeTableRecords([$this->vigente])
        ->assertTableColumnFormattedStateSet('condicion_laboral', 'Contratado', $this->vigente)
        ->assertSee('Sin fecha de cierre');
});

it('registers a cost from a date, closing the open one the day before', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListCostosPorHora::class)
        ->callAction('registrarCosto', data: ['condicion_laboral' => 'contratado', 'costo_hora' => '47.50', 'vigente_desde' => '2027-06-01'])
        ->assertHasNoFormErrors()
        ->assertNotified('Costo por hora registrado');

    expect($this->vigente->refresh()->vigente_hasta->toDateString())->toBe('2027-05-31')
        ->and(CostoPorHora::query()->latest('id')->first()->only(['costo_hora', 'registrado_por']))->toBe(['costo_hora' => '47.50', 'registrado_por' => $this->gestor->id]);
});

it('shows a rejected cost as a notification', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListCostosPorHora::class)
        ->callAction('registrarCosto', data: ['condicion_laboral' => 'contratado', 'costo_hora' => '47.50', 'vigente_desde' => '2026-12-01', 'vigente_hasta' => '2027-01-15'])
        ->assertNotified('La vigencia se superpone con otro costo por hora de esta categoría.');

    expect(CostoPorHora::query()->count())->toBe(1);
});

it('offers to delete only a cost not yet in effect', function (): void {
    $futuro = CostoPorHora::query()->create(['condicion_laboral' => 'tercero', 'costo_hora' => '30.00', 'vigente_desde' => '2027-07-01', 'registrado_por' => $this->gestor->id]);
    $this->actingAs($this->gestor);

    Livewire::test(ListCostosPorHora::class)
        ->assertActionHidden(TestAction::make('eliminar')->table($this->vigente))
        ->callAction(TestAction::make('eliminar')->table($futuro))
        ->assertNotified('Costo por hora eliminado');

    expect(CostoPorHora::query()->whereKey($futuro->id)->exists())->toBeFalse();
});
