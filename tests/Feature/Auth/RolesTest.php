<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\PermisoPago;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Filament\Resources\Roles\Pages\CreateRol;
use App\Shared\Auth\Filament\Resources\Roles\Pages\EditRol;
use App\Shared\Auth\Filament\Resources\Roles\Pages\ListRoles;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoUsuario::Gestionar->value]);
    $this->actingAs($this->gestor);
});

it('creates a role with its permissions and records the grant in the audit trail', function (): void {
    Livewire::test(CreateRol::class)
        ->assertFormFieldDoesNotExist('permisos.admin-panel')
        ->fillForm(['name' => 'Caja', 'permisos' => ['pagos' => [PermisoPago::Ver->value, PermisoPago::Registrar->value]]])
        ->call('create')
        ->assertHasNoFormErrors();

    $rol = Role::findByName('Caja', 'web');

    expect($rol->permissions->pluck('name')->sort()->values()->all())->toBe([PermisoPago::Registrar->value, PermisoPago::Ver->value])
        ->and(DB::table('aud_registros')->where(['tabla' => 'roles', 'registro_id' => $rol->id, 'operacion' => 'INSERT'])->exists())->toBeTrue()
        ->and(DB::table('aud_registros')->where(['tabla' => 'role_has_permissions', 'operacion' => 'INSERT'])->whereRaw("datos_nuevos->>'role_id' = ?", [(string) $rol->id])->count())->toBe(2);
});

it('records in the audit trail a permission or role given to an account and taken away', function (): void {
    $cuenta = User::factory()->create();
    $rol = Role::create(['name' => 'Caja', 'guard_name' => 'web']);

    $cuenta->givePermissionTo(PermisoPago::Ver->value)->assignRole($rol);
    $cuenta->revokePermissionTo(PermisoPago::Ver->value);

    $operaciones = fn (string $tabla): array => DB::table('aud_registros')->where('tabla', $tabla)
        ->whereRaw("coalesce(datos_nuevos, datos_anteriores)->>'model_id' = ?", [(string) $cuenta->id])->orderBy('id')->pluck('operacion')->all();

    expect($operaciones('model_has_permissions'))->toBe(['INSERT', 'DELETE'])
        ->and($operaciones('model_has_roles'))->toBe(['INSERT']);
});

it('refuses a role name already in use', function (): void {
    Role::create(['name' => 'Caja', 'guard_name' => 'web']);

    Livewire::test(CreateRol::class)
        ->fillForm(['name' => 'Caja'])
        ->call('create')
        ->assertHasFormErrors(['name']);

    expect(Role::query()->where('name', 'Caja')->count())->toBe(1);
});

it('saves the permissions changed on the edit page', function (): void {
    $rol = Role::create(['name' => 'Caja', 'guard_name' => 'web'])->givePermissionTo(PermisoPago::Ver->value);

    Livewire::test(EditRol::class, ['record' => $rol->getRouteKey()])
        ->assertFormSet(['name' => 'Caja', 'permisos.pagos' => [PermisoPago::Ver->value]])
        ->fillForm(['name' => 'Caja central', 'permisos' => ['pagos' => [PermisoPago::Validar->value]]])
        ->call('save')
        ->assertHasNoFormErrors();

    $rol = Role::query()->findOrFail($rol->id);
    expect($rol->name)->toBe('Caja central')
        ->and($rol->permissions->pluck('name')->all())->toBe([PermisoPago::Validar->value]);
});

it('deletes a role only when no account has it', function (): void {
    $asignado = Role::create(['name' => 'Asignado', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($asignado);
    $libre = Role::create(['name' => 'Libre', 'guard_name' => 'web']);

    Livewire::test(ListRoles::class)
        ->callAction(TestAction::make('eliminar')->table($asignado))
        ->assertNotified('No puedes eliminar un rol asignado a usuarios. Quítalo primero de sus cuentas.');

    Livewire::test(ListRoles::class)
        ->callAction(TestAction::make('eliminar')->table($libre))
        ->assertNotified('Rol eliminado');

    expect(Role::query()->whereKey($asignado->id)->exists())->toBeTrue()
        ->and(Role::query()->whereKey($libre->id)->exists())->toBeFalse();
});

it('refuses to take the permission to manage users away from a role you depend on', function (): void {
    $administracion = Role::create(['name' => 'Administración', 'guard_name' => 'web'])->givePermissionTo(PermisoUsuario::Gestionar->value);
    $porRol = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION)->assignRole($administracion);
    $this->actingAs($porRol);

    Livewire::test(EditRol::class, ['record' => $administracion->getRouteKey()])
        ->fillForm(['permisos' => ['usuarios' => []]])
        ->call('save')
        ->assertNotified('No puedes quitarte el permiso para gestionar usuarios.');

    expect(Role::query()->findOrFail($administracion->id)->hasPermissionTo(PermisoUsuario::Gestionar->value))->toBeTrue();
});
