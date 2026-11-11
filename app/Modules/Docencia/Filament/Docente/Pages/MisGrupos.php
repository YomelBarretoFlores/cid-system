<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Docente\Pages;

use App\Modules\Docencia\Actions\ResumirMisGrupos;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;

final class MisGrupos extends Page
{
    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    #[\Override]
    protected static ?int $navigationSort = 1;

    #[\Override]
    protected static ?string $navigationLabel = 'Mis grupos';

    #[\Override]
    protected static ?string $title = 'Mis grupos';

    #[\Override]
    protected static ?string $slug = 'mis-grupos';

    #[\Override]
    protected ?string $subheading = 'Los grupos que tienes a cargo en este momento. Si la coordinación te reasigna un grupo, deja de aparecer aquí.';

    #[\Override]
    protected string $view = 'docencia::filament.docente.mis-grupos';

    // A group's attendance, pre-acta, final acta and syllabus pages keep "Mis grupos" marked in the menu.
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

        return ['grupos' => app(ResumirMisGrupos::class)($docenteId)];
    }
}
