<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    $this->persona = Persona::factory()->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam]);
    $this->concepto = Concepto::factory()->create();
    $this->tarifa = Tarifa::factory()->for($this->concepto)->create([
        'tipo_vinculo' => TipoVinculo::EstudianteUnasam,
        'monto' => '120.00',
        'vigente_desde' => '2027-01-01',
    ]);
    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '180.00']);
});

function registro(array $cambios = []): RegistroDePago
{
    $cambios = array_map(fn (mixed $valor): mixed => $valor instanceof Closure ? $valor() : $valor, $cambios);

    return new RegistroDePago(...[
        'personaId' => test()->persona->id,
        'conceptoId' => test()->concepto->id,
        'medioPago' => MedioPago::BancoNacion,
        'numeroOperacion' => ' op-2027-001 ',
        'fechaPago' => CarbonImmutable::parse('2027-02-20'),
        'monto' => '120.00',
        ...$cambios,
    ]);
}

function reciboPdf(string $nombre = 'recibo.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nombre, "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
}

it('registers a pending payment with its receipt and the fee that applied', function (UploadedFile $recibo, string $extension): void {
    $cajero = User::factory()->create();

    $pago = app(RegistrarPago::class)(registro(), $recibo, $cajero);

    expect($pago->refresh())
        ->estado->toBe(EstadoPago::Pendiente)
        ->persona_id->toBe($this->persona->id)
        ->tarifa_id->toBe($this->tarifa->id)
        ->monto->toBe('120.00')
        ->numero_operacion->toBe('OP-2027-001')
        ->registrado_por->toBe($cajero->id)
        ->and($pago->recibo_path)->toStartWith('recibos/2027/')->toEndWith(".{$extension}");
    Storage::disk('local')->assertExists($pago->recibo_path);
})->with([
    'pdf' => [fn (): UploadedFile => reciboPdf(), 'pdf'],
    'jpg' => [fn (): UploadedFile => UploadedFile::fake()->image('voucher.jpeg'), 'jpg'],
    'png' => [fn (): UploadedFile => UploadedFile::fake()->image('voucher.png'), 'png'],
]);

it('judges the receipt by its content, not by its name', function (): void {
    $recibo = UploadedFile::fake()->createWithContent('recibo.pdf', '<?php echo "no soy un recibo";');

    expect(fn () => app(RegistrarPago::class)(registro(), $recibo))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El recibo debe ser una imagen JPG o PNG, o un PDF.');
    expect(Pago::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('refuses a payment without a fee in force for the person on the payment date', function (array $cambios): void {
    expect(fn () => app(RegistrarPago::class)(registro($cambios), reciboPdf()))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No hay una tarifa vigente para este concepto en la fecha de pago.');
    expect(Pago::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'before the fee starts' => [['fechaPago' => CarbonImmutable::parse('2026-12-31')]],
    'link without a fee' => [['personaId' => fn (): int => Persona::factory()->create(['tipo_vinculo' => TipoVinculo::DocenteAdministrativoUnasam])->id]],
]);

it('refuses a concept that is no longer offered', function (): void {
    $this->concepto->update(['activo' => false]);

    expect(fn () => app(RegistrarPago::class)(registro(), reciboPdf()))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El concepto de pago no está disponible.');
});

it('refuses an operation number already registered and discards the uploaded receipt', function (): void {
    $primero = app(RegistrarPago::class)(registro(), reciboPdf());

    expect(fn () => app(RegistrarPago::class)(registro(['numeroOperacion' => 'OP-2027-001']), reciboPdf()))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Ese número de operación ya fue registrado con este medio de pago.');
    expect(Pago::query()->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toBe([$primero->recibo_path]);
});

it('keeps the fingerprint of the receipt file', function (): void {
    $recibo = reciboPdf();

    $pago = app(RegistrarPago::class)(registro(), $recibo);

    expect($pago->refresh()->recibo_sha256)->toBe(hash('sha256', "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n"));
});

it('refuses a receipt file already used by another payment and stores nothing', function (): void {
    app(RegistrarPago::class)(registro(), reciboPdf('primero.pdf'));
    $archivos = count(Storage::disk('local')->allFiles());

    expect(fn () => app(RegistrarPago::class)(registro(['numeroOperacion' => 'OTRA-OPERACION']), reciboPdf('copia.pdf')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Esta boleta ya fue registrada en otro pago.')
        ->and(Pago::query()->count())->toBe(1)
        ->and(count(Storage::disk('local')->allFiles()))->toBe($archivos);
});

it('accepts again the receipt of a rejected payment', function (): void {
    $rechazado = app(RegistrarPago::class)(registro(), reciboPdf());
    $rechazado->forceFill(['estado' => EstadoPago::Rechazado, 'validado_por' => User::factory()->create()->id, 'validado_en' => now(), 'motivo_rechazo' => 'Ilegible.'])->save();

    $nuevo = app(RegistrarPago::class)(registro(['numeroOperacion' => 'OTRA-OPERACION']), reciboPdf());

    expect($nuevo->refresh()->recibo_sha256)->toBe($rechazado->refresh()->recibo_sha256);
});
