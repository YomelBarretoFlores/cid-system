<?php

declare(strict_types=1);

use App\Modules\Personas\Actions\ComprobarDocumento;
use App\Modules\Personas\Actions\GuardarTipoDeDocumento;
use App\Modules\Personas\Actions\RegistrarCuentaDeEstudiante;
use App\Modules\Personas\Contracts\CatalogoDeDocumentos;
use App\Modules\Personas\Contracts\FichaDePersona;
use App\Modules\Personas\Data\SolicitudDeCuenta;
use App\Modules\Personas\Data\TipoDeDocumento;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\CreatePersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\EditPersona;
use App\Modules\Personas\Filament\Resources\TiposDocumento\Pages\CreateTipoDocumento;
use App\Modules\Personas\Filament\Resources\TiposDocumento\Pages\EditTipoDocumento;
use App\Modules\Personas\Filament\Resources\TiposDocumento\Pages\ListTiposDocumento;
use App\Modules\Personas\Filament\Resources\TiposDocumento\TipoDocumentoResource;
use App\Modules\Personas\Livewire\Registro;
use App\Modules\Personas\Models\Persona;
use App\Modules\Personas\Models\TipoDocumento;
use App\Modules\Personas\Support\DocumentoValido;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value, PermisoPersona::Gestionar->value]);
});

function carneDeRefugiado(bool $activo = true): TipoDocumento
{
    return app(GuardarTipoDeDocumento::class)(null, [
        'nombre' => 'Carné de refugiado', 'solo_digitos' => false, 'longitud_minima' => 6, 'longitud_maxima' => 15,
        'orden' => 4, 'activo' => $activo, 'por_defecto' => false,
    ]);
}

function solicitudConDocumento(string $tipo, string $numero): SolicitudDeCuenta
{
    return new SolicitudDeCuenta($tipo, $numero, 'Quispe', null, 'Ana', CarbonImmutable::parse('2000-01-01'), 'ana@correo.test', null, true, 'clave-segura1');
}

it('keeps the three document types that existed before, with their rules, and DNI as the default', function (): void {
    expect(array_map(fn (TipoDeDocumento $tipo): array => [$tipo->codigo, $tipo->nombre, $tipo->soloDigitos, $tipo->longitudMinima, $tipo->longitudMaxima, $tipo->porDefecto], app(CatalogoDeDocumentos::class)()))->toBe([
        ['dni', 'DNI', true, 8, 8, true],
        ['ce', 'Carné de Extranjería', false, 6, 12, false],
        ['pasaporte', 'Pasaporte', false, 6, 12, false],
    ]);
});

it('lets a person register in the portal with a document type staff created', function (): void {
    Notification::fake();
    carneDeRefugiado();

    $componente = Livewire::test(Registro::class)->assertSet('tipo_documento', 'dni')->assertSee('Carné de refugiado');

    foreach ([
        'tipo_documento' => 'carne_de_refugiado', 'numero_documento' => 'ref12345678901', 'apellido_paterno' => 'Quispe', 'nombres' => 'Rosa',
        'fecha_nacimiento' => '2001-05-14', 'email' => 'rosa@correo.test', 'celular' => '987654321',
        'password' => 'clave-segura1', 'password_confirmation' => 'clave-segura1', 'acepta_tratamiento' => true,
    ] as $campo => $valor) {
        $componente->set($campo, $valor);
    }

    $componente->call('registrarse')->assertHasNoErrors();

    $persona = Persona::query()->sole();
    expect($persona)->tipo_documento->toBe('carne_de_refugiado')->numero_documento->toBe('REF12345678901')
        ->and(app(FichaDePersona::class)($persona->id)->documento)->toBe('Carné de refugiado REF12345678901');
});

