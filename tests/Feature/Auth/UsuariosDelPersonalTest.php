<?php

declare(strict_types=1);

use App\Modules\Certificacion\Filament\Resources\Firmantes\FirmanteResource;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Shared\Auth\Actions\AutenticarUsuario;
use App\Shared\Auth\Actions\DesactivarUsuario;
use App\Shared\Auth\Actions\GuardarUsuarioDelPersonal;
use App\Shared\Auth\Data\DatosDeUsuarioDelPersonal;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Filament\IngresarAlPanel;
use App\Shared\Auth\Filament\Resources\Roles\RolResource;
use App\Shared\Auth\Filament\Resources\Usuarios\Pages\CreateUsuario;
use App\Shared\Auth\Filament\Resources\Usuarios\Pages\EditUsuario;
use App\Shared\Auth\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Shared\Auth\Filament\Resources\Usuarios\UsuarioResource;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Notifications\CorreoDeRestablecimiento;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as Correos;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    Correos::fake();
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoUsuario::Gestionar->value]);
});

function cuentaDelPersonal(string ...$permisos): User
{
    return User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...$permisos]);
}

function ingresarConCuentaDesactivable(User $usuario): mixed
{
    Auth::logout();

    return Livewire::test(IniciarSesion::class)->set('email', $usuario->email)->set('password', 'password')->call('ingresar');
}

