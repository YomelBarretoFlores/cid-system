<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\MatricularEnVentanilla;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Events\EstudianteMatriculado;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ListMatriculas;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Actions\GuardarConfiguracionDePagos;
use App\Modules\Pagos\Data\PagoAnteriorAlSistema;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\ViewPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Radio;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    Event::fake([EstudianteMatriculado::class]);
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoMatricula::valores(), PermisoPago::Registrar->value]);
    $this->lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoMatricula::Ver->value]);

    $idioma = Idioma::factory()->create();
    $this->ciclo1 = Ciclo::factory()->for($idioma)->create(['numero' => 1]);
    $this->ciclo2 = Ciclo::factory()->for($idioma)->create(['numero' => 2]);
    $this->periodo = Periodo::factory()->conMatriculaAbierta()->create();
    $this->grupo = Grupo::factory()->for($this->periodo)->for($this->ciclo1)->create(['codigo' => 'ING-I-V', 'cupo_maximo' => 20]);
    $this->persona = Persona::factory()->create(['tipo_vinculo' => 'estudiante_unasam', 'habilitada_en' => null]);
    $this->concepto = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula, 'nombre' => 'Matrícula de idiomas']);
    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => 'estudiante_unasam', 'monto' => '120.00', 'vigente_desde' => '2026-01-01']);
    $this->fechaPago = CarbonImmutable::today('America/Lima')->subDays(2);
});

function datosDelPagoAnterior(array $cambios = []): array
{
    return [
        'persona' => ['tipo_documento' => test()->persona->tipo_documento, 'numero_documento' => test()->persona->numero_documento],
        'grupo_id' => test()->grupo->id,
        'modo_de_pago' => 'anterior',
        'concepto_id' => test()->concepto->id,
        'medio_pago_id' => MedioDePago::idDe('banco_nacion'),
        'numero_operacion' => 'nov-2026-015',
        'fecha_pago' => test()->fechaPago->toDateString(),
        'monto' => '120.00',
        'motivo_anterior' => 'Pago de noviembre de 2026, anterior al sistema',
        ...$cambios,
    ];
}

function pagoAnteriorDeVentanilla(?int $personaId = null): PagoAnteriorAlSistema
{
    return new PagoAnteriorAlSistema(new RegistroDePago(
        personaId: $personaId ?? test()->persona->id,
        conceptoId: test()->concepto->id,
        medioPagoId: MedioDePago::idDe('banco_nacion'),
        numeroOperacion: 'NOV-2026-020',
        fechaPago: test()->fechaPago,
        monto: '120.00',
    ), 'Pago de noviembre de 2026, anterior al sistema', UploadedFile::fake()->createWithContent('recibo.pdf', "%PDF-1.4\n% ventanilla\n%%EOF\n"));
}

function apagarPagosAnteriores(): void
{
    app(GuardarConfiguracionDePagos::class)(
        ['recargo_matricula_extemporanea' => 20, 'permite_pagos_anteriores_al_sistema' => false],
        User::factory()->create()->givePermissionTo(PermisoPago::Configurar->value),
    );
}

it('enrolls a student at the office with a validated payment of the student', function (): void {
    $pago = Pago::factory()->validado()->for($this->concepto)->create(['persona_id' => $this->persona->id]);
    $this->actingAs($this->gestor);

    Livewire::test(ListMatriculas::class)
        ->callAction('matricularEnVentanilla', data: [
            'persona' => ['tipo_documento' => $this->persona->tipo_documento, 'numero_documento' => $this->persona->numero_documento],
            'grupo_id' => $this->grupo->id,
            'modo_de_pago' => 'validado',
            'pago_id' => $pago->id,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('Matrícula registrada');

    $matricula = Matricula::query()->sole();
    expect($matricula)
        ->persona_id->toBe($this->persona->id)
        ->grupo_id->toBe($this->grupo->id)
        ->pago_id->toBe($pago->id)
        ->and($this->grupo->refresh()->matriculados)->toBe(1);
    Event::assertDispatched(EstudianteMatriculado::class, fn (EstudianteMatriculado $evento): bool => $evento->matriculaId === $matricula->id);
});

it('enrolls a student with a payment made before the system, without the receipt file', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListMatriculas::class)
        ->callAction('matricularEnVentanilla', data: datosDelPagoAnterior())
        ->assertHasNoFormErrors()
        ->assertNotified('Matrícula registrada');

    $pago = Pago::query()->sole();
    expect($pago)
        ->estado->toBe(EstadoPago::Validado)
        ->anterior_al_sistema->toBeTrue()
        ->motivo_anterior->toBe('Pago de noviembre de 2026, anterior al sistema')
        ->recibo_path->toBeNull()
        ->numero_operacion->toBe('NOV-2026-015')
        ->validado_por->toBe($this->gestor->id)
        ->registrado_por->toBe($this->gestor->id)
        ->and(Matricula::query()->sole()->pago_id)->toBe($pago->id)
        ->and($this->grupo->refresh()->matriculados)->toBe(1)
        ->and($this->persona->refresh()->habilitada_en)->not->toBeNull();
});

