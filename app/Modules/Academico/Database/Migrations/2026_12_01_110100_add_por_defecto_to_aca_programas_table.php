<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_programas', function (Blueprint $table): void {
            $table->boolean('por_defecto')->default(false);
        });

        // Groups belonged to Jóvenes y Adultos before programs existed, so it starts as the default.
        DB::table('aca_programas')->where('codigo', 'JA')->update(['por_defecto' => true, 'activo' => true]);
        DB::statement('ALTER TABLE aca_programas ADD CONSTRAINT aca_programas_por_defecto_activo_check CHECK (NOT por_defecto OR activo)');
        DB::statement('CREATE UNIQUE INDEX aca_programas_por_defecto_unique ON aca_programas (por_defecto) WHERE por_defecto');
        DB::statement("COMMENT ON COLUMN aca_programas.por_defecto IS 'Programa que se propone para grupos nuevos y que se imprime en los documentos de grupos anteriores a los programas; solo uno a la vez y siempre activo.'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX aca_programas_por_defecto_unique');
        DB::statement('ALTER TABLE aca_programas DROP CONSTRAINT aca_programas_por_defecto_activo_check');

        Schema::table('aca_programas', function (Blueprint $table): void {
            $table->dropColumn('por_defecto');
        });
    }
};
