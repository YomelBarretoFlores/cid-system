<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Matricula\Actions\ResumirAvanceDelEstudiante;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Contracts\View\View;
use Livewire\Component;

// Embedded in the portal home: Personas shows it without depending on this module's classes.
final class ResumenDelEstudiante extends Component
{
    public function render(): View
    {
        $ficha = app(FichaDelUsuario::class)((int) auth()->id()) ?? abort(403);

        return view('matricula::livewire.resumen-del-estudiante', [
            'ficha' => $ficha,
            'avance' => app(ResumirAvanceDelEstudiante::class)($ficha->personaId),
        ]);
    }
}