it('lets staff create a person with a new document type and applies its rule', function (): void {
    $this->actingAs($this->gestor);
    carneDeRefugiado();
    $datos = [
        'tipo_documento' => 'carne_de_refugiado', 'apellido_paterno' => 'Huamán', 'nombres' => 'Lucía', 'tipo_vinculo' => 'publico_general',
    ];

    Livewire::test(CreatePersona::class)
        ->fillForm([...$datos, 'numero_documento' => 'AB12'])
        ->call('create')
        ->assertHasFormErrors(['numero_documento' => 'El documento debe tener de 6 a 15 letras o números, sin espacios ni guiones.']);

    Livewire::test(CreatePersona::class)
        ->fillForm([...$datos, 'numero_documento' => 'ab1234567890123'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Persona::query()->sole())->tipo_documento->toBe('carne_de_refugiado')->numero_documento->toBe('AB1234567890123');
});

it('applies the rule edited in the catalog to new records with the same message as before', function (): void {
    $dni = TipoDocumento::query()->where('codigo', 'dni')->sole();

    expect(fn () => app(RegistrarCuentaDeEstudiante::class)(solicitudConDocumento('dni', '4123456')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El DNI tiene 8 dígitos.');

    app(GuardarTipoDeDocumento::class)($dni->id, [
        'nombre' => 'DNI', 'solo_digitos' => true, 'longitud_minima' => 7, 'longitud_maxima' => 8, 'orden' => 1, 'activo' => true, 'por_defecto' => true,
    ]);
    Notification::fake();

    app(RegistrarCuentaDeEstudiante::class)(solicitudConDocumento('dni', '4123456'));

    expect(Persona::query()->sole()->numero_documento)->toBe('4123456');
});

it('keeps stored numbers valid after their rule changes and checks them again only when they change', function (): void {
    $this->actingAs($this->gestor);
    $persona = Persona::factory()->create(['numero_documento' => '41234567']);
    TipoDocumento::query()->where('codigo', 'dni')->update(['longitud_minima' => 9, 'longitud_maxima' => 9]);

    Livewire::test(EditPersona::class, ['record' => $persona->getRouteKey()])
        ->fillForm(['correo' => 'nuevo@example.com'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->fillForm(['numero_documento' => '41234568'])
        ->call('save')
        ->assertHasFormErrors(['numero_documento' => 'El DNI debe tener exactamente 9 dígitos.']);

    expect($persona->refresh())->numero_documento->toBe('41234567')->correo->toBe('nuevo@example.com');
});

it('does not offer an inactive document type and refuses it in the portal, the panel and the action', function (): void {
    $this->actingAs($this->gestor);
    carneDeRefugiado(activo: false);

    Livewire::test(Registro::class)
        ->assertDontSee('Carné de refugiado')
        ->set('tipo_documento', 'carne_de_refugiado')
        ->set('numero_documento', 'REF123456')
        ->call('registrarse')
        ->assertHasErrors(['tipo_documento' => DocumentoValido::TIPO_NO_DISPONIBLE]);

    Livewire::test(CreatePersona::class)
        ->assertFormFieldExists('tipo_documento', fn ($campo): bool => ! array_key_exists('carne_de_refugiado', $campo->getOptions()));

    expect(fn () => app(ComprobarDocumento::class)(new Persona(['tipo_documento' => 'carne_de_refugiado', 'numero_documento' => 'REF123456'])))
        ->toThrow(ReglaDeNegocioIncumplida::class, DocumentoValido::TIPO_NO_DISPONIBLE)
        ->and(Persona::query()->count())->toBe(0);
});

it('keeps an inactive type already stored on a person who is edited', function (): void {
    $this->actingAs($this->gestor);
    $tipo = carneDeRefugiado();
    $persona = Persona::factory()->create(['tipo_documento' => $tipo->codigo, 'numero_documento' => 'REF123456']);
    $tipo->update(['activo' => false]);

    Livewire::test(EditPersona::class, ['record' => $persona->getRouteKey()])
        ->assertFormFieldExists('tipo_documento', fn ($campo): bool => array_key_exists('carne_de_refugiado', $campo->getOptions()))
        ->fillForm(['numero_documento' => 'REF1234567'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($persona->refresh()->numero_documento)->toBe('REF1234567');
});

it('makes the database refuse a person with a document type outside the catalog', function (): void {
    expect(fn () => DB::transaction(fn () => Persona::factory()->create(['tipo_documento' => 'cedula'])))
        ->toThrow(QueryException::class, 'per_personas_tipo_documento_foreign');
});

it('refuses a rule whose minimum length exceeds the maximum or the 20 characters stored', function (): void {
    $this->actingAs($this->gestor);

    expect(fn () => app(GuardarTipoDeDocumento::class)(null, [
        'nombre' => 'Cédula', 'solo_digitos' => true, 'longitud_minima' => 10, 'longitud_maxima' => 8, 'orden' => 5, 'activo' => true, 'por_defecto' => false,
    ]))->toThrow(ReglaDeNegocioIncumplida::class, GuardarTipoDeDocumento::LONGITUD_INVALIDA);

    Livewire::test(CreateTipoDocumento::class)
        ->fillForm(['nombre' => 'Cédula', 'longitud_minima' => 6, 'longitud_maxima' => 21, 'orden' => 5])
        ->call('create')
        ->assertHasFormErrors(['longitud_maxima']);

    expect(TipoDocumento::query()->count())->toBe(3);
});

it('lets staff create and edit a document type whose code never changes, without deleting it', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreateTipoDocumento::class)
        ->fillForm(['nombre' => 'Carné de refugiado', 'solo_digitos' => false, 'longitud_minima' => 6, 'longitud_maxima' => 15, 'orden' => 4])
        ->call('create')
        ->assertHasNoFormErrors();

    $tipo = TipoDocumento::query()->where('codigo', 'carne_de_refugiado')->sole();

    Livewire::test(EditTipoDocumento::class, ['record' => $tipo->getRouteKey()])
        ->fillForm(['nombre' => 'Carné de refugiado (CR)', 'longitud_maxima' => 12, 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tipo->refresh())->nombre->toBe('Carné de refugiado (CR)')->codigo->toBe('carne_de_refugiado')
        ->longitud_maxima->toBe(12)->activo->toBeFalse();

    Livewire::test(ListTiposDocumento::class)
        ->assertCanSeeTableRecords(TipoDocumento::query()->get())
        ->assertTableActionDoesNotExist('delete');
});

it('refuses to leave the catalog without a default document type', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(EditTipoDocumento::class, ['record' => TipoDocumento::query()->where('codigo', 'dni')->sole()->getRouteKey()])
        ->fillForm(['por_defecto' => false])
        ->call('save')
        ->assertNotified(GuardarTipoDeDocumento::SIN_POR_DEFECTO);

    expect(TipoDocumento::query()->where('codigo', 'dni')->value('por_defecto'))->toBeTrue();
});

it('keeps the document type screens away from staff who cannot manage people', function (): void {
    $consulta = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value]);

    $this->actingAs($consulta)->get(TipoDocumentoResource::getUrl('index'))->assertForbidden();
    $this->actingAs($this->gestor)->get(TipoDocumentoResource::getUrl('index'))->assertOk()->assertSeeText('Carné de Extranjería');
});

it('makes the database refuse a document number with anything but upper-case letters and digits', function (string $numero): void {
    $persona = Persona::factory()->extranjera()->create();

    expect(fn () => DB::transaction(fn () => DB::table('per_personas')->where('id', $persona->id)->update(['numero_documento' => $numero])))
        ->toThrow(QueryException::class, 'per_personas_numero_documento_formato_check')
        ->and($persona->refresh()->numero_documento)->not->toBe($numero);
})->with(['lower case' => 'ab123456', 'hyphen' => 'AB-12345', 'space' => 'AB 12345', 'empty' => '']);

it('rolls the catalog back to the fixed document types and forward again, keeping every stored number', function (): void {
    $migracion = require base_path('app/Modules/Personas/Database/Migrations/2026_12_01_100000_turn_tipo_documento_into_a_catalog.php');
    $persona = Persona::factory()->extranjera()->create();

    $migracion->down();

    expect(fn () => DB::transaction(fn () => DB::table('per_personas')->where('id', $persona->id)->update(['tipo_documento' => 'cedula'])))->toThrow(QueryException::class, 'per_personas_tipo_documento_check')
        ->and(fn () => DB::transaction(fn () => DB::table('per_personas')->where('id', $persona->id)->update(['numero_documento' => '123'])))->toThrow(QueryException::class, 'per_personas_documento_check');

    $migracion->up();

    expect(app(FichaDePersona::class)($persona->refresh()->id)->documento)->toBe("Carné de Extranjería {$persona->numero_documento}");
});

it('refuses to roll back while a person uses a document type created later', function (): void {
    $migracion = require base_path('app/Modules/Personas/Database/Migrations/2026_12_01_100000_turn_tipo_documento_into_a_catalog.php');
    Persona::factory()->create(['tipo_documento' => carneDeRefugiado()->codigo, 'numero_documento' => 'REF123456']);

    expect(fn () => DB::transaction(fn () => $migracion->down()))->toThrow(QueryException::class, 'per_personas_tipo_documento_check')
        ->and(Persona::query()->value('tipo_documento'))->toBe('carne_de_refugiado');
});
