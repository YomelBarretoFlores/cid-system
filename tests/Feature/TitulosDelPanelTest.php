<?php

declare(strict_types=1);

use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

it('writes every panel section title in sentence case, like the rest of the interface', function (): void {
    $titulos = collect(Filament::getPanel('admin')->getResources())->mapWithKeys(fn (string $recurso): array => [
        $recurso => [$recurso::getTitleCasePluralModelLabel(), $recurso::getTitleCaseModelLabel()],
    ]);

    expect($titulos)->not->toBeEmpty();

    $titulos->each(function (array $titulo, string $recurso): void {
        expect($titulo)->toBe([Str::ucfirst($recurso::getPluralModelLabel()), Str::ucfirst($recurso::getModelLabel())]);
    });
});

it('shows the placement requests list as "Solicitudes de clasificación"', function (): void {
    Filament::setCurrentPanel('admin');
    $usuario = User::factory()->create()->givePermissionTo([
        User::ADMIN_PANEL_PERMISSION,
        PermisoClasificacion::Ver->value,
    ]);

    $this->actingAs($usuario)->get('/admin/solicitudes-clasificacion')
        ->assertOk()
        ->assertSee('Solicitudes de clasificación')
        ->assertDontSee('Solicitudes De Clasificación');
});
