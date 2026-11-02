<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const string PERMISO = 'auditoria.ver';

    private const string GESTIONAR_USUARIOS = 'usuarios.gestionar';

    private const string USUARIO = User::class;

    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'name' => self::PERMISO,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permisoId = DB::table('permissions')->where(['name' => self::PERMISO, 'guard_name' => 'web'])->value('id');
        $gestionarId = DB::table('permissions')->where(['name' => self::GESTIONAR_USUARIOS, 'guard_name' => 'web'])->value('id');

        // Whoever manages staff accounts today can read the audit trail; others receive it from them.
        DB::insert(<<<'SQL'
            INSERT INTO model_has_permissions (permission_id, model_type, model_id)
            SELECT ?, ?, u.id FROM users u
            WHERE u.desactivado_en IS NULL
              AND (
                EXISTS (SELECT 1 FROM model_has_permissions mp WHERE mp.permission_id = ? AND mp.model_type = ? AND mp.model_id = u.id)
                OR EXISTS (
                  SELECT 1 FROM model_has_roles mr JOIN role_has_permissions rp ON rp.role_id = mr.role_id
                  WHERE mr.model_type = ? AND mr.model_id = u.id AND rp.permission_id = ?
                )
              )
            ON CONFLICT DO NOTHING
            SQL, [$permisoId, self::USUARIO, $gestionarId, self::USUARIO, self::USUARIO, $gestionarId]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->where(['name' => self::PERMISO, 'guard_name' => 'web'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
