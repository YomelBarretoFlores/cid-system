<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Livewire;

use App\Modules\Docencia\Actions\ListarGruposDelDocente;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Mis grupos')]
final class MisGrupos extends Component
{
    public function render(): View
    {
        $docenteId = app(DocenteDelUsuario::class)((int) auth()->id()) ?? abort(403);
        $grupos = collect(app(ListarGruposDelDocente::class)($docenteId));

        return view('docencia::livewire.mis-grupos', ['grupos' => $grupos]);
    }
}
