<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Livewire;

use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Schemas\PagoForm;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Livewire\MensajesDeValidacion;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.portal')]
#[Title('Mis pagos')]
final class MisPagos extends Component
{
    use MensajesDeValidacion {
        messages as mensajesComunes;
        validationAttributes as atributosComunes;
    }
    use WithFileUploads;

    private const string ZONA_HORARIA = 'America/Lima';

    public string $concepto_id = '';

    public string $medio_pago = '';

    public string $numero_operacion = '';

    public string $fecha_pago = '';

    public string $monto = '';

    public ?TemporaryUploadedFile $recibo = null;

    private ?FichaDeEstudiante $ficha = null;

    public function mount(): void
    {
        $this->fecha_pago = $this->hoy();
    }

    public function updatedConceptoId(): void
    {
        $this->monto = ctype_digit($this->concepto_id) ? $this->tarifaVigente((int) $this->concepto_id) : '';
    }

    public function registrar(RegistrarPago $registrar): void
    {
        $this->monto = str_replace(',', '.', trim($this->monto));
        $this->validate();
        $recibo = $this->recibo ?? abort(422);

        try {
            $registrar($this->registro(), $recibo, auth()->user());
        } catch (ReglaDeNegocioIncumplida $error) {
            $this->addError('registro', $error->getMessage());

            return;
        }

        session()->flash('estado', 'Registramos tu pago. El CILN revisará el recibo y te mostrará aquí el resultado.');
        $this->redirectRoute('estudiante.pagos');
    }

    public function render(): View
    {
        return view('pagos::livewire.mis-pagos', [
            'pagos' => Pago::query()
                ->with('concepto:id,nombre')
                ->where('persona_id', $this->ficha()->personaId)
                ->orderByDesc('fecha_pago')
                ->orderByDesc('id')
                ->get(),
            'conceptos' => Concepto::query()->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all(),
            'mediosDePago' => collect(MedioPago::cases())->mapWithKeys(fn (MedioPago $medio): array => [$medio->value => $medio->etiqueta()])->all(),
            'hoy' => $this->hoy(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'concepto_id' => ['required', 'integer'],
            'medio_pago' => ['required', Rule::enum(MedioPago::class)],
            'numero_operacion' => ['required', 'string', 'max:40'],
            'fecha_pago' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$this->hoy()],
            'monto' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'recibo' => ['required', 'file', 'max:'.PagoForm::RECIBO_MAXIMO_KB],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        $montoInvalido = 'Escribe el monto en soles, por ejemplo 150.00.';

        return [
            ...$this->mensajesComunes(),
            'concepto_id.integer' => 'Elige un concepto de la lista.',
            'medio_pago.enum' => 'Elige un medio de pago de la lista.',
            'fecha_pago.date_format' => 'Escribe una fecha de pago válida.',
            'fecha_pago.before_or_equal' => 'La fecha de pago no puede ser posterior a hoy.',
            'monto.numeric' => $montoInvalido,
            'monto.decimal' => $montoInvalido,
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.max' => 'Revisa el monto: es demasiado alto.',
            'recibo.required' => 'Adjunta el recibo escaneado.',
            'recibo.file' => 'Adjunta el recibo escaneado.',
            'recibo.max' => 'El recibo no debe pesar más de 5 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            ...$this->atributosComunes(),
            'concepto_id' => 'concepto',
            'medio_pago' => 'medio de pago',
            'numero_operacion' => 'número de operación',
            'fecha_pago' => 'fecha de pago',
            'monto' => 'monto',
            'recibo' => 'recibo',
        ];
    }

    private function tarifaVigente(int $conceptoId): string
    {
        $monto = Tarifa::query()
            ->where('concepto_id', $conceptoId)
            ->where('tipo_vinculo', app(ConsultaDeVinculo::class)($this->ficha()->personaId))
            ->vigenteEn(CarbonImmutable::today(self::ZONA_HORARIA))
            ->value('monto');

        return is_string($monto) ? $monto : '';
    }

    private function registro(): RegistroDePago
    {
        return new RegistroDePago(
            personaId: $this->ficha()->personaId,
            conceptoId: (int) $this->concepto_id,
            medioPago: MedioPago::from($this->medio_pago),
            numeroOperacion: $this->numero_operacion,
            fechaPago: CarbonImmutable::parse($this->fecha_pago),
            monto: $this->monto,
        );
    }

    private function ficha(): FichaDeEstudiante
    {
        return $this->ficha ??= app(FichaDelUsuario::class)((int) auth()->id()) ?? abort(403);
    }

    private function hoy(): string
    {
        return CarbonImmutable::today(self::ZONA_HORARIA)->toDateString();
    }
}
