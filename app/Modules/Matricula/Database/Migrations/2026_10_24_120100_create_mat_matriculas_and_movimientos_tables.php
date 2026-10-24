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
        Schema::create('mat_matriculas', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->foreignId('grupo_id')->constrained('aca_grupos')->restrictOnDelete();
            $table->enum('condicion', ['regular', 'repitente']);
            $table->enum('estado', ['activa', 'retirada'])->default('activa');
            $table->timestampTz('matriculado_en')->useCurrent();
            $table->timestampTz('retirado_en')->nullable();
            $table->timestampsTz();

            $table->index('persona_id');
            $table->index(['grupo_id', 'estado']);
        });

        DB::statement("ALTER TABLE mat_matriculas ADD CONSTRAINT mat_matriculas_retiro_check CHECK ((estado = 'retirada') = (retirado_en IS NOT NULL))");
        DB::statement("CREATE UNIQUE INDEX mat_matriculas_activa_unique ON mat_matriculas (persona_id, grupo_id) WHERE estado = 'activa'");

        Schema::create('mat_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('matricula_id')->constrained('mat_matriculas')->restrictOnDelete();
            $table->enum('tipo', ['traslado', 'retiro']);
            $table->foreignId('grupo_origen_id')->constrained('aca_grupos')->restrictOnDelete();
            $table->foreignId('grupo_destino_id')->nullable()->constrained('aca_grupos')->restrictOnDelete();
            $table->text('motivo');
            $table->foreignId('autorizado_por')->constrained('users')->restrictOnDelete();
            $table->timestampTz('efectuado_en')->useCurrent();
            $table->timestampsTz();

            $table->index('matricula_id');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE mat_movimientos ADD CONSTRAINT mat_movimientos_destino_check CHECK (
                (tipo = 'traslado' AND grupo_destino_id IS NOT NULL AND grupo_destino_id <> grupo_origen_id)
                OR (tipo = 'retiro' AND grupo_destino_id IS NULL)
            )
            SQL);
        DB::statement('ALTER TABLE mat_movimientos ADD CONSTRAINT mat_movimientos_motivo_check CHECK (length(trim(motivo)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('mat_movimientos');
        Schema::dropIfExists('mat_matriculas');
    }
};
