<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Livewire;

use App\Modules\Docencia\Actions\ConfirmarClaseDictada;
use App\Modules\Docencia\Support\BandejaDelDelegado;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Clases por confirmar')]
final class ClasesPorConfirmar extends Component
{
    #[Locked]
    public ?int $observando = null;

    public string $observacion = '';

    #[Locked]
    public ?string $aviso = null;

    private ?int $personaId = null;

    public function confirmar(int $claseId): void
    {
        $this->cancelar();
        $this->responder($claseId, true, 'confirmacion');
    }

    public function observar(int $claseId): void
    {
        $this->cancelar();
        $this->observando = $claseId;
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
        $this->reset(['observando', 'observacion', 'aviso']);
    }

    public function enviarObservacion(): void
    {
        if ($this->observando !== null) {
            $this->responder($this->observando, false, 'observacion');
        }
    }

    public function render(): View
    {
        return view('docencia::livewire.clases-por-confirmar', [
            'clases' => app(BandejaDelDelegado::class)($this->personaId()),
        ]);
    }

    private function responder(int $claseId, bool $dictada, string $campo): void
    {
        $this->resetErrorBag();

        try {
            $clase = app(ConfirmarClaseDictada::class)($claseId, $this->personaId(), $dictada, $this->observacion);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError($campo, $error->getMessage());

            return;
        }

        $this->reset(['observando', 'observacion']);
        $this->aviso = $dictada
            ? "Confirmaste la clase del {$clase->fecha->format('d/m/Y')}."
            : "Observaste la clase del {$clase->fecha->format('d/m/Y')}. La coordinación del CILN revisará tu comentario.";
    }

    private function personaId(): int
    {
        return $this->personaId ??= app(FichaDelUsuario::class)((int) auth()->id())->personaId ?? abort(403);
    }
}
