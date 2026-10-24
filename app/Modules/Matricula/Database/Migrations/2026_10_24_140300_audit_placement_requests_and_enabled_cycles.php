<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER mat_solicitudes_clasificacion_auditoria AFTER INSERT OR UPDATE OR DELETE ON mat_solicitudes_clasificacion
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            CREATE TRIGGER mat_habilitaciones_auditoria AFTER INSERT OR UPDATE OR DELETE ON mat_habilitaciones
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS mat_habilitaciones_auditoria ON mat_habilitaciones;
            DROP TRIGGER IF EXISTS mat_solicitudes_clasificacion_auditoria ON mat_solicitudes_clasificacion;
            SQL);
    }
};
