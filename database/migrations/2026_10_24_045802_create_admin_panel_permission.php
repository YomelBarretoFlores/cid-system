<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const string PERMISO = 'admin-panel.access';

    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'name' => self::PERMISO,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->where(['name' => self::PERMISO, 'guard_name' => 'web'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
