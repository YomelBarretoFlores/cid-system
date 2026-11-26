<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Programa;
use App\Modules\Pagos\Actions\ListarTarifasVigentes;
use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Actions\RegistrarTarifa;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Livewire\MisPagos;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    $this->persona = Persona::factory()->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral]);
    $this->concepto = Concepto::factory()->create(['nombre' => 'Certificado de estudios']);
    $this->posgrado = (int) Programa::query()->where('codigo', 'POSGRADO')->value('id');
});

function tarifaDe(Concepto $concepto, string $monto, ?NivelCurricular $nivel = null, ?int $programaId = null): Tarifa
{
    return Tarifa::factory()->for($concepto)->create(['monto' => $monto, 'vigente_desde' => '2027-01-01', 'nivel' => $nivel, 'programa_id' => $programaId]);
}

function pagoPorNivel(?NivelCurricular $nivel = null, ?int $programaId = null, string $numero = 'OP-1'): Pago
{
    return app(RegistrarPago::class)(new RegistroDePago(
        personaId: test()->persona->id,
        conceptoId: test()->concepto->id,
        medioPagoId: MedioDePago::idDe('banco_nacion'),
        numeroOperacion: $numero,
        fechaPago: CarbonImmutable::parse('2027-02-20'),
        monto: '50.00',
        nivel: $nivel,
        programaId: $programaId,
    ), UploadedFile::fake()->createWithContent("{$numero}.pdf", "%PDF-1.4\n% {$numero}\n%%EOF\n"));
}

it('applies the most specific fee in force: program and level, then level, then program, then general', function (): void {
    $general = tarifaDe($this->concepto, '40.00');
    $deNivel = tarifaDe($this->concepto, '50.00', NivelCurricular::Intermedio);
    $dePrograma = tarifaDe($this->concepto, '90.00', programaId: $this->posgrado);
    $deProgramaYNivel = tarifaDe($this->concepto, '110.00', NivelCurricular::Intermedio, $this->posgrado);

    expect(pagoPorNivel(NivelCurricular::Intermedio, $this->posgrado, 'OP-1')->tarifa_id)->toBe($deProgramaYNivel->id)
        ->and(pagoPorNivel(NivelCurricular::Intermedio, null, 'OP-2')->tarifa_id)->toBe($deNivel->id)
        ->and(pagoPorNivel(NivelCurricular::Avanzado, $this->posgrado, 'OP-3')->tarifa_id)->toBe($dePrograma->id)
        ->and(pagoPorNivel(NivelCurricular::Avanzado, null, 'OP-4')->tarifa_id)->toBe($general->id);
});

it('asks for the level and the program only when the fees of the concept vary by them', function (): void {
    tarifaDe($this->concepto, '50.00', NivelCurricular::Basico);
    tarifaDe($this->concepto, '60.00', NivelCurricular::Intermedio);

    expect(fn (): Pago => pagoPorNivel())->toThrow(ReglaDeNegocioIncumplida::class, 'Indica el nivel al que corresponde el pago.');

    $pago = pagoPorNivel(NivelCurricular::Intermedio, $this->posgrado);

    expect($pago->refresh()->tarifa_id)->toBe(Tarifa::query()->where('nivel', 'intermedio')->value('id'))
        ->and(Pago::query()->count())->toBe(1);
});

it('keeps registering a concept with a single general fee without level or program', function (): void {
    $general = tarifaDe($this->concepto, '40.00');

    expect(pagoPorNivel()->tarifa_id)->toBe($general->id);
});

it('rejects overlapping fees for the same concept, link, level and program, but allows another level or program', function (): void {
    tarifaDe($this->concepto, '50.00', NivelCurricular::Basico, $this->posgrado);

    expect(fn () => DB::transaction(fn (): Tarifa => tarifaDe($this->concepto, '55.00', NivelCurricular::Basico, $this->posgrado)))
        ->toThrow(QueryException::class, 'pag_tarifas_sin_superposicion');

    $otroNivel = tarifaDe($this->concepto, '60.00', NivelCurricular::Intermedio, $this->posgrado);
    $general = tarifaDe($this->concepto, '45.00', NivelCurricular::Basico);

    expect($otroNivel->exists)->toBeTrue()->and($general->exists)->toBeTrue();
});

