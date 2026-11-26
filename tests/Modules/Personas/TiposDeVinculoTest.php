<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Actions\RegistrarTarifa;
use App\Modules\Pagos\Contracts\ResumenDePagosDePersona;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Personas\Actions\GuardarTipoDeVinculo;
use App\Modules\Personas\Actions\ObtenerVinculoPorDefecto;
use App\Modules\Personas\Actions\RegistrarCuentaDeEstudiante;
use App\Modules\Personas\Data\SolicitudDeCuenta;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\EditPersona;
use App\Modules\Personas\Filament\Resources\TiposVinculo\Pages\CreateTipoVinculo;
use App\Modules\Personas\Filament\Resources\TiposVinculo\Pages\EditTipoVinculo;
use App\Modules\Personas\Filament\Resources\TiposVinculo\Pages\ListTiposVinculo;
use App\Modules\Personas\Filament\Resources\TiposVinculo\TipoVinculoResource;
use App\Modules\Personas\Models\Persona;
use App\Modules\Personas\Models\TipoVinculo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value, PermisoPersona::Gestionar->value]);
});

function egresado(): TipoVinculo
{
    return app(GuardarTipoDeVinculo::class)(null, ['nombre' => 'Egresado UNASAM', 'orden' => 4, 'activo' => true, 'por_defecto' => false]);
}

function solicitudDeCuenta(): SolicitudDeCuenta
{
    return new SolicitudDeCuenta('dni', '45678912', 'Quispe', null, 'Ana', CarbonImmutable::parse('2000-01-01'), 'ana@correo.test', null, true, 'clave-segura1');
}

it('keeps the three vínculos that existed before, in order, with público general as the default', function (): void {
    expect(TipoVinculo::query()->activos()->pluck('nombre', 'codigo')->all())->toBe([
        'estudiante_unasam' => 'Estudiante UNASAM',
        'docente_administrativo_unasam' => 'Docente/Administrativo UNASAM',
        'publico_general' => 'Público general',
    ])->and(TipoVinculo::query()->where('por_defecto', true)->value('codigo'))->toBe('publico_general');
});

it('charges a person with a new vínculo the fee registered for it', function (): void {
    Storage::fake('local');
    $vinculo = egresado();
    $concepto = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]);
    app(RegistrarTarifa::class)($concepto->id, $vinculo->codigo, '95.00', CarbonImmutable::parse('2020-01-01'));
    $persona = Persona::factory()->create(['tipo_vinculo' => $vinculo->codigo]);

    $pago = app(RegistrarPago::class)(
        new RegistroDePago($persona->id, $concepto->id, MedioDePago::idDe('caja_presencial'), 'OP-9', CarbonImmutable::parse('2027-03-01'), '95.00'),
        UploadedFile::fake()->createWithContent('r.pdf', "%PDF-1.4\n%%EOF\n"),
    );

    expect($vinculo->codigo)->toBe('egresado_unasam')
        ->and($pago->tarifa->tipo_vinculo)->toBe('egresado_unasam')
        ->and($pago->monto)->toBe('95.00')
        ->and(app(ResumenDePagosDePersona::class)($persona->id)->montoDeMatricula)->toBe('95.00');
});

it('refuses a fee for an unknown or an inactive vínculo', function (string $codigo): void {
    egresado()->update(['activo' => false]);
    $concepto = Concepto::factory()->create();

    expect(fn () => app(RegistrarTarifa::class)($concepto->id, $codigo, '95.00', CarbonImmutable::parse('2027-01-01')))
        ->toThrow(ReglaDeNegocioIncumplida::class, RegistrarTarifa::VINCULO_NO_DISPONIBLE)
        ->and($concepto->tarifas()->count())->toBe(0);
})->with(['inactive' => 'egresado_unasam', 'unknown' => 'jubilado']);

it('makes the database refuse a person or a fee with a code outside the catalog', function (): void {
    expect(fn () => DB::transaction(fn () => Persona::factory()->create(['tipo_vinculo' => 'jubilado'])))->toThrow(QueryException::class, 'per_personas_tipo_vinculo_foreign');
});

it('gives a self-registered account the default vínculo of the catalog', function (): void {
    Notification::fake();
    app(GuardarTipoDeVinculo::class)(null, ['nombre' => 'Egresado UNASAM', 'orden' => 4, 'activo' => true, 'por_defecto' => true]);

    $user = app(RegistrarCuentaDeEstudiante::class)(solicitudDeCuenta());

    expect(Persona::query()->where('user_id', $user->id)->value('tipo_vinculo'))->toBe('egresado_unasam')
        ->and(TipoVinculo::query()->where('por_defecto', true)->pluck('codigo')->all())->toBe(['egresado_unasam']);
});

it('refuses self-registration when no vínculo is marked as the default', function (): void {
    TipoVinculo::query()->update(['por_defecto' => false]);

    expect(fn () => app(RegistrarCuentaDeEstudiante::class)(solicitudDeCuenta()))
        ->toThrow(ReglaDeNegocioIncumplida::class, ObtenerVinculoPorDefecto::SIN_POR_DEFECTO)
        ->and(User::query()->where('email', 'ana@correo.test')->exists())->toBeFalse();
});

