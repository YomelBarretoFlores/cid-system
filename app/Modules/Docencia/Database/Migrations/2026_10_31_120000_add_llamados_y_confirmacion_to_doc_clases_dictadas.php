<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array RESTRICCIONES = [
        'doc_clases_dictadas' => [
            'cierre_tras_inicio' => 'llamado_cierre_en IS NULL OR llamado_inicio_en IS NOT NULL',
            'cierre_y_confirmacion' => '(llamado_cierre_en IS NULL) = (confirmacion IS NULL)',
            'posterior_sin_llamados' => 'NOT registro_posterior OR (llamado_inicio_en IS NULL AND llamado_cierre_en IS NULL)',
            'revision_del_delegado' => "(coalesce(confirmacion, 'pendiente') IN ('confirmada', 'observada')) = (confirmada_por_matricula_id IS NOT NULL AND confirmada_en IS NOT NULL)",
            'observacion_requerida' => "confirmacion IS DISTINCT FROM 'observada' OR btrim(coalesce(observacion, '')) <> ''",
        ],
        'doc_asistencias' => [
            'cierre_de_presentes' => "estado_cierre IS NULL OR estado <> 'falta'",
        ],
    ];

    public function up(): void
    {
        // Rows saved before this change keep both calls empty and registro_posterior false: they read as "registro anterior".
        Schema::table('doc_clases_dictadas', function (Blueprint $table): void {
            $table->timestampTz('llamado_inicio_en')->nullable();
            $table->timestampTz('llamado_cierre_en')->nullable();
            $table->boolean('registro_posterior')->default(false);
            $table->enum('confirmacion', ['pendiente', 'confirmada', 'observada'])->nullable();
            $table->foreignId('confirmada_por_matricula_id')->nullable()->constrained('mat_matriculas')->restrictOnDelete();
            $table->timestampTz('confirmada_en')->nullable();
            $table->text('observacion')->nullable();

            $table->index('confirmada_por_matricula_id');
            $table->index('confirmacion');
        });

        Schema::table('doc_asistencias', function (Blueprint $table): void {
            $table->enum('estado_cierre', ['presente', 'falta'])->nullable();
        });

        foreach (self::RESTRICCIONES as $tabla => $reglas) {
            foreach ($reglas as $regla => $condicion) {
                DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_{$regla}_check CHECK ({$condicion})");
            }
        }
    }

    public function down(): void
    {
        foreach (self::RESTRICCIONES as $tabla => $reglas) {
            foreach (array_keys($reglas) as $regla) {
                DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT IF EXISTS {$tabla}_{$regla}_check");
            }
        }

        Schema::table('doc_asistencias', function (Blueprint $table): void {
            $table->dropColumn('estado_cierre');
        });

        Schema::table('doc_clases_dictadas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('confirmada_por_matricula_id');
            $table->dropIndex(['confirmacion']);
            $table->dropColumn(['llamado_inicio_en', 'llamado_cierre_en', 'registro_posterior', 'confirmacion', 'confirmada_en', 'observacion']);
        });
    }
};
