<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

// A module's table of one person's records, shown as a tab of the person page.
abstract class TablaDeLaPersona extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    #[Locked]
    public int $personaId;

    abstract protected function permiso(): string;

    public function mount(int $personaId): void
    {
        abort_unless(auth()->user()?->can($this->permiso()) === true, 403);

        $this->personaId = $personaId;
    }

    // Every later request of the component checks the permission again, since a role can change while the page is open.
    public function hydrate(): void
    {
        abort_unless(auth()->user()?->can($this->permiso()) === true, 403);
    }

    public function render(): View
    {
        return view('filament.tabla-de-la-persona');
    }
}
