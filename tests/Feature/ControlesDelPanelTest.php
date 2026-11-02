<?php

declare(strict_types=1);

use App\Modules\Academico\Filament\Resources\Grupos\Pages\ListGrupos;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Livewire\Livewire;

it('uses the panel dropdown instead of the browser select in table filters too', function (): void {
    Filament::setCurrentPanel('admin');

    expect(SelectFilter::make('estado')->isNative())->toBeFalse()
        ->and(TernaryFilter::make('activo')->isNative())->toBeFalse();
});

it('names the default order of card grids instead of showing a dash', function (): void {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'academico.ver']));
    Grupo::factory()->create();

    Livewire::test(ListGrupos::class)->assertSeeHtml('Predeterminado');
});

it('gives the teacher panel its own theme on top of the staff one', function (): void {
    expect(Filament::getPanel('docente')->getViteTheme())->toBe('resources/css/filament/docente/theme.css')
        ->and(Filament::getPanel('admin')->getViteTheme())->toBe('resources/css/filament/admin/theme.css')
        ->and((string) file_get_contents(resource_path('css/filament/docente/theme.css')))->toContain("@import '../admin/theme.css';");
});
