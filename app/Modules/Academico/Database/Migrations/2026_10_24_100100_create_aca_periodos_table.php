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
        Schema::create('aca_periodos', function (Blueprint $table): void {
            $table->id();
            $table->smallInteger('anio');
            $table->smallInteger('numero');
            $table->string('nombre', 60);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('matricula_inicio');
            $table->date('matricula_fin');
            $table->enum('estado', ['planificado', 'matricula_abierta', 'en_curso', 'cerrado'])->default('planificado');
            $table->timestampsTz();

            $table->unique(['anio', 'numero']);
            $table->index('estado');
        });

        DB::statement('ALTER TABLE aca_periodos ADD CONSTRAINT aca_periodos_anio_check CHECK (anio BETWEEN 2000 AND 2100)');
        DB::statement('ALTER TABLE aca_periodos ADD CONSTRAINT aca_periodos_numero_check CHECK (numero BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE aca_periodos ADD CONSTRAINT aca_periodos_fechas_check CHECK (fecha_fin > fecha_inicio)');
        DB::statement('ALTER TABLE aca_periodos ADD CONSTRAINT aca_periodos_matricula_check CHECK (matricula_fin >= matricula_inicio AND matricula_fin <= fecha_fin)');
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_periodos');
    }
};
