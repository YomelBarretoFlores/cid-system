<?php

declare(strict_types=1);

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\CuentasDocentes\Pages\ListCuentasDocentes;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Actions\AutenticarUsuario;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoDocencia::valores()]);
});

function docenteSinCuenta(array $persona = []): Docente
{
    return Docente::factory()->create(['persona_id' => Persona::factory()->create(['numero_documento' => '41234567', 'correo' => null, 'user_id' => null, ...$persona])->id]);
}

function cuentasDeDocentes(User $usuario): Testable
{
    return Livewire::actingAs($usuario)->test(ListCuentasDocentes::class);
}

it('creates a teacher account with the DNI as username and the password typed by staff, without an email', function (): void {
    $docente = docenteSinCuenta();

    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('crearCuenta')->table($docente), data: ['contrasena' => 'docente2027', 'contrasena_confirmation' => 'docente2027'])
        ->assertHasNoActionErrors()
        ->assertNotified('Cuenta creada. Usuario: 41234567');

    $cuenta = User::query()->where('usuario', '41234567')->sole();
    expect($cuenta->email)->toBeNull()
        ->and(Hash::check('docente2027', $cuenta->password))->toBeTrue()
        ->and(Persona::query()->find($docente->persona_id)?->user_id)->toBe($cuenta->id)
        ->and(app(AutenticarUsuario::class)('41234567', 'docente2027', '127.0.0.1')->id)->toBe($cuenta->id);
});

it('keeps the email of the record on the new account when no other account uses it', function (): void {
    $docente = docenteSinCuenta(['correo' => 'Docente@Correo.test']);

    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('crearCuenta')->table($docente), data: ['contrasena' => 'docente2027', 'contrasena_confirmation' => 'docente2027']);

    expect(User::query()->where('usuario', '41234567')->sole()->email)->toBe('docente@correo.test');
});

it('gives the DNI username and a new password to the account a teacher already has', function (): void {
    $existente = User::factory()->create(['email' => 'docente@correo.test']);
    $existente->createToken('App móvil');
    $docente = docenteSinCuenta(['user_id' => $existente->id]);

    cuentasDeDocentes($this->gestor)
        ->assertActionHasLabel(TestAction::make('crearCuenta')->table($docente), 'Asignar usuario')
        ->callAction(TestAction::make('crearCuenta')->table($docente), data: ['contrasena' => 'docente2027', 'contrasena_confirmation' => 'docente2027'])
        ->assertHasNoActionErrors();

    expect(User::query()->count())->toBe(2)
        ->and($existente->fresh()?->usuario)->toBe('41234567')
        ->and($existente->tokens()->count())->toBe(0)
        ->and(Hash::check('docente2027', (string) $existente->fresh()?->password))->toBeTrue();
});

it('refuses a username another account already uses', function (): void {
    User::factory()->create(['usuario' => '41234567']);
    $docente = docenteSinCuenta();

    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('crearCuenta')->table($docente), data: ['contrasena' => 'docente2027', 'contrasena_confirmation' => 'docente2027'])
        ->assertNotified('Ya existe otra cuenta con el usuario 41234567.');

    expect(Persona::query()->find($docente->persona_id)?->user_id)->toBeNull();
});

it('asks for a password of at least eight characters with letters and numbers, typed twice', function (): void {
    $docente = docenteSinCuenta();

    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('crearCuenta')->table($docente), data: ['contrasena' => 'abc', 'contrasena_confirmation' => 'abc'])
        ->assertHasActionErrors(['contrasena']);
    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('crearCuenta')->table($docente), data: ['contrasena' => 'docente2027', 'contrasena_confirmation' => 'otra2027'])
        ->assertHasActionErrors(['contrasena']);

    expect(User::query()->where('usuario', '41234567')->exists())->toBeFalse();
});

it('changes the password at the office and closes the open sessions of the teacher', function (): void {
    $docente = docenteSinCuenta();
    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('crearCuenta')->table($docente), data: ['contrasena' => 'docente2027', 'contrasena_confirmation' => 'docente2027']);
    $cuenta = User::query()->where('usuario', '41234567')->sole();
    $cuenta->createToken('App móvil');

    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('cambiarContrasena')->table($docente), data: ['contrasena' => 'nueva2028', 'contrasena_confirmation' => 'nueva2028'])
        ->assertHasNoActionErrors()
        ->assertNotified('Contraseña cambiada');

    expect(Hash::check('nueva2028', (string) $cuenta->fresh()?->password))->toBeTrue()
        ->and($cuenta->tokens()->count())->toBe(0);
});

it('never sets the password of a staff account that also teaches', function (): void {
    $personal = User::factory()->create(['email' => 'personal@correo.test'])->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $docente = docenteSinCuenta(['user_id' => $personal->id]);
    $clave = $personal->password;

    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('crearCuenta')->table($docente), data: ['contrasena' => 'docente2027', 'contrasena_confirmation' => 'docente2027'])
        ->assertNotified('Es una cuenta del personal: gestiónala desde Usuarios del personal.');
    cuentasDeDocentes($this->gestor)
        ->callAction(TestAction::make('cambiarContrasena')->table($docente), data: ['contrasena' => 'docente2027', 'contrasena_confirmation' => 'docente2027'])
        ->assertNotified('Es una cuenta del personal: gestiónala desde Usuarios del personal.');

    expect($personal->fresh()?->password)->toBe($clave)
        ->and($personal->fresh()?->usuario)->toBeNull();
});

it('lists each teacher with the username and the state of the account', function (): void {
    $conCuenta = docenteSinCuenta(['user_id' => User::factory()->create(['usuario' => '41234567'])->id]);
    $sinCuenta = Docente::factory()->create();

    cuentasDeDocentes($this->gestor)
        ->assertCanSeeTableRecords([$conCuenta, $sinCuenta])
        ->assertTableColumnStateSet('usuario', '41234567', $conCuenta)
        ->assertTableColumnStateSet('estado_de_cuenta', 'Activa', $conCuenta)
        ->assertTableColumnStateSet('estado_de_cuenta', 'Sin cuenta', $sinCuenta)
        ->assertActionHidden(TestAction::make('cambiarContrasena')->table($sinCuenta));
});

it('lets only staff who manage teaching open the section', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value]);

    $this->actingAs($lector)->get(route('filament.admin.resources.cuentas-de-docentes.index'))->assertForbidden();
    $this->actingAs($this->gestor)->get(route('filament.admin.resources.cuentas-de-docentes.index'))->assertOk();
});
