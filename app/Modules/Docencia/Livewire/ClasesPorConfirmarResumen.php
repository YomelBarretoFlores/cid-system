<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Livewire;

use App\Modules\Docencia\Support\BandejaDelDelegado;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Contracts\View\View;
use Livewire\Component;

// Embedded in the portal home: Personas shows it without depending on this module's classes.
final class ClasesPorConfirmarResumen extends Component
{
    public function render(): View
    {
        $ficha = app(FichaDelUsuario::class)((int) auth()->id());

        return view('docencia::livewire.clases-por-confirmar-resumen', [
            'pendientes' => $ficha === null ? 0 : app(BandejaDelDelegado::class)($ficha->personaId)->pendientesDelDelegado(),
        ]);
    }
}
