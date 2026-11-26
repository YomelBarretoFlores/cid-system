<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Actions\SolicitarExamenDeClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Livewire\MensajesDeValidacion;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Examen de clasificación')]
final class ExamenDeClasificacion extends Component
{
    use MensajesDeValidacion {
        messages as mensajesComunes;
        validationAttributes as atributosComunes;
    }

    public string $idioma = '';

    public string $pago = '';

    private ?int $personaId = null;

    /** @var Collection<int, IdiomaDelCatalogo>|null */
    private ?Collection $catalogo = null;

    /** @var Collection<int, PagoValidado>|null */
    private ?Collection $pagosDisponibles = null;

    public function solicitar(SolicitarExamenDeClasificacion $solicitarExamen): void
    {
        $this->validate();

        try {
            $solicitarExamen($this->personaId(), (int) $this->idioma, (int) $this->pago);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('solicitud', $error->getMessage());
            $this->pagosDisponibles = null;

            return;
        }

        session()->flash('estado', 'Registramos tu solicitud. La coordinación académica te indicará la fecha del examen.');

        $this->redirectRoute('estudiante.clasificacion');
    }

    public function render(): View
    {
        return view('matricula::livewire.examen-de-clasificacion', [
            'puntajeMaximo' => app(ParametrosDelSistema::class)()->puntajeMaximoClasificacion,
            'solicitudes' => SolicitudClasificacion::query()
                ->where('persona_id', $this->personaId())
                ->latest()
                ->latest('id')
                ->get(),
            'catalogo' => $this->catalogo(),
            'opcionesDeIdioma' => $this->idiomasActivos()->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => $idioma->nombre])->all(),
            'opcionesDePago' => $this->pagosDisponibles()->mapWithKeys(fn (PagoValidado $pago): array => [
                $pago->id => "{$pago->concepto} · Operación {$pago->numeroOperacion} · S/ {$pago->monto} · {$pago->fechaPago->format('d/m/Y')}",
            ])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'idioma' => ['required', Rule::in($this->idiomasActivos()->keys()->map(strval(...))->all())],
            'pago' => ['required', Rule::in($this->pagosDisponibles()->pluck('id')->map(strval(...))->all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            ...$this->mensajesComunes(),
            'idioma.in' => 'Elige uno de los idiomas disponibles.',
            'pago.in' => 'Elige uno de tus pagos del examen de clasificación validados y sin usar.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            ...$this->atributosComunes(),
            'idioma' => 'idioma',
            'pago' => 'pago',
        ];
    }

    private function personaId(): int
    {
        return $this->personaId ??= app(FichaDelUsuario::class)((int) auth()->id())->personaId ?? abort(403);
    }

    /**
     * @return Collection<int, IdiomaDelCatalogo>
     */
    private function catalogo(): Collection
    {
        return $this->catalogo ??= collect(app(CatalogoDeIdiomas::class)())->keyBy('id');
    }

    /**
     * @return Collection<int, IdiomaDelCatalogo>
     */
    private function idiomasActivos(): Collection
    {
        return $this->catalogo()->where('activo', true);
    }

    /**
     * @return Collection<int, PagoValidado>
     */
    private function pagosDisponibles(): Collection
    {
        if ($this->pagosDisponibles instanceof Collection) {
            return $this->pagosDisponibles;
        }

        $pagos = collect(app(PagosValidadosDePersona::class)($this->personaId(), TipoConcepto::ExamenClasificacion));
        $usados = SolicitudClasificacion::query()->whereIn('pago_id', $pagos->pluck('id'))->pluck('pago_id');

        return $this->pagosDisponibles = $pagos->whereNotIn('id', $usados)->values();
    }
}
