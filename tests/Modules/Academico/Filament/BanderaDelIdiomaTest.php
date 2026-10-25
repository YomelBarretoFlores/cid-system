<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Idiomas\Pages\CreateIdioma;
use App\Modules\Academico\Filament\Resources\Idiomas\Pages\EditIdioma;
use App\Modules\Academico\Models\Idioma;
use App\Shared\Auth\Models\User;
use App\Shared\Ui\BanderaDeIdioma;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value, PermisoAcademico::Gestionar->value]));
});

function hoja(string $idioma): string
{
    return Blade::render('<x-ciln.hoja-bandera :idioma="$idioma" />', ['idioma' => $idioma]);
}

it('offers a fixed list of flags, each with its preview, when creating a language', function (): void {
    expect(BanderaDeIdioma::opciones())->toHaveCount(14)
        ->and(BanderaDeIdioma::opciones()['japon'])->toContain('Japón')->toContain('<svg');

    Livewire::test(CreateIdioma::class)
        ->fillForm(['codigo' => 'JAP', 'nombre' => 'Japonés', 'bandera' => 'japon'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Idioma::query()->where('codigo', 'JAP')->firstOrFail()->bandera)->toBe(BanderaDeIdioma::Japon);
});

it('suggests the flag from the name when the language has none stored yet', function (): void {
    $frances = Idioma::factory()->create(['nombre' => 'Francés']);

    Livewire::test(EditIdioma::class, ['record' => $frances->getRouteKey()])
        ->assertFormSet(['bandera' => 'francia']);

    expect(BanderaDeIdioma::sugeridaPara('Inglés'))->toBe(BanderaDeIdioma::EstadosUnidos)
        ->and(BanderaDeIdioma::sugeridaPara(' QUECHUA '))->toBe(BanderaDeIdioma::Wiphala)
        ->and(BanderaDeIdioma::sugeridaPara('Japonés'))->toBe(BanderaDeIdioma::Japon)
        ->and(BanderaDeIdioma::sugeridaPara('Sueco'))->toBe(BanderaDeIdioma::Neutra);
});

it('draws the stored flag wherever the language name appears, even if the name changes', function (): void {
    $idioma = Idioma::factory()->create(['nombre' => 'Ingles', 'bandera' => BanderaDeIdioma::ReinoUnido]);

    expect(hoja('Ingles'))->toContain('#012169')
        ->and(hoja('Francés'))->toContain('#0055A4');

    Livewire::test(EditIdioma::class, ['record' => $idioma->getRouteKey()])
        ->fillForm(['bandera' => 'japon'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($idioma->refresh()->bandera)->toBe(BanderaDeIdioma::Japon)
        ->and(hoja('Ingles'))->toContain('#BC002D');
});
