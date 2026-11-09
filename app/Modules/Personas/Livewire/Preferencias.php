<?php

declare(strict_types=1);

namespace App\Modules\Personas\Livewire;

use App\Shared\Auth\Actions\GuardarTemaDeInterfaz;
use App\Shared\Auth\Enums\TemaDeInterfaz;
use App\Shared\Auth\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Preferencias')]
final class Preferencias extends Component
{
    public const string ELIGE_UN_TEMA = 'Elige Claro, Oscuro o Del sistema.';

    public string $tema = '';

    public function mount(): void
    {
        $usuario = auth()->user();
        $this->tema = $usuario instanceof User ? $usuario->tema->value : TemaDeInterfaz::Sistema->value;
    }

    public function guardar(GuardarTemaDeInterfaz $guardar): void
    {
        $this->validate(['tema' => ['required', Rule::enum(TemaDeInterfaz::class)]], ['tema' => self::ELIGE_UN_TEMA]);

        $guardar((int) auth()->id(), TemaDeInterfaz::from($this->tema));

        // A full reload, not wire:navigate, so the layout paints the new theme from the server.
        session()->flash('estado', 'Guardamos tus preferencias.');
        $this->redirectRoute('estudiante.preferencias');
    }

    public function render(): View
    {
        return view('personas::livewire.preferencias', ['opciones' => TemaDeInterfaz::cases()]);
    }
}
