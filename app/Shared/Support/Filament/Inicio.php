<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;

// The panel opens on the first section each account can see (the dashboard for management), never on an empty page.
final class Inicio extends Dashboard
{
    #[\Override]
    protected static ?string $title = 'Inicio';

    #[\Override]
    protected static bool $shouldRegisterNavigation = false;

    // Keeps the route name the portal sign-in already links to (filament.admin.pages.dashboard).
    #[\Override]
    protected static ?string $slug = 'dashboard';

    #[\Override]
    protected string $view = 'filament.admin.inicio';

    public function mount(): void
    {
        $destino = $this->primeraSeccionVisible();

        if ($destino !== null) {
            $this->redirect($destino);
        }
    }

    public function getWidgets(): array
    {
        return [];
    }

    private function primeraSeccionVisible(): ?string
    {
        foreach (Filament::getNavigation() as $grupo) {
            $item = collect($grupo->getItems())->first(fn (NavigationItem $item): bool => $item->isVisible());

            if ($item instanceof NavigationItem) {
                return $item->getUrl();
            }
        }

        return null;
    }
}
