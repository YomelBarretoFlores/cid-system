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
        Schema::create('aca_esquemas_evaluacion', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100);
            $table->foreignId('idioma_id')->nullable()->constrained('aca_idiomas')->restrictOnDelete();
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado'])->nullable();
            $table->decimal('nota_maxima', 4, 2)->default(20);
            $table->decimal('nota_minima_aprobatoria', 4, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestampsTz();

            $table->index(['idioma_id', 'nivel', 'vigente_desde']);
        });

        DB::statement('ALTER TABLE aca_esquemas_evaluacion ADD CONSTRAINT aca_esquemas_evaluacion_notas_check CHECK (nota_minima_aprobatoria > 0 AND nota_minima_aprobatoria <= nota_maxima)');
        DB::statement('ALTER TABLE aca_esquemas_evaluacion ADD CONSTRAINT aca_esquemas_evaluacion_vigencia_check CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');

        Schema::create('aca_componentes_evaluacion', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('esquema_id')->constrained('aca_esquemas_evaluacion')->cascadeOnDelete();
            $table->string('codigo', 30);
            $table->string('nombre', 80);
            $table->decimal('peso', 5, 2);
            $table->smallInteger('orden');
            $table->timestampsTz();

            $table->unique(['esquema_id', 'codigo']);
        });

        DB::statement('ALTER TABLE aca_componentes_evaluacion ADD CONSTRAINT aca_componentes_evaluacion_peso_check CHECK (peso > 0 AND peso <= 100)');
        DB::statement('ALTER TABLE aca_componentes_evaluacion ADD CONSTRAINT aca_componentes_evaluacion_orden_check CHECK (orden > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_componentes_evaluacion');
        Schema::dropIfExists('aca_esquemas_evaluacion');
    }
};
