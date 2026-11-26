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
        Schema::table('aca_periodos', function (Blueprint $table): void {
            $table->date('matricula_extemporanea_fin')->nullable()->after('matricula_fin');
        });

        // Same upper bound as the regular window: enrollment never runs past the end of classes.
        DB::statement('ALTER TABLE aca_periodos ADD CONSTRAINT aca_periodos_matricula_extemporanea_check CHECK (matricula_extemporanea_fin IS NULL OR (matricula_extemporanea_fin > matricula_fin AND matricula_extemporanea_fin <= fecha_fin))');
        DB::statement("COMMENT ON COLUMN aca_periodos.matricula_extemporanea_fin IS 'Último día de la matrícula extemporánea, posterior al fin de la matrícula regular; vacío si el periodo no la tiene. El pago de matrícula en ese plazo lleva el recargo de la configuración de pagos.'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE aca_periodos DROP CONSTRAINT IF EXISTS aca_periodos_matricula_extemporanea_check');

        Schema::table('aca_periodos', function (Blueprint $table): void {
            $table->dropColumn('matricula_extemporanea_fin');
        });
    }
};
