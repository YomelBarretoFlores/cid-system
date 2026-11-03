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
        Schema::table('mat_habilitaciones', function (Blueprint $table): void {
            $table->string('motivo', 500)->nullable();
        });

        DB::statement("ALTER TABLE mat_habilitaciones ADD CONSTRAINT mat_habilitaciones_motivo_check CHECK ((origen = 'ajuste_administrativo') = (motivo IS NOT NULL) AND (motivo IS NULL OR char_length(motivo) BETWEEN 5 AND 500))");

        Schema::table('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->foreignId('corregido_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('corregido_en')->nullable();
            $table->string('motivo_correccion', 500)->nullable();
            $table->index('corregido_por');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE mat_solicitudes_clasificacion ADD CONSTRAINT mat_solicitudes_clasificacion_correccion_check CHECK (
                (corregido_en IS NULL) = (corregido_por IS NULL)
                AND (corregido_en IS NULL) = (motivo_correccion IS NULL)
                AND (corregido_en IS NULL OR estado = 'evaluado')
                AND (motivo_correccion IS NULL OR char_length(motivo_correccion) BETWEEN 5 AND 500)
            )
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE mat_solicitudes_clasificacion DROP CONSTRAINT mat_solicitudes_clasificacion_correccion_check');

        Schema::table('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('corregido_por');
            $table->dropColumn(['corregido_en', 'motivo_correccion']);
        });

        DB::statement('ALTER TABLE mat_habilitaciones DROP CONSTRAINT mat_habilitaciones_motivo_check');

        Schema::table('mat_habilitaciones', function (Blueprint $table): void {
            $table->dropColumn('motivo');
        });
    }
};
