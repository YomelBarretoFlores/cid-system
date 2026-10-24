<?php

declare(strict_types=1);

namespace App\Modules\Personas\Livewire;

use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Inicio')]
final class InicioDelPortal extends Component
{
    public function render(): View
    {
        return view('personas::livewire.inicio', [
            'ficha' => app(FichaDelUsuario::class)((int) auth()->id()) ?? abort(403),
        ]);
    }
}
