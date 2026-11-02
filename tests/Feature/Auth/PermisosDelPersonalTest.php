<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\PermisoPago;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\CatalogoDePermisos;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('labels in Spanish every permission the migrations create', function (): void {
    $sinEtiqueta = DB::table('permissions')->pluck('name')
        ->reject(fn (string $permiso): bool => CatalogoDePermisos::etiqueta($permiso) !== null)
        ->values()
        ->all();

    expect($sinEtiqueta)->toBe([]);
});

it('grants the permission to manage users to the accounts that already held every permission when it was created', function (): void {
    $gestionar = DB::table('permissions')->where('name', PermisoUsuario::Gestionar->value)->value('id');
    $resto = DB::table('permissions')->where('id', '!=', $gestionar)->pluck('name')->all();
    $directo = User::factory()->create()->givePermissionTo($resto);
    $porRol = User::factory()->create()->assignRole(Role::create(['name' => 'Todo', 'guard_name' => 'web'])->givePermissionTo($resto))->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $incompleto = User::factory()->create()->givePermissionTo(array_values(array_diff($resto, [PermisoPago::Anular->value])));

    (require base_path('app/Shared/Auth/Database/Migrations/2026_11_02_100200_create_usuarios_permission.php'))->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(User::query()->findOrFail($directo->id)->hasDirectPermission(PermisoUsuario::Gestionar->value))->toBeTrue()
        ->and(User::query()->findOrFail($porRol->id)->hasDirectPermission(PermisoUsuario::Gestionar->value))->toBeTrue()
        ->and(User::query()->findOrFail($incompleto->id)->can(PermisoUsuario::Gestionar->value))->toBeFalse();
});
