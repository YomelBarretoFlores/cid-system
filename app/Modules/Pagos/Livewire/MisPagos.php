<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Livewire;

use App\Modules\Pagos\Actions\ListarPagosDeEstudiante;
use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Data\ReporteDeRecaudacion;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Schemas\PagoForm;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Livewire\MensajesDeValidacion;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.portal')]
#[Title('Registro de pagos')]
final class MisPagos extends Component
{
    use MensajesDeValidacion {
        messages as mensajesComunes;
        validationAttributes as atributosComunes;
    }
    use WithFileUploads;

    private const string ZONA_HORARIA = 'America/Lima';

    public string $concepto_id = '';

    public string $medio_pago_id = '';

    public string $numero_operacion = '';

    public string $fecha_pago = '';

    public string $monto = '';

    public ?TemporaryUploadedFile $recibo = null;

    public bool $registrando = false;

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
        $this->registrando = true;
        $this->monto = str_replace(',', '.', trim($this->monto));
        $this->validate();
        $recibo = $this->recibo ?? abort(422);

        try {
            $registrar($this->registro(), $recibo, auth()->user());
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('registro', $error->getMessage());

            return;
        }

        $this->registrando = false;
        session()->flash('estado', 'Registramos tu pago. El CILN revisará el recibo y te mostrará aquí el resultado.');
        $this->redirectRoute('estudiante.pagos');
    }

    public function cancelarRegistro(): void
    {
        $this->reset('concepto_id', 'medio_pago_id', 'numero_operacion', 'monto', 'recibo', 'registrando');
        $this->fecha_pago = $this->hoy();
        $this->resetValidation();
    }

    public function render(): View
    {
        $pagos = app(ListarPagosDeEstudiante::class)($this->ficha()->personaId);

        return view('pagos::livewire.mis-pagos', [
            'pagos' => $pagos,
            'resumen' => [
                'total' => $this->suma($pagos),
                'validados' => $this->suma($pagos->where('estado', EstadoPago::Validado)),
                'porValidar' => $this->suma($pagos->where('estado', EstadoPago::Pendiente)),
                'rechazados' => $pagos->where('estado', EstadoPago::Rechazado)->count(),
            ],
            'conceptos' => Concepto::query()->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all(),
            'mediosDePago' => MedioDePago::query()->activos()->pluck('nombre', 'id')->all(),
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
            'medio_pago_id' => ['required', 'integer', Rule::exists('pag_medios_pago', 'id')->where('activo', true)],
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
            'medio_pago_id.exists' => 'Elige un medio de pago de la lista.',
            'medio_pago_id.integer' => 'Elige un medio de pago de la lista.',
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
            'medio_pago_id' => 'medio de pago',
            'numero_operacion' => 'número de operación',
            'fecha_pago' => 'fecha de pago',
            'monto' => 'monto',
            'recibo' => 'recibo',
        ];
    }

    /**
     * @param  Collection<int, Pago>  $pagos
     */
    private function suma(Collection $pagos): string
    {
        return ReporteDeRecaudacion::soles($pagos->sum(fn (Pago $pago): int => ReporteDeRecaudacion::centimosDe($pago->monto)));
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
            medioPagoId: (int) $this->medio_pago_id,
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
