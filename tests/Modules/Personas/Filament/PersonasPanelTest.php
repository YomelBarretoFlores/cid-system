<?php

declare(strict_types=1);

use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Filament\Resources\Personas\Pages\CreatePersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\EditPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\ListPersonas;
use App\Modules\Personas\Filament\Resources\Personas\Pages\ViewPersona;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use App\Modules\Personas\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function usuarioDePersonas(PermisoPersona ...$permisos): User
{
    return User::factory()->create()->givePermissionTo([
        User::ADMIN_PANEL_PERMISSION,
        ...array_map(fn (PermisoPersona $permiso): string => $permiso->value, $permisos),
    ]);
}

/**
 * @param  array<string, string|null>  $cambios
 * @return array<string, string|null>
 */
function datosDePersona(array $cambios = []): array
{
    return [
        'tipo_documento' => TipoDocumento::Dni->value,
        'numero_documento' => '45678912',
        'apellido_paterno' => 'Huamán',
        'apellido_materno' => 'Torres',
        'nombres' => 'Lucía',
        'fecha_nacimiento' => '1998-05-14',
        'correo' => 'lucia.prueba@example.com',
        'celular' => '987654321',
        'tipo_vinculo' => TipoVinculo::EstudianteUnasam->value,
        ...$cambios,
    ];
}

beforeEach(function (): void {
    $this->persona = Persona::factory()->create([
        'numero_documento' => '40123456', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Ana',
        'tipo_vinculo' => TipoVinculo::DocenteAdministrativoUnasam,
    ]);
    $this->extranjera = Persona::factory()->extranjera()->create([
        'numero_documento' => '001234567', 'apellido_paterno' => 'Rossi', 'nombres' => 'Marco',
    ]);
});

it('keeps the people screens away from users without permission to see people', function (string $pagina): void {
    $this->actingAs(usuarioDePersonas());

    $this->get(PersonaResource::getUrl($pagina, $pagina === 'index' ? [] : ['record' => $this->persona]))->assertForbidden();
})->with(['index', 'view']);

it('lets a read-only user see people but not register or edit them', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver));

    $this->get(PersonaResource::getUrl('index'))->assertOk();
    $this->get(PersonaResource::getUrl('view', ['record' => $this->persona]))->assertOk();
    $this->get(PersonaResource::getUrl('create'))->assertForbidden();
    $this->get(PersonaResource::getUrl('edit', ['record' => $this->persona]))->assertForbidden();
});

it('lists people with their document, full name, link type and email', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver));

    Livewire::test(ListPersonas::class)
        ->assertCanSeeTableRecords([$this->persona, $this->extranjera])
        ->assertTableColumnFormattedStateSet('numero_documento', 'DNI 40123456', $this->persona)
        ->assertTableColumnStateSet('nombre_completo', 'Quispe Mamani, Ana', $this->persona)
        ->assertTableColumnFormattedStateSet('tipo_vinculo', 'Docente/Administrativo UNASAM', $this->persona)
        ->assertTableColumnStateSet('correo', $this->persona->correo, $this->persona)
        ->assertTableActionDoesNotExist('delete')
        ->assertTableBulkActionDoesNotExist('delete')
        ->assertActionDoesNotExist('export');
});

it('finds people by document prefix or by any part of the name', function (string $termino, string $encontrada, string $descartada): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver));

    Livewire::test(ListPersonas::class)
        ->searchTable($termino)
        ->assertCanSeeTableRecords([$this->{$encontrada}])
        ->assertCanNotSeeTableRecords([$this->{$descartada}]);
})->with([
    'document prefix' => ['4012', 'persona', 'extranjera'],
    'paternal surname' => ['quispe', 'persona', 'extranjera'],
    'given name' => ['marco', 'extranjera', 'persona'],
]);

it('filters people by link type and document type', function (string $filtro, string $valor, string $encontrada, string $descartada): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver));

    Livewire::test(ListPersonas::class)
        ->filterTable($filtro, $valor)
        ->assertCanSeeTableRecords([$this->{$encontrada}])
        ->assertCanNotSeeTableRecords([$this->{$descartada}]);
})->with([
    'link type' => ['tipo_vinculo', TipoVinculo::DocenteAdministrativoUnasam->value, 'persona', 'extranjera'],
    'document type' => ['tipo_documento', TipoDocumento::Ce->value, 'extranjera', 'persona'],
]);

it('shows a person without offering to delete them', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver));

    Livewire::test(ViewPersona::class, ['record' => $this->persona->getRouteKey()])
        ->assertSchemaStateSet(['numero_documento' => '40123456', 'nombres' => 'Ana'])
        ->assertActionDoesNotExist('delete')
        ->assertActionHidden('edit');
});

it('registers a person with a normalized document and phone', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver, PermisoPersona::Gestionar));

    Livewire::test(CreatePersona::class)
        ->fillForm(datosDePersona([
            'tipo_documento' => TipoDocumento::Pasaporte->value, 'numero_documento' => ' ab123456 ',
            'apellido_materno' => null, 'celular' => '+51 987 654-321',
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Persona::query()->where('numero_documento', 'AB123456')->sole())
        ->tipo_documento->toBe(TipoDocumento::Pasaporte)
        ->apellido_materno->toBeNull()
        ->celular->toBe('+51987654321')
        ->tipo_vinculo->toBe(TipoVinculo::EstudianteUnasam)
        ->user_id->toBeNull();
});

