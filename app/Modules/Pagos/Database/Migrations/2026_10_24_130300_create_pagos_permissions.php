<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const array PERMISOS = ['pagos.ver', 'pagos.registrar', 'pagos.validar', 'pagos.anular'];

    public function up(): void
    {
        $ahora = now();

        DB::table('permissions')->insertOrIgnore(array_map(fn (string $nombre): array => [
            'name' => $nombre,
            'guard_name' => 'web',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], self::PERMISOS));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->where('guard_name', 'web')->whereIn('name', self::PERMISOS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
