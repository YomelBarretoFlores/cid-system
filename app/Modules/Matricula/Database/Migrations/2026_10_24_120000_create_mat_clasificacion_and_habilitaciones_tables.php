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
        Schema::create('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->foreignId('idioma_id')->constrained('aca_idiomas')->restrictOnDelete();
            $table->enum('estado', ['pendiente_evaluacion', 'evaluado', 'expirado'])->default('pendiente_evaluacion');
            $table->date('vence_en');
            $table->date('fecha_examen')->nullable();
            $table->decimal('puntaje', 5, 2)->nullable();
            $table->foreignId('ciclo_asignado_id')->nullable();
            $table->foreignId('evaluado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('evaluado_en')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestampsTz();

            $table->foreign(['ciclo_asignado_id', 'idioma_id'], 'mat_solicitudes_clasificacion_ciclo_fk')->references(['id', 'idioma_id'])->on('aca_ciclos')->restrictOnDelete();
            $table->index(['estado', 'vence_en']);
            $table->index('persona_id');
        });

        DB::statement('ALTER TABLE mat_solicitudes_clasificacion ADD CONSTRAINT mat_solicitudes_clasificacion_puntaje_check CHECK (puntaje IS NULL OR puntaje BETWEEN 0 AND 100)');
        DB::statement(<<<'SQL'
            ALTER TABLE mat_solicitudes_clasificacion ADD CONSTRAINT mat_solicitudes_clasificacion_evaluado_check CHECK (
                estado <> 'evaluado'
                OR (puntaje IS NOT NULL AND ciclo_asignado_id IS NOT NULL AND fecha_examen IS NOT NULL
                    AND evaluado_por IS NOT NULL AND evaluado_en IS NOT NULL)
            )
            SQL);
        DB::statement("CREATE UNIQUE INDEX mat_solicitudes_clasificacion_pendiente_unique ON mat_solicitudes_clasificacion (persona_id, idioma_id) WHERE estado = 'pendiente_evaluacion'");

        Schema::create('mat_habilitaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->foreignId('idioma_id')->constrained('aca_idiomas')->restrictOnDelete();
            $table->foreignId('ciclo_id');
            $table->enum('origen', ['clasificacion', 'aprobacion', 'ajuste_administrativo']);
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->foreign(['ciclo_id', 'idioma_id'], 'mat_habilitaciones_ciclo_fk')->references(['id', 'idioma_id'])->on('aca_ciclos')->restrictOnDelete();
            $table->unique(['persona_id', 'idioma_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mat_habilitaciones');
        Schema::dropIfExists('mat_solicitudes_clasificacion');
    }
};