it('rejects data the database would refuse', function (array $cambios, string $campo, string $mensaje): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver, PermisoPersona::Gestionar));

    Livewire::test(CreatePersona::class)
        ->fillForm(datosDePersona($cambios))
        ->call('create')
        ->assertHasFormErrors([$campo => $mensaje]);

    expect(Persona::query()->count())->toBe(2);
})->with([
    'DNI with 7 digits' => [['numero_documento' => '4567891'], 'numero_documento', PersonaForm::DNI_INVALIDO],
    'DNI with letters' => [['numero_documento' => '4567891A'], 'numero_documento', PersonaForm::DNI_INVALIDO],
    'CE too short' => [['tipo_documento' => 'ce', 'numero_documento' => 'ab123'], 'numero_documento', PersonaForm::DOCUMENTO_INVALIDO],
    'passport with symbols' => [['tipo_documento' => 'pasaporte', 'numero_documento' => 'AB-12345'], 'numero_documento', PersonaForm::DOCUMENTO_INVALIDO],
    'missing paternal surname' => [['apellido_paterno' => null], 'apellido_paterno', 'required'],
    'missing names' => [['nombres' => null], 'nombres', 'required'],
    'invalid email' => [['correo' => 'lucia.prueba'], 'correo', 'email'],
    'phone too short' => [['celular' => '98765'], 'celular', PersonaForm::CELULAR_INVALIDO],
    'phone with letters' => [['celular' => '98765432A'], 'celular', PersonaForm::CELULAR_INVALIDO],
    'birth date today' => [fn (): array => ['fecha_nacimiento' => today('America/Lima')->toDateString()], 'fecha_nacimiento', 'before'],
    'birth date in 1900' => [['fecha_nacimiento' => '1900-01-01'], 'fecha_nacimiento', 'after'],
    'missing link type' => [['tipo_vinculo' => null], 'tipo_vinculo', 'required'],
]);

it('refuses a document that is already registered', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver, PermisoPersona::Gestionar));

    Livewire::test(CreatePersona::class)
        ->fillForm(datosDePersona(['tipo_documento' => 'ce', 'numero_documento' => ' 001234567']))
        ->call('create')
        ->assertHasFormErrors(['numero_documento' => PersonaForm::DOCUMENTO_REPETIDO]);

    expect(Persona::query()->count())->toBe(2);
});

it('accepts the same number under another document type', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver, PermisoPersona::Gestionar));

    Livewire::test(CreatePersona::class)
        ->fillForm(datosDePersona(['tipo_documento' => 'ce', 'numero_documento' => '40123456']))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Persona::query()->where('numero_documento', '40123456')->count())->toBe(2);
});

it('reports a document registered by someone else at the same time as a form error', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver, PermisoPersona::Gestionar));
    Persona::creating(function (Persona $nueva): void {
        DB::table('per_personas')->insert([
            'ulid' => (string) str()->ulid(), 'tipo_documento' => $nueva->tipo_documento->value,
            'numero_documento' => $nueva->numero_documento, 'apellido_paterno' => 'Simultánea', 'nombres' => 'Prueba',
        ]);
    });

    Livewire::test(CreatePersona::class)
        ->fillForm(datosDePersona())
        ->call('create')
        ->assertHasFormErrors(['numero_documento' => PersonaForm::DOCUMENTO_REPETIDO]);

    expect(Persona::query()->count())->toBe(2);
});

it('edits a person keeping their document unique and their account link', function (): void {
    $usuario = User::factory()->create();
    $this->persona->update(['user_id' => $usuario->id]);
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver, PermisoPersona::Gestionar));

    Livewire::test(EditPersona::class, ['record' => $this->persona->getRouteKey()])
        ->assertActionDoesNotExist('delete')
        ->assertFormFieldDoesNotExist('user_id')
        ->fillForm(['numero_documento' => '40123456', 'correo' => 'ANA.NUEVA@example.com'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->fillForm(['tipo_documento' => 'ce', 'numero_documento' => '001234567'])
        ->call('save')
        ->assertHasFormErrors(['numero_documento' => PersonaForm::DOCUMENTO_REPETIDO]);

    expect($this->persona->refresh())
        ->tipo_documento->toBe(TipoDocumento::Dni)
        ->numero_documento->toBe('40123456')
        ->correo->toBe('ana.nueva@example.com')
        ->user_id->toBe($usuario->id);
});

it('reports a document taken by someone else while editing as a form error', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver, PermisoPersona::Gestionar));
    Persona::updating(function (Persona $editada): void {
        DB::table('per_personas')->insert([
            'ulid' => (string) str()->ulid(), 'tipo_documento' => $editada->tipo_documento->value,
            'numero_documento' => $editada->numero_documento, 'apellido_paterno' => 'Simultánea', 'nombres' => 'Prueba',
        ]);
    });

    Livewire::test(EditPersona::class, ['record' => $this->persona->getRouteKey()])
        ->fillForm(['numero_documento' => '41234567'])
        ->call('save')
        ->assertHasFormErrors(['numero_documento' => PersonaForm::DOCUMENTO_REPETIDO]);

    expect($this->persona->refresh()->numero_documento)->toBe('40123456');
});

it('warns that the personal email does not change the portal login email only when the person has an account', function (): void {
    $this->actingAs(usuarioDePersonas(PermisoPersona::Ver, PermisoPersona::Gestionar));
    $aviso = 'Este correo no cambia el correo con el que ingresa al portal.';

    Livewire::test(EditPersona::class, ['record' => $this->persona->getRouteKey()])->assertDontSee($aviso);

    $this->persona->update(['user_id' => User::factory()->create()->id]);
    Livewire::test(EditPersona::class, ['record' => $this->persona->getRouteKey()])->assertSee($aviso);
});
