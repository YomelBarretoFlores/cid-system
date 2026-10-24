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
        Schema::create('aca_idiomas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('nombre', 80)->unique();
            $table->boolean('es_lengua_nativa')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
        });

        Schema::create('aca_ciclos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idioma_id')->constrained('aca_idiomas')->restrictOnDelete();
            $table->smallInteger('numero');
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado']);
            $table->string('nombre', 60);
            $table->smallInteger('horas_lectivas');
            $table->boolean('activo')->default(true);
            $table->timestampsTz();

            $table->unique(['idioma_id', 'numero']);
        });

        DB::statement('ALTER TABLE aca_ciclos ADD CONSTRAINT aca_ciclos_numero_check CHECK (numero BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE aca_ciclos ADD CONSTRAINT aca_ciclos_horas_lectivas_check CHECK (horas_lectivas > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_ciclos');
        Schema::dropIfExists('aca_idiomas');
    }
};
