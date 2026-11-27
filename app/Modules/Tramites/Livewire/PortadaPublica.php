<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Livewire;

use App\Modules\Tramites\Support\AtajosDeEdicion;
use App\Modules\Tramites\Support\ContenidoDePortada;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

// The public home page; choosing a language, a modality or a link filters on the client, without requests.
#[Layout('components.layouts.base', ['marco' => false])]
final class PortadaPublica extends Component
{
    public function render(): View
    {
        // The shared cache holds only the content; the shortcuts depend on who visits.
        return view('tramites::livewire.portada-publica', [...app(ContenidoDePortada::class)->armar(), 'atajos' => app(AtajosDeEdicion::class)(auth()->user())]);
    }
}
