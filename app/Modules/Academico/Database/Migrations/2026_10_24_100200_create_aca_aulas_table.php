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
        Schema::create('aca_aulas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 80);
            $table->smallInteger('capacidad');
            $table->string('ubicacion', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE aca_aulas ADD CONSTRAINT aca_aulas_capacidad_check CHECK (capacidad > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_aulas');
    }
};