it('stores the receipt file of a payment made before the system when the staff member has it', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListMatriculas::class)
        ->callAction('matricularEnVentanilla', data: datosDelPagoAnterior(['recibo' => UploadedFile::fake()->image('recibo.jpg')]))
        ->assertHasNoFormErrors()
        ->assertNotified('Matrícula registrada');

    $pago = Pago::query()->sole();
    expect($pago->recibo_path)->toEndWith('.jpg')
        ->and($pago->recibo_sha256)->toMatch('/^[0-9a-f]{64}$/')
        ->and(Matricula::query()->sole()->pago_id)->toBe($pago->id);
    Storage::disk('local')->assertExists((string) $pago->recibo_path);
});

it('requires the reason of a payment made before the system', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListMatriculas::class)
        ->callAction('matricularEnVentanilla', data: datosDelPagoAnterior(['motivo_anterior' => '']))
        ->assertHasFormErrors(['motivo_anterior' => 'required']);

    expect(Pago::query()->count())->toBe(0)
        ->and(Matricula::query()->count())->toBe(0);
});

it('hides the payment made before the system while the switch is off, and the action refuses it', function (): void {
    apagarPagosAnteriores();
    $this->actingAs($this->gestor);

    Livewire::test(ListMatriculas::class)
        ->mountAction('matricularEnVentanilla')
        ->assertFormFieldExists('modo_de_pago', fn (Radio $campo): bool => $campo->getOptions() === ['validado' => 'Pago validado del estudiante'])
        ->fillForm(['modo_de_pago' => 'anterior'])
        ->assertFormFieldDoesNotExist('motivo_anterior');

    expect(fn (): Matricula => app(MatricularEnVentanilla::class)($this->persona->id, $this->grupo->id, null, pagoAnteriorDeVentanilla(), $this->gestor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El registro de pagos anteriores al sistema está desactivado en la configuración de pagos.');
    expect(Pago::query()->count())->toBe(0);
});

it('leaves no payment and no file when the enrollment fails', function (): void {
    $grupoCiclo2 = Grupo::factory()->for($this->periodo)->for($this->ciclo2)->create();

    expect(fn (): Matricula => app(MatricularEnVentanilla::class)($this->persona->id, $grupoCiclo2->id, null, pagoAnteriorDeVentanilla(), $this->gestor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No está habilitado para este ciclo');

    expect(Pago::query()->count())->toBe(0)
        ->and(Matricula::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and($grupoCiclo2->refresh()->matriculados)->toBe(0);
});

it('asks for exactly one payment', function (?int $pagoId, bool $conPagoAnterior): void {
    $pagoAnterior = $conPagoAnterior ? pagoAnteriorDeVentanilla() : null;

    expect(fn (): Matricula => app(MatricularEnVentanilla::class)($this->persona->id, $this->grupo->id, $pagoId, $pagoAnterior, $this->gestor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Elige un pago validado del estudiante o registra el pago anterior al sistema.');
})->with([
    'none' => [null, false],
    'both' => [999, true],
]);

it('offers the office enrollment only to staff who manage enrollments', function (): void {
    $this->actingAs($this->lector);
    Livewire::test(ListMatriculas::class)->assertActionHidden('matricularEnVentanilla');

    $this->actingAs($this->gestor);
    Livewire::test(ListMatriculas::class)->assertActionVisible('matricularEnVentanilla');

    expect(fn (): Matricula => app(MatricularEnVentanilla::class)($this->persona->id, $this->grupo->id, null, pagoAnteriorDeVentanilla(), $this->lector))
        ->toThrow(AuthorizationException::class);
    expect(Pago::query()->count())->toBe(0);
});

it('refuses a payment made before the system from staff who cannot register payments', function (): void {
    $sinPagos = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoMatricula::valores()]);

    expect(fn (): Matricula => app(MatricularEnVentanilla::class)($this->persona->id, $this->grupo->id, null, pagoAnteriorDeVentanilla(), $sinPagos))
        ->toThrow(AuthorizationException::class);
    expect(Pago::query()->count())->toBe(0)
        ->and(Matricula::query()->count())->toBe(0);
});

it('registers a student new to the system and enrolls them in the same screen', function (): void {
    $this->actingAs($this->gestor->givePermissionTo(PermisoPersona::Gestionar->value));

    Livewire::test(ListMatriculas::class)
        ->callAction('matricularEnVentanilla', data: datosDelPagoAnterior([
            'persona' => [
                'tipo_documento' => 'dni', 'numero_documento' => '47001122', 'apellido_paterno' => 'Pariona', 'nombres' => 'Rut',
                'tipo_vinculo' => 'estudiante_unasam', 'acepta_tratamiento' => true,
            ],
        ]))
        ->assertHasNoActionErrors()
        ->assertNotified('Matrícula registrada');

    $nueva = Persona::query()->where('numero_documento', '47001122')->sole();

    expect(Matricula::query()->where('persona_id', $nueva->id)->sole()->grupo_id)->toBe($this->grupo->id)
        ->and(Pago::query()->where('persona_id', $nueva->id)->sole()->estado)->toBe(EstadoPago::Validado)
        ->and($nueva->tratamiento_aceptado_en)->not->toBeNull();
});

it('leaves no new student behind when the enrollment is refused', function (): void {
    $this->actingAs($this->gestor->givePermissionTo(PermisoPersona::Gestionar->value));
    $grupoCiclo2 = Grupo::factory()->for($this->periodo)->for($this->ciclo2)->create();

    Livewire::test(ListMatriculas::class)
        ->callAction('matricularEnVentanilla', data: datosDelPagoAnterior([
            'grupo_id' => $grupoCiclo2->id,
            'persona' => ['tipo_documento' => 'dni', 'numero_documento' => '47001123', 'apellido_paterno' => 'Pariona', 'nombres' => 'Rut', 'tipo_vinculo' => 'estudiante_unasam'],
        ]));

    expect(Persona::query()->where('numero_documento', '47001123')->exists())->toBeFalse()
        ->and(Pago::query()->count())->toBe(0)
        ->and(Matricula::query()->count())->toBe(0);
});

it('tells staff to register and validate the payment first when the student has no validated enrollment payment', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListMatriculas::class)
        ->mountAction('matricularEnVentanilla')
        ->fillForm(['persona' => ['tipo_documento' => $this->persona->tipo_documento, 'numero_documento' => $this->persona->numero_documento]])
        ->assertSchemaComponentVisible('sin-pagos-validados')
        ->assertFormFieldHidden('pago_id');
});

it('opens the office enrollment from the person page with the student already chosen', function (): void {
    $pago = Pago::factory()->validado()->for($this->concepto)->create(['persona_id' => $this->persona->id]);
    $this->actingAs($this->gestor->givePermissionTo(PermisoPersona::Ver->value));

    Livewire::test(ViewPersona::class, ['record' => $this->persona->getRouteKey()])
        ->mountAction('matricular')
        ->assertSchemaStateSet(['persona.numero_documento' => $this->persona->numero_documento, 'persona_id' => $this->persona->id])
        ->fillForm(['grupo_id' => $this->grupo->id, 'modo_de_pago' => 'validado', 'pago_id' => $pago->id])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified('Matrícula registrada');

    expect(Matricula::query()->where('persona_id', $this->persona->id)->sole()->pago_id)->toBe($pago->id);
});
