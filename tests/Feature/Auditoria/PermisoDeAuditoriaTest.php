<?php

declare(strict_types=1);

use App\Shared\Audit\Enums\PermisoAuditoria;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('grants the audit permission to the active accounts that already managed users when it was created', function (): void {
    $directo = User::factory()->create()->givePermissionTo(PermisoUsuario::Gestionar->value);
    $porRol = User::factory()->create()->assignRole(Role::create(['name' => 'Gestión', 'guard_name' => 'web'])->givePermissionTo(PermisoUsuario::Gestionar->value));
    $desactivado = User::factory()->create(['desactivado_en' => now()])->givePermissionTo(PermisoUsuario::Gestionar->value);
    $otro = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    (require base_path('app/Shared/Audit/Database/Migrations/2026_11_02_110100_create_auditoria_permission.php'))->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $tienePermiso = fn (User $usuario): bool => User::query()->findOrFail($usuario->id)->hasDirectPermission(PermisoAuditoria::Ver->value);

    expect($tienePermiso($directo))->toBeTrue()
        ->and($tienePermiso($porRol))->toBeTrue()
        ->and($tienePermiso($desactivado))->toBeFalse()
        ->and($tienePermiso($otro))->toBeFalse();
});
