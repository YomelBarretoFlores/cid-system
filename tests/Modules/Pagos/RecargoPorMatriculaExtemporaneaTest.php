<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Pagos\Actions\GuardarConfiguracionDePagos;
use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Contracts\RecargoDeMatricula;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Pagos\Filament\Pages\EditarConfiguracionDePagos;
use App\Modules\Pagos\Livewire\MisPagos;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Audit\ContextoAuditoria;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function (): void {
    Event::fake([PagoValidado::class]);
    $this->travelTo(CarbonImmutable::parse('2027-03-08 10:00', 'America/Lima'));
    $this->administrador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Configurar->value]);
    $this->cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Validar->value]);
    Periodo::factory()->create([
        'estado' => EstadoPeriodo::MatriculaAbierta, 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-03-31',
        'matricula_inicio' => '2027-02-15', 'matricula_fin' => '2027-03-05', 'matricula_extemporanea_fin' => '2027-03-12',
    ]);
    $this->matricula = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula, 'nombre' => 'Matrícula']);
});

function guardarRecargo(mixed $porcentaje, ?User $usuario = null): void
{
    app(GuardarConfiguracionDePagos::class)(['recargo_matricula_extemporanea' => $porcentaje], $usuario ?? test()->administrador);
}

function pagoDeMatricula(string $monto, string $tarifa, string $fecha = '2027-03-08', ?Concepto $concepto = null): Pago
{
    $tarifa = Tarifa::factory()->for($concepto ?? test()->matricula)->create(['monto' => $tarifa, 'vigente_desde' => '2027-01-01']);

    return Pago::factory()->create(['concepto_id' => $tarifa->concepto_id, 'tarifa_id' => $tarifa->id, 'monto' => $monto, 'fecha_pago' => $fecha]);
}

it('applies a late enrollment surcharge of 20 % until the CILN changes it, and keeps each change audited', function (): void {
    expect(app(RecargoDeMatricula::class)(CarbonImmutable::parse('2027-03-08')))->toBe(20);

    app(ContextoAuditoria::class)->establecer($this->administrador->id, '10.0.0.9');
    guardarRecargo('15');

    expect(app(RecargoDeMatricula::class)(CarbonImmutable::parse('2027-03-08')))->toBe(15)
        ->and(DB::table('aud_registros')->where(['tabla' => 'pag_configuracion', 'operacion' => 'UPDATE', 'usuario_id' => $this->administrador->id])->count())->toBe(1);
});

it('gives no surcharge outside the late window, nor when the CILN sets it to 0 %', function (): void {
    expect(app(RecargoDeMatricula::class)(CarbonImmutable::parse('2027-03-05')))->toBeNull()
        ->and(app(RecargoDeMatricula::class)(CarbonImmutable::parse('2027-03-13')))->toBeNull();

    guardarRecargo(0);

    expect(app(RecargoDeMatricula::class)(CarbonImmutable::parse('2027-03-08')))->toBeNull();
});

it('accepts only a whole percentage from 0 to 100', function (mixed $porcentaje): void {
    expect(fn () => guardarRecargo($porcentaje))->toThrow(ReglaDeNegocioIncumplida::class, 'El recargo por matrícula extemporánea debe ser un porcentaje entero de 0 a 100.')
        ->and(app(RecargoDeMatricula::class)(CarbonImmutable::parse('2027-03-08')))->toBe(20);
})->with(['negative' => [-1], 'above 100' => [101], 'decimal' => ['12.5'], 'text' => ['veinte'], 'empty' => [null]]);

it('lets only users with the configuration permission change the surcharge', function (): void {
    expect(fn () => guardarRecargo(30, $this->cajero))->toThrow(AuthorizationException::class);

    $this->actingAs($this->cajero)->get(EditarConfiguracionDePagos::getUrl())->assertForbidden();
});

it('edits the surcharge from the payment settings page', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(EditarConfiguracionDePagos::class)
        ->assertSchemaStateSet(['recargo_matricula_extemporanea' => 20])
        ->assertSee('Recargo por matrícula extemporánea (%)')
        ->fillForm(['recargo_matricula_extemporanea' => 25])
        ->call('guardar')
        ->assertHasNoFormErrors();

    expect(app(RecargoDeMatricula::class)(CarbonImmutable::parse('2027-03-08')))->toBe(25);
});

it('expects the fee plus the surcharge, rounded to cents, for an enrollment paid in the late window', function (string $tarifa, int $porcentaje, string $esperado): void {
    guardarRecargo($porcentaje);
    $pago = pagoDeMatricula($esperado, $tarifa);

    app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true);

    expect($pago->refresh())->estado->toBe(EstadoPago::Validado)->justificacion_monto->toBeNull()->tarifa_id->not->toBeNull()
        ->and($pago->tarifa?->monto)->toBe($tarifa);
})->with([
    '10.00 plus 20 %' => ['10.00', 20, '12.00'],
    '33.33 plus 15 %' => ['33.33', 15, '38.33'],
    '0.05 plus 50 % rounds half up' => ['0.05', 50, '0.08'],
]);

it('asks for a justification to validate only the plain fee in the late window', function (): void {
    $pago = pagoDeMatricula('10.00', '10.00');

    expect(fn () => app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El monto pagado (S/ 10.00) no coincide con la tarifa más el recargo de 20 % por matrícula extemporánea (S/ 12.00). Escribe la justificación para validarlo.');
});

it('keeps expecting the plain fee for an enrollment paid in the regular window or for other concepts', function (string $fecha, TipoConcepto $tipo): void {
    $pago = pagoDeMatricula('10.00', '10.00', $fecha, Concepto::factory()->create(['tipo' => $tipo]));

    app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true);

    expect($pago->refresh())->estado->toBe(EstadoPago::Validado)->justificacion_monto->toBeNull();
})->with([
    'enrollment in the regular window' => ['2027-03-04', TipoConcepto::Matricula],
    'pension in the late window' => ['2027-03-08', TipoConcepto::Pension],
]);

it('proposes the fee with the surcharge in the student portal and says so', function (): void {
    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id, 'tipo_vinculo' => TipoVinculo::EstudianteUnasam]);
    Tarifa::factory()->for($this->matricula)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '10.00', 'vigente_desde' => '2027-01-01']);

    Livewire::actingAs($estudiante)
        ->test(MisPagos::class)
        ->set('registrando', true)
        ->set('concepto_id', (string) $this->matricula->id)
        ->assertSet('monto', '12.00')
        ->assertSee('Incluye el recargo de 20 % por matrícula extemporánea.')
        ->set('fecha_pago', '2027-03-04')
        ->assertSet('monto', '10.00')
        ->assertDontSee('Incluye el recargo');
});

it('keeps the amount the student typed when the payment date changes', function (): void {
    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id, 'tipo_vinculo' => TipoVinculo::EstudianteUnasam]);
    Tarifa::factory()->for($this->matricula)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '10.00', 'vigente_desde' => '2027-01-01']);

    Livewire::actingAs($estudiante)
        ->test(MisPagos::class)
        ->set('registrando', true)
        ->set('concepto_id', (string) $this->matricula->id)
        ->set('monto', '11.50')
        ->set('fecha_pago', '2027-03-04')
        ->assertSet('monto', '11.50')
        ->set('monto', '')
        ->set('fecha_pago', '2027-03-08')
        ->assertSet('monto', '12.00');
});
