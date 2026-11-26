<?php

declare(strict_types=1);

namespace App\Modules\Personas\Livewire;

use App\Modules\Personas\Actions\CambiarFotoDePerfil;
use App\Modules\Personas\Actions\QuitarFotoDePerfil;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Personas\Livewire\Concerns\LimitaIntentos;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Parametros\Data\ValoresDeParametros;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.portal')]
#[Title('Subir fotografía')]
final class SubirFotografia extends Component
{
    use LimitaIntentos;
    use WithFileUploads;

    private const int FOTOS_POR_HORA = 10;

    public ?TemporaryUploadedFile $foto = null;

    public function updatedFoto(): void
    {
        $this->validateOnly('foto');
    }

    public function guardar(CambiarFotoDePerfil $cambiar): void
    {
        $this->limitarIntentos('foto-de-perfil|'.auth()->id(), self::FOTOS_POR_HORA, 3600, 'foto');
        $this->validate();
        $foto = $this->foto ?? abort(422);

        try {
            $cambiar($this->ficha()->personaId, $foto);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('foto', $error->getMessage());

            return;
        }

        session()->flash('estado', 'Guardamos tu fotografía.');
        $this->redirectRoute('estudiante.perfil.foto');
    }

    public function quitar(QuitarFotoDePerfil $quitar): void
    {
        $quitar($this->ficha()->personaId);

        session()->flash('estado', 'Quitamos tu fotografía.');
        $this->redirectRoute('estudiante.perfil.foto');
    }

    public function render(): View
    {
        return view('personas::livewire.subir-fotografia', ['ficha' => $this->ficha(), 'parametros' => $this->parametros()]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $parametros = $this->parametros();
        [$minimo, $maximo] = [$parametros->fotoLadoMinimoPx, $parametros->fotoLadoMaximoPx];

        return [
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:'.$parametros->fotoMaximoKb(), "dimensions:min_width={$minimo},min_height={$minimo},max_width={$maximo},max_height={$maximo}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'foto.required' => 'Elige la fotografía que quieres subir.',
            ...array_fill_keys(['foto.image', 'foto.mimes', 'foto.max', 'foto.dimensions', 'foto.uploaded'], "La fotografía debe ser una imagen {$this->requisitosDelArchivo()}."),
        ];
    }

    // "JPG o PNG de hasta 2 MB, entre 200 y 3000 píxeles por lado", from «Parámetros del sistema».
    private function requisitosDelArchivo(): string
    {
        $parametros = $this->parametros();

        return "JPG o PNG de hasta {$parametros->fotoMaximoMb} MB, entre {$parametros->fotoLadoMinimoPx} y {$parametros->fotoLadoMaximoPx} píxeles por lado";
    }

    private function parametros(): ValoresDeParametros
    {
        return app(ParametrosDelSistema::class)();
    }

    private function ficha(): FichaDeEstudiante
    {
        return app(FichaDelUsuario::class)((int) auth()->id()) ?? abort(403);
    }
}