it('refuses to leave the catalog without a default or with an inactive one', function (): void {
    $publico = TipoVinculo::query()->where('codigo', 'publico_general')->sole();

    expect(fn () => app(GuardarTipoDeVinculo::class)($publico->id, ['nombre' => 'Público general', 'orden' => 3, 'activo' => true, 'por_defecto' => false]))
        ->toThrow(ReglaDeNegocioIncumplida::class, GuardarTipoDeVinculo::SIN_POR_DEFECTO)
        ->and(fn () => app(GuardarTipoDeVinculo::class)($publico->id, ['nombre' => 'Público general', 'orden' => 3, 'activo' => false, 'por_defecto' => true]))
        ->toThrow(ReglaDeNegocioIncumplida::class, GuardarTipoDeVinculo::POR_DEFECTO_INACTIVO)
        ->and($publico->refresh())->activo->toBeTrue()->por_defecto->toBeTrue();
});

it('shows the fees of a new vínculo under its name in the public fee table', function (): void {
    $vinculo = egresado();
    $concepto = Concepto::factory()->create(['codigo' => 'TUSNE-050', 'nombre' => 'Matrícula por ciclo']);
    app(RegistrarTarifa::class)($concepto->id, $vinculo->codigo, '95.00', CarbonImmutable::parse('2020-01-01'));

    $this->get('/')->assertOk()->assertSeeTextInOrder(['Egresado UNASAM', 'Matrícula por ciclo', 'S/ 95.00']);
});

it('lets staff create, rename and deactivate a vínculo whose code never changes', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreateTipoVinculo::class)
        ->fillForm(['nombre' => 'Egresado UNASAM', 'orden' => 4])
        ->call('create')
        ->assertHasNoFormErrors();

    $vinculo = TipoVinculo::query()->where('codigo', 'egresado_unasam')->sole();

    Livewire::test(EditTipoVinculo::class, ['record' => $vinculo->getRouteKey()])
        ->fillForm(['nombre' => 'Egresado de la UNASAM', 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vinculo->refresh())->nombre->toBe('Egresado de la UNASAM')->codigo->toBe('egresado_unasam')->activo->toBeFalse();

    Livewire::test(ListTiposVinculo::class)
        ->assertCanSeeTableRecords(TipoVinculo::query()->get())
        ->assertTableActionDoesNotExist('delete');
});

it('refuses a second vínculo with the same name and moving the default away by unmarking it', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreateTipoVinculo::class)
        ->fillForm(['nombre' => 'Público general', 'orden' => 9])
        ->call('create')
        ->assertHasFormErrors(['nombre']);

    Livewire::test(EditTipoVinculo::class, ['record' => TipoVinculo::query()->where('codigo', 'publico_general')->sole()->getRouteKey()])
        ->fillForm(['por_defecto' => false])
        ->call('save')
        ->assertNotified(GuardarTipoDeVinculo::SIN_POR_DEFECTO);

    expect(TipoVinculo::query()->where('codigo', 'publico_general')->value('por_defecto'))->toBeTrue();
});

it('keeps the vínculo screens away from staff who cannot manage people', function (): void {
    $consulta = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value]);

    $this->actingAs($consulta)->get(TipoVinculoResource::getUrl('index'))->assertForbidden();
    $this->actingAs($this->gestor)->get(TipoVinculoResource::getUrl('index'))->assertOk()->assertSeeText('Público general');
});

it('offers only active vínculos to a person but keeps the inactive one already stored', function (): void {
    $this->actingAs($this->gestor);
    $vinculo = egresado();
    $persona = Persona::factory()->create(['tipo_vinculo' => $vinculo->codigo]);
    $vinculo->update(['activo' => false]);

    Livewire::test(EditPersona::class, ['record' => $persona->getRouteKey()])
        ->assertFormFieldExists('tipo_vinculo', fn ($campo): bool => array_key_exists('egresado_unasam', $campo->getOptions()))
        ->call('save')
        ->assertHasNoFormErrors();

    $otra = Persona::factory()->create();
    Livewire::test(EditPersona::class, ['record' => $otra->getRouteKey()])
        ->assertFormFieldExists('tipo_vinculo', fn ($campo): bool => ! array_key_exists('egresado_unasam', $campo->getOptions()));

    expect($persona->refresh()->tipo_vinculo)->toBe('egresado_unasam');
});

it('rolls the catalog back to the fixed vínculos and forward again, keeping every stored code', function (): void {
    $migracion = require base_path('app/Modules/Personas/Database/Migrations/2026_11_30_100000_turn_tipo_vinculo_into_a_catalog.php');
    $persona = Persona::factory()->estudianteUnasam()->create();

    $migracion->down();

    expect(fn () => DB::transaction(fn () => DB::table('per_personas')->where('id', $persona->id)->update(['tipo_vinculo' => 'jubilado'])))->toThrow(QueryException::class, 'per_personas_tipo_vinculo_check');

    $migracion->up();

    expect($persona->refresh()->vinculo->nombre)->toBe('Estudiante UNASAM');
});
