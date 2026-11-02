<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // tipo_vinculo in per_personas sets the fee each person pays.
    private const array TABLAS = ['per_personas', 'aca_periodos', 'aca_grupos', 'aca_horarios', 'aca_aulas', 'aca_idiomas', 'aca_ciclos'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            DB::unprepared("CREATE TRIGGER {$tabla}_auditoria AFTER INSERT OR UPDATE OR DELETE ON {$tabla} FOR EACH ROW EXECUTE FUNCTION aud_registrar()");
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabla}_auditoria ON {$tabla}");
        }
    }
};
