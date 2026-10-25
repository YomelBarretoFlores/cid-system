<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Livewire;

use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Support\AccesoDelDocente;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Actas finales')]
final class MisActasFinales extends Component
{
    public function render(): View
    {
        $docenteId = app(AccesoDelDocente::class)->docenteId((int) auth()->id());

        return view('certificacion::livewire.mis-actas-finales', [
            'actas' => Acta::query()
                ->where('docente_id', $docenteId)
                ->withCount(['notas', 'notas as aprobados_count' => fn ($consulta) => $consulta->where('estado_final', 'aprobado')])
                ->orderByDesc('validada_en')
                ->orderBy('grupo_codigo')
                ->get(),
        ]);
    }
}
