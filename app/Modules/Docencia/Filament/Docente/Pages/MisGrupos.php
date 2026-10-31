<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Docente\Pages;

use App\Modules\Docencia\Actions\ListarGruposDelDocente;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;

final class MisGrupos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Mis grupos';

    protected static ?string $title = 'Mis grupos';

    protected static ?string $slug = 'mis-grupos';

    protected ?string $subheading = 'Los grupos que tienes a cargo en este momento. Si la coordinación te reasigna un grupo, deja de aparecer aquí.';

    protected string $view = 'docencia::filament.docente.mis-grupos';

    // A group's attendance, pre-acta and final acta pages keep "Mis grupos" marked in the menu.
    public static function getNavigationItemActiveRoutePattern(): array
    {
        return [self::getRouteName(), 'filament.docente.pages.grupos.*'];
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $docenteId = app(DocenteDelUsuario::class)((int) auth()->id()) ?? abort(403);

        return ['grupos' => app(ListarGruposDelDocente::class)($docenteId)];
    }
}