it('creates a staff account with panel access and the chosen access, and emails it the link to set its own password', function (): void {
    $caja = Role::create(['name' => 'Caja', 'guard_name' => 'web'])->givePermissionTo(PermisoPago::Ver->value);
    $this->actingAs($this->gestor);

    Livewire::test(CreateUsuario::class)
        ->assertFormFieldDoesNotExist('password')
        ->fillForm(['name' => 'Ana Caja', 'email' => 'Ana.Caja@CILN.test', 'roles' => [$caja->id], 'permisos' => ['pagos' => [PermisoPago::Validar->value]]])
        ->call('create')
        ->assertHasNoFormErrors();

    $usuario = User::query()->where('email', 'ana.caja@ciln.test')->sole();

    expect($usuario->name)->toBe('Ana Caja')
        ->and($usuario->hasDirectPermission(User::ADMIN_PANEL_PERMISSION))->toBeTrue()
        ->and($usuario->getDirectPermissions()->pluck('name')->sort()->values()->all())->toBe([User::ADMIN_PANEL_PERMISSION, PermisoPago::Validar->value])
        ->and($usuario->hasRole('Caja'))->toBeTrue()
        ->and($usuario->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
    Correos::assertSentTo($usuario, CorreoDeRestablecimiento::class);
});

it('refuses an email that already has an account', function (): void {
    User::factory()->create(['email' => 'repetido@ciln.test']);
    $this->actingAs($this->gestor);

    Livewire::test(CreateUsuario::class)
        ->fillForm(['name' => 'Otra persona', 'email' => 'Repetido@ciln.test'])
        ->call('create')
        ->assertHasFormErrors(['email']);

    expect(User::query()->where('email', 'repetido@ciln.test')->count())->toBe(1);
    Correos::assertNothingSent();
});

it('saves the roles and permissions changed on the edit page, keeping the email read-only', function (): void {
    $caja = Role::create(['name' => 'Caja', 'guard_name' => 'web']);
    $cuenta = cuentaDelPersonal(PermisoPago::Ver->value)->assignRole($caja);
    $this->actingAs($this->gestor);

    Livewire::test(EditUsuario::class, ['record' => $cuenta->getRouteKey()])
        ->assertFormSet(['roles' => [$caja->id], 'permisos.pagos' => [PermisoPago::Ver->value]])
        ->assertFormFieldDisabled('email')
        ->fillForm(['name' => 'Nombre corregido', 'roles' => [], 'permisos' => ['pagos' => [], 'tablero' => ['tablero.ver']]])
        ->call('save')
        ->assertHasNoFormErrors();

    $cuenta = User::query()->findOrFail($cuenta->id);

    expect($cuenta->name)->toBe('Nombre corregido')
        ->and($cuenta->roles)->toBeEmpty()
        ->and($cuenta->getDirectPermissions()->pluck('name')->sort()->values()->all())->toBe([User::ADMIN_PANEL_PERMISSION, 'tablero.ver']);
});

it('lists staff and deactivated accounts with their roles, state and second factor, but not students', function (): void {
    $caja = cuentaDelPersonal()->assignRole(Role::create(['name' => 'Caja', 'guard_name' => 'web']));
    $caja->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
    $porRol = User::factory()->create()->assignRole(Role::create(['name' => 'Panel', 'guard_name' => 'web'])->givePermissionTo(User::ADMIN_PANEL_PERMISSION));
    $desactivada = User::factory()->create(['desactivado_en' => now()]);
    $estudiante = User::factory()->create();
    $this->actingAs($this->gestor);

    Livewire::test(ListUsuarios::class)
        ->assertCanSeeTableRecords([$this->gestor, $caja, $porRol, $desactivada])
        ->assertCanNotSeeTableRecords([$estudiante])
        ->assertTableColumnStateSet('roles.name', ['Caja'], $caja)
        ->assertTableColumnStateSet('estado', 'Desactivado', $desactivada)
        ->assertTableColumnStateSet('estado', 'Activo', $caja)
        ->assertTableColumnStateSet('doble_factor', 'Sí', $caja)
        ->assertTableColumnStateSet('doble_factor', 'No', $porRol);
});

it('deactivates an account: no portal, panel or API sign-in, no tokens, and the reason in the log', function (): void {
    Log::spy();
    $cuenta = cuentaDelPersonal(PermisoPago::Ver->value);
    $cuenta->createToken('movil');
    $recordar = $cuenta->remember_token;
    $this->actingAs($this->gestor);

    Livewire::test(ListUsuarios::class)
        ->callAction(TestAction::make('desactivar')->table($cuenta), data: ['motivo' => 'Dejó el cargo.'])
        ->assertNotified('Cuenta desactivada');

    $cuenta = User::query()->findOrFail($cuenta->id);

    expect($cuenta->desactivado_en)->not->toBeNull()
        ->and($cuenta->remember_token)->not->toBe($recordar)
        ->and($cuenta->tokens()->count())->toBe(0)
        ->and($cuenta->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
    Log::shouldHaveReceived('info')->with('Se desactivó una cuenta del personal.', ['usuario_id' => $cuenta->id, 'desactivado_por' => $this->gestor->id, 'motivo' => 'Dejó el cargo.']);

    ingresarConCuentaDesactivable($cuenta)->assertHasErrors(['email' => AutenticarUsuario::CREDENCIALES_INCORRECTAS]);
    $this->assertGuest();

    Livewire::test(IngresarAlPanel::class)
        ->fillForm(['email' => $cuenta->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);
    $this->assertGuest();

    $this->postJson('/api/v1/sesiones', ['email' => $cuenta->email, 'password' => 'password', 'dispositivo' => 'movil'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', AutenticarUsuario::CREDENCIALES_INCORRECTAS);
});

it('reactivates a deactivated account so it can sign in again', function (): void {
    $cuenta = cuentaDelPersonal();
    $cuenta->forceFill(['desactivado_en' => now()])->save();
    $this->actingAs($this->gestor);

    Livewire::test(ListUsuarios::class)
        ->assertActionHidden(TestAction::make('desactivar')->table($cuenta))
        ->callAction(TestAction::make('reactivar')->table($cuenta))
        ->assertNotified('Cuenta reactivada');

    expect(User::query()->findOrFail($cuenta->id)->desactivado_en)->toBeNull();
    ingresarConCuentaDesactivable($cuenta)->assertHasNoErrors();
    $this->assertAuthenticatedAs($cuenta);
});

it('refuses to deactivate your own account', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListUsuarios::class)
        ->callAction(TestAction::make('desactivar')->table($this->gestor), data: ['motivo' => 'Prueba.'])
        ->assertNotified('No puedes desactivar tu propia cuenta.');

    expect(User::query()->findOrFail($this->gestor->id)->desactivado_en)->toBeNull();
});

it('refuses to remove your own permission to manage users, directly or through a role', function (): void {
    $administracion = Role::create(['name' => 'Administración', 'guard_name' => 'web'])->givePermissionTo(PermisoUsuario::Gestionar->value);
    $porRol = cuentaDelPersonal()->assignRole($administracion);
    cuentaDelPersonal(PermisoUsuario::Gestionar->value);

    $this->actingAs($this->gestor);
    Livewire::test(EditUsuario::class, ['record' => $this->gestor->getRouteKey()])
        ->fillForm(['permisos' => ['usuarios' => []]])
        ->call('save')
        ->assertNotified('No puedes quitarte el permiso para gestionar usuarios.');

    $this->actingAs($porRol);
    Livewire::test(EditUsuario::class, ['record' => $porRol->getRouteKey()])
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertNotified('No puedes quitarte el permiso para gestionar usuarios.');

    expect(User::query()->findOrFail($this->gestor->id)->hasDirectPermission(PermisoUsuario::Gestionar->value))->toBeTrue()
        ->and(User::query()->findOrFail($porRol->id)->hasRole($administracion))->toBeTrue();
});

it('keeps at least one active account that manages users when a stale session deactivates or strips the last one', function (): void {
    $ultimo = cuentaDelPersonal(PermisoUsuario::Gestionar->value);
    // The actor's permissions were loaded before another request took its permission away.
    $this->gestor->load('permissions', 'roles');
    DB::table('model_has_permissions')->where(['model_id' => $this->gestor->id, 'permission_id' => DB::table('permissions')->where('name', PermisoUsuario::Gestionar->value)->value('id')])->delete();

    expect(fn () => app(DesactivarUsuario::class)($ultimo->id, 'Prueba.', $this->gestor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Debe quedar al menos una cuenta activa con permiso para gestionar usuarios.')
        ->and(fn () => app(GuardarUsuarioDelPersonal::class)(new DatosDeUsuarioDelPersonal('Último', null, [], []), $this->gestor, $ultimo->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Debe quedar al menos una cuenta activa con permiso para gestionar usuarios.');

    $ultimo = User::query()->findOrFail($ultimo->id);
    expect($ultimo->desactivado_en)->toBeNull()
        ->and($ultimo->hasDirectPermission(PermisoUsuario::Gestionar->value))->toBeTrue();
});

it('resends the link to set the password and resets the second factor of an account', function (): void {
    $cuenta = cuentaDelPersonal();
    $cuenta->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
    $this->actingAs($this->gestor);

    Livewire::test(ListUsuarios::class)
        ->callAction(TestAction::make('reenviarEnlace')->table($cuenta))
        ->assertNotified('Enlace enviado')
        ->callAction(TestAction::make('reiniciarDobleFactor')->table($cuenta))
        ->assertNotified('Doble factor reiniciado');

    Correos::assertSentTo($cuenta, CorreoDeRestablecimiento::class);
    expect(User::query()->findOrFail($cuenta->id)->getRawOriginal('app_authentication_secret'))->toBeNull();
});

it('forbids both screens to staff without the permission to manage users', function (): void {
    $this->actingAs(cuentaDelPersonal(...PermisoPago::valores()));

    $this->get(UsuarioResource::getUrl('index'))->assertForbidden();
    $this->get(UsuarioResource::getUrl('create'))->assertForbidden();
    $this->get(RolResource::getUrl('index'))->assertForbidden();
    $this->get(RolResource::getUrl('create'))->assertForbidden();
});

it('still lets the seeded demo administrator open the existing screens and the new ones', function (): void {
    $this->seed(DatabaseSeeder::class);
    $administrador = User::query()->where('email', 'admin@ciln.test')->sole();

    expect($administrador->can(PermisoUsuario::Gestionar->value))->toBeTrue();
    $this->actingAs($administrador);
    $this->get(FirmanteResource::getUrl('index'))->assertOk();
    $this->get(UsuarioResource::getUrl('index'))->assertOk();
    $this->get(RolResource::getUrl('index'))->assertOk();
});

it('creates or upgrades a full administrator from the console and emails the link', function (): void {
    $existente = User::factory()->create(['email' => 'dsti@ciln.test']);
    $existente->forceFill(['desactivado_en' => now()])->save();

    $this->artisan('personal:crear-administrador', ['correo' => 'nueva@ciln.test', 'nombre' => 'Administración CILN'])->assertExitCode(0);
    $this->artisan('personal:crear-administrador', ['correo' => 'DSTI@ciln.test', 'nombre' => 'Otro nombre'])->assertExitCode(0);

    $todos = DB::table('permissions')->orderBy('name')->pluck('name')->all();
    foreach (['nueva@ciln.test', 'dsti@ciln.test'] as $correo) {
        $usuario = User::query()->where('email', $correo)->sole();
        expect($usuario->getAllPermissions()->pluck('name')->sort()->values()->all())->toBe($todos)
            ->and($usuario->desactivado_en)->toBeNull();
        Correos::assertSentTo($usuario, CorreoDeRestablecimiento::class);
    }
    expect(User::query()->where('email', 'nueva@ciln.test')->value('name'))->toBe('Administración CILN')
        ->and(User::query()->findOrFail($existente->id)->name)->toBe($existente->name);
});

it('reports success from the console when the account is ready but the password link was sent moments ago', function (): void {
    $this->artisan('personal:crear-administrador', ['correo' => 'dsti@ciln.test', 'nombre' => 'DSTI'])->assertExitCode(0);
    User::query()->where('email', 'dsti@ciln.test')->sole()->revokePermissionTo(PermisoUsuario::Gestionar->value);

    $this->artisan('personal:crear-administrador', ['correo' => 'dsti@ciln.test', 'nombre' => 'DSTI'])
        ->expectsOutputToContain('no se pudo enviar el enlace')
        ->assertExitCode(0);

    expect(User::query()->where('email', 'dsti@ciln.test')->sole()->hasPermissionTo(PermisoUsuario::Gestionar->value))->toBeTrue();
});
