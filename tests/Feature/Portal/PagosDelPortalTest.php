<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Livewire\MisPagos;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    $this->alumno = User::factory()->create();
    $this->ficha = Persona::factory()->create(['user_id' => $this->alumno->id, 'tipo_vinculo' => 'publico_general']);
    $this->otro = User::factory()->create();
    $this->fichaOtra = Persona::factory()->create(['user_id' => $this->otro->id]);
    $this->conceptoSonda = Concepto::factory()->create(['nombre' => 'Pensión']);
    Tarifa::factory()->for($this->conceptoSonda)->create(['tipo_vinculo' => 'publico_general', 'monto' => '180.00', 'vigente_desde' => '2021-01-01']);
    Tarifa::factory()->for($this->conceptoSonda)->create(['tipo_vinculo' => 'estudiante_unasam', 'monto' => '150.00', 'vigente_desde' => '2021-01-01']);
});

/**
 * @param  array<string, mixed>  $cambios
 */
function sondaRegistrarPago(array $cambios = []): Testable
{
    $componente = Livewire::actingAs(test()->alumno)->test(MisPagos::class);

    foreach ([
        'concepto_id' => (string) test()->conceptoSonda->id, 'medio_pago_id' => (string) MedioDePago::idDe('banco_nacion'),
        'numero_operacion' => 'OP-SONDA-1', 'fecha_pago' => CarbonImmutable::today('America/Lima')->toDateString(),
        'monto' => '180.00', 'recibo' => UploadedFile::fake()->createWithContent('recibo.pdf', "%PDF-1.4\n%%EOF\n"), ...$cambios,
    ] as $campo => $valor) {
        $componente->set($campo, $valor);
    }

    return $componente->call('registrar');
}

it('never lists nor serves another student payment or receipt', function (): void {
    $ajeno = Pago::factory()->rechazado()->create(['persona_id' => $this->fichaOtra->id, 'numero_operacion' => 'OP-AJENO-9', 'recibo_path' => 'recibos/2027/ajeno.pdf']);
    Storage::disk('local')->put('recibos/2027/ajeno.pdf', '%PDF ajeno');

    $this->actingAs($this->alumno)->get(route('estudiante.pagos'))->assertOk()->assertDontSee('OP-AJENO-9')->assertDontSee($ajeno->ulid);
    $this->actingAs($this->alumno)->get(route('estudiante.pagos.recibo', $ajeno))->assertNotFound();
    $this->actingAs($this->otro)->get(route('estudiante.pagos.recibo', $ajeno))->assertOk();
});

it('refuses a PHP file renamed to .pdf and stores nothing', function (): void {
    sondaRegistrarPago(['recibo' => UploadedFile::fake()->createWithContent('recibo.pdf', '<?php system($_GET["c"]);')])
        ->assertHasErrors(['registro' => 'El recibo debe ser una imagen JPG o PNG, o un PDF.']);

    expect(Pago::query()->count())->toBe(0)->and(Storage::disk('local')->allFiles('recibos'))->toBe([]);
});

it('refuses a future payment date and creates nothing', function (): void {
    sondaRegistrarPago(['fecha_pago' => CarbonImmutable::today('America/Lima')->addDay()->toDateString()])
        ->assertHasErrors(['fecha_pago' => 'La fecha de pago no puede ser posterior a hoy.']);

    expect(Pago::query()->count())->toBe(0);
});

it('proposes the current fee for the student link type', function (): void {
    Livewire::actingAs($this->alumno)->test(MisPagos::class)
        ->set('concepto_id', (string) $this->conceptoSonda->id)
        ->assertSet('monto', '180.00');
});

it('registers the payment as pending, owned by and registered by the student', function (): void {
    sondaRegistrarPago()->assertHasNoErrors()->assertRedirect(route('estudiante.pagos'));

    expect(Pago::query()->sole())
        ->persona_id->toBe($this->ficha->id)
        ->registrado_por->toBe($this->alumno->id)
        ->estado->toBe(EstadoPago::Pendiente);
});

it('refuses a duplicated operation number and keeps one payment', function (): void {
    sondaRegistrarPago()->assertHasNoErrors();
    sondaRegistrarPago()->assertHasErrors('registro');

    expect(Pago::query()->count())->toBe(1)->and(Storage::disk('local')->allFiles('recibos'))->toHaveCount(1);
});
