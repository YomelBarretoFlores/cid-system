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
        Schema::create('aca_grupos', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('periodo_id')->constrained('aca_periodos')->restrictOnDelete();
            $table->foreignId('ciclo_id')->constrained('aca_ciclos')->restrictOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('aca_aulas')->restrictOnDelete();
            $table->string('codigo', 20);
            $table->enum('modalidad', ['presencial', 'virtual', 'semipresencial']);
            $table->boolean('es_acelerado')->default(false);
            $table->string('enlace_virtual')->nullable();
            $table->smallInteger('cupo_maximo');
            $table->smallInteger('cupo_minimo');
            $table->smallInteger('matriculados')->default(0);
            $table->enum('estado', ['programado', 'abierto', 'cerrado', 'cancelado', 'finalizado'])->default('programado');
            $table->timestampsTz();

            $table->unique(['periodo_id', 'codigo']);
            $table->index(['periodo_id', 'estado']);
            $table->index('ciclo_id');
            $table->index('aula_id');
        });

        DB::statement('ALTER TABLE aca_grupos ADD CONSTRAINT aca_grupos_cupos_check CHECK (cupo_maximo > 0 AND cupo_minimo >= 0 AND cupo_minimo <= cupo_maximo)');
        DB::statement('ALTER TABLE aca_grupos ADD CONSTRAINT aca_grupos_matriculados_check CHECK (matriculados >= 0 AND matriculados <= cupo_maximo)');

        Schema::create('aca_horarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->constrained('aca_grupos')->cascadeOnDelete();
            $table->smallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestampsTz();

            $table->unique(['grupo_id', 'dia_semana', 'hora_inicio']);
        });

        DB::statement('ALTER TABLE aca_horarios ADD CONSTRAINT aca_horarios_dia_semana_check CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE aca_horarios ADD CONSTRAINT aca_horarios_horas_check CHECK (hora_fin > hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_horarios');
        Schema::dropIfExists('aca_grupos');
    }
};
