<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Livewire\MisPagos;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    $this->estudiante = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->estudiante->id, 'tipo_vinculo' => TipoVinculo::EstudianteUnasam]);
    $this->concepto = Concepto::factory()->create(['nombre' => 'Derecho de matrícula']);
    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '120.00', 'vigente_desde' => '2020-01-01', 'vigente_hasta' => '2020-12-31']);
    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '150.00', 'vigente_desde' => '2021-01-01']);
    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '180.00', 'vigente_desde' => '2021-01-01']);
});

function reciboDelPortal(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('voucher.pdf', "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
}

/**
 * @param  array<string, mixed>  $cambios
 */
function registrarPagoEnElPortal(array $cambios = []): Testable
{
    $datos = [
        'concepto_id' => (string) test()->concepto->id,
        'medio_pago' => MedioPago::BancoNacion->value,
        'numero_operacion' => ' op-2027-001 ',
        'fecha_pago' => CarbonImmutable::today('America/Lima')->toDateString(),
        'monto' => '150.00',
        'recibo' => reciboDelPortal(),
        ...$cambios,
    ];

    $componente = Livewire::actingAs(test()->estudiante)->test(MisPagos::class);

    foreach ($datos as $campo => $valor) {
        $componente->set($campo, $valor);
    }

    return $componente->call('registrar');
}

it('lists only the payments of the signed-in student, with their state and rejection reason', function (): void {
    Pago::factory()->validado()->create(['persona_id' => $this->persona->id, 'concepto_id' => $this->concepto->id, 'numero_operacion' => 'OP-PROPIO-1', 'monto' => '150.00', 'fecha_pago' => '2027-02-20']);
    Pago::factory()->rechazado()->create(['persona_id' => $this->persona->id, 'concepto_id' => $this->concepto->id, 'numero_operacion' => 'OP-PROPIO-2', 'fecha_pago' => '2027-02-21', 'motivo_rechazo' => 'El monto no coincide con el recibo.']);
    Pago::factory()->create(['numero_operacion' => 'OP-AJENO-9']);

    $this->actingAs($this->estudiante)
        ->get(route('estudiante.pagos'))
        ->assertOk()
        ->assertSee('Mis pagos')
        ->assertSeeInOrder(['OP-PROPIO-2', 'OP-PROPIO-1'])
        ->assertSee('Derecho de matrícula')
        ->assertSee('Banco de la Nación')
        ->assertSee('20/02/2027')
        ->assertSee('S/ 150.00')
        ->assertSee(EstadoPago::Validado->etiqueta())
        ->assertSee(EstadoPago::Rechazado->etiqueta())
        ->assertSee('El monto no coincide con el recibo.')
        ->assertDontSee('OP-AJENO-9');
});

it('registers a pending payment of the student with the stored receipt and the student as registrant', function (): void {
    registrarPagoEnElPortal()
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.pagos'));

    $pago = Pago::query()->sole();
    expect($pago)
        ->persona_id->toBe($this->persona->id)
        ->concepto_id->toBe($this->concepto->id)
        ->estado->toBe(EstadoPago::Pendiente)
        ->numero_operacion->toBe('OP-2027-001')
        ->monto->toBe('150.00')
        ->registrado_por->toBe($this->estudiante->id)
        ->and($pago->recibo_path)->toStartWith('recibos/'.CarbonImmutable::today('America/Lima')->format('Y').'/')->toEndWith('.pdf');
    Storage::disk('local')->assertExists($pago->recibo_path);
    expect(session('estado'))->toBe('Registramos tu pago. El CILN revisará el recibo y te mostrará aquí el resultado.');
});

it('refuses a receipt that is not an image or a PDF with the business message', function (): void {
    registrarPagoEnElPortal(['recibo' => UploadedFile::fake()->createWithContent('recibo.pdf', '<?php echo "no soy un recibo";')])
        ->assertHasErrors(['registro' => 'El recibo debe ser una imagen JPG o PNG, o un PDF.'])
        ->assertSee('El recibo debe ser una imagen JPG o PNG, o un PDF.')
        ->assertNoRedirect();

    expect(Pago::query()->count())->toBe(0);
});

it('refuses a payment date after today in Lima', function (): void {
    // Late evening in Lima is already the next day in UTC.
    $this->travelTo(CarbonImmutable::parse('2027-03-10 22:00', 'America/Lima'));

    registrarPagoEnElPortal(['fecha_pago' => '2027-03-11', 'recibo' => null])
        ->assertHasErrors(['fecha_pago' => 'La fecha de pago no puede ser posterior a hoy.']);
    registrarPagoEnElPortal(['fecha_pago' => '2027-03-10', 'recibo' => null])
        ->assertHasNoErrors('fecha_pago');
});

it('validates the form with Spanish messages', function (array $cambios, string $campo, string $mensaje): void {
    registrarPagoEnElPortal($cambios)->assertHasErrors([$campo => $mensaje]);

    expect(Pago::query()->count())->toBe(0);
})->with([
    'no concept' => [['concepto_id' => ''], 'concepto_id', 'Completa el campo concepto.'],
    'no operation number' => [['numero_operacion' => ''], 'numero_operacion', 'Completa el campo número de operación.'],
    'amount not in soles' => [['monto' => 'ciento cincuenta'], 'monto', 'Escribe el monto en soles, por ejemplo 150.00.'],
    'zero amount' => [['monto' => '0'], 'monto', 'El monto debe ser mayor que cero.'],
    'no receipt' => [['recibo' => null], 'recibo', 'Adjunta el recibo escaneado.'],
    'receipt too large' => [['recibo' => UploadedFile::fake()->create('recibo.pdf', 5121, 'application/pdf')], 'recibo', 'El recibo no debe pesar más de 5 MB.'],
]);

it('proposes the fee in force today for the student link type when a concept is chosen', function (): void {
    Livewire::actingAs($this->estudiante)
        ->test(MisPagos::class)
        ->assertSet('monto', '')
        ->set('concepto_id', (string) $this->concepto->id)
        ->assertSet('monto', '150.00')
        ->set('concepto_id', (string) Concepto::factory()->create()->id)
        ->assertSet('monto', '');
});

it('sends guests to the portal sign-in', function (): void {
    $this->get(route('estudiante.pagos'))->assertRedirect(route('estudiante.ingresar'));
});

it('refuses the page to an account without a student record', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('estudiante.pagos'))
        ->assertForbidden();
});
