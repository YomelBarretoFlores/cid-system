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
        Schema::create('aca_programas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 80)->unique();
            $table->string('regimen', 10);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE aca_programas ADD CONSTRAINT aca_programas_regimen_valido_check CHECK (regimen IN ('regular', 'acelerado', 'ambos'))");

        $ahora = now();
        DB::table('aca_programas')->insert([
            ['codigo' => 'JA', 'nombre' => 'Jóvenes y Adultos', 'regimen' => 'ambos', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['codigo' => 'NINOS', 'nombre' => 'Niños', 'regimen' => 'ambos', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['codigo' => 'POSGRADO', 'nombre' => 'Posgrado', 'regimen' => 'acelerado', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        DB::unprepared(<<<'SQL'
            COMMENT ON TABLE aca_programas IS 'Programas del centro (Jóvenes y Adultos, Niños, Posgrado) con el régimen de ciclo que admiten sus grupos.';
            COMMENT ON COLUMN aca_programas.id IS 'Identificador interno.';
            COMMENT ON COLUMN aca_programas.codigo IS 'Código único del programa, en mayúsculas.';
            COMMENT ON COLUMN aca_programas.nombre IS 'Nombre único del programa, como aparece en los documentos.';
            COMMENT ON COLUMN aca_programas.regimen IS 'Régimen de ciclo que admiten sus grupos. Valores: regular, acelerado, ambos.';
            COMMENT ON COLUMN aca_programas.activo IS 'Indica si el programa está disponible para nuevos grupos.';
            COMMENT ON COLUMN aca_programas.created_at IS 'Fecha y hora de registro.';
            COMMENT ON COLUMN aca_programas.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_programas');
    }
};