it('opens a new fee for a level without closing the open fee of another level', function (): void {
    $basico = app(RegistrarTarifa::class)($this->concepto->id, TipoVinculo::PublicoGeneral, '20.00', CarbonImmutable::parse('2027-01-01'), nivel: NivelCurricular::Basico);
    app(RegistrarTarifa::class)($this->concepto->id, TipoVinculo::PublicoGeneral, '30.00', CarbonImmutable::parse('2027-03-01'), nivel: NivelCurricular::Intermedio);
    app(RegistrarTarifa::class)($this->concepto->id, TipoVinculo::PublicoGeneral, '25.00', CarbonImmutable::parse('2027-06-01'), nivel: NivelCurricular::Basico);

    expect($basico->refresh()->vigente_hasta?->toDateString())->toBe('2027-05-31')
        ->and(Tarifa::query()->where('nivel', 'intermedio')->value('vigente_hasta'))->toBeNull();
});

it('freezes the level and program of a fee once a payment used it', function (string $columna, mixed $valor): void {
    tarifaDe($this->concepto, '50.00', NivelCurricular::Basico);
    pagoPorNivel(NivelCurricular::Basico);
    $tarifa = Tarifa::query()->sole();

    expect(fn () => DB::table('pag_tarifas')->where('id', $tarifa->id)->update([$columna => $valor === 'posgrado' ? $this->posgrado : $valor]))
        ->toThrow(QueryException::class, 'una tarifa aplicada a pagos');
})->with([
    'level' => ['nivel', 'avanzado'],
    'program' => ['programa_id', 'posgrado'],
]);

it('lists the level and program of the fees in force', function (): void {
    tarifaDe($this->concepto, '100.00', NivelCurricular::Basico, $this->posgrado);

    $tarifa = app(ListarTarifasVigentes::class)(CarbonImmutable::parse('2027-02-01'))[0];

    expect($tarifa->nivel)->toBe(NivelCurricular::Basico)->and($tarifa->programa)->toBe('Posgrado');
});

it('shows the level field in the student portal only for concepts with level fees, and prefills that fee', function (): void {
    $estudiante = User::factory()->create();
    $this->persona->update(['user_id' => $estudiante->id]);
    $porNivel = Concepto::factory()->create(['nombre' => 'Constancia de notas']);
    Tarifa::factory()->for($porNivel)->create(['monto' => '20.00', 'nivel' => 'basico', 'vigente_desde' => '2020-01-01']);
    Tarifa::factory()->for($porNivel)->create(['monto' => '25.00', 'nivel' => 'avanzado', 'vigente_desde' => '2020-01-01']);
    $general = Concepto::factory()->create(['nombre' => 'Pensión']);
    Tarifa::factory()->for($general)->create(['monto' => '100.00', 'vigente_desde' => '2020-01-01']);

    $componente = Livewire::actingAs($estudiante)->test(MisPagos::class)
        ->set('registrando', true)
        ->set('concepto_id', (string) $general->id)
        ->assertSet('monto', '100.00')
        ->assertDontSee('Nivel que pagaste')
        ->set('concepto_id', (string) $porNivel->id)
        ->assertSet('monto', '')
        ->assertSee('Nivel que pagaste')
        ->set('nivel', 'avanzado')
        ->assertSet('monto', '25.00');

    $componente->set('medio_pago_id', (string) MedioDePago::idDe('banco_nacion'))
        ->set('numero_operacion', 'OP-77')
        ->set('fecha_pago', CarbonImmutable::today('America/Lima')->toDateString())
        ->set('recibo', UploadedFile::fake()->createWithContent('v.pdf', "%PDF-1.4\n%%EOF\n"))
        ->call('registrar')
        ->assertHasNoErrors();

    expect(Pago::query()->sole()->tarifa_id)->toBe(Tarifa::query()->where(['concepto_id' => $porNivel->id, 'nivel' => 'avanzado'])->value('id'));
});
