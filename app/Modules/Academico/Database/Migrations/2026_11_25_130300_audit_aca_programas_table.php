<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // A program's regimen decides which groups are allowed, so its changes are audited like the rest of the academic offer.
    public function up(): void
    {
        DB::unprepared('CREATE TRIGGER aca_programas_auditoria AFTER INSERT OR UPDATE OR DELETE ON aca_programas FOR EACH ROW EXECUTE FUNCTION aud_registrar()');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS aca_programas_auditoria ON aca_programas');
    }
};
