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
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('pag_conceptos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 150);
            $table->enum('tipo', ['matricula', 'pension', 'examen_clasificacion', 'certificado', 'constancia', 'tramite', 'otro']);
            $table->enum('fuente', ['tupa', 'tusne']);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();

            $table->index('tipo');
        });

        Schema::create('pag_tarifas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('concepto_id')->constrained('pag_conceptos')->restrictOnDelete();
            $table->enum('tipo_vinculo', ['estudiante_unasam', 'docente_administrativo_unasam', 'publico_general']);
            $table->decimal('monto', 10, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE pag_tarifas ADD CONSTRAINT pag_tarifas_monto_check CHECK (monto > 0)');
        DB::statement('ALTER TABLE pag_tarifas ADD CONSTRAINT pag_tarifas_vigencia_check CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
        DB::statement(<<<'SQL'
            ALTER TABLE pag_tarifas ADD CONSTRAINT pag_tarifas_sin_superposicion EXCLUDE USING gist (
                concepto_id WITH =,
                tipo_vinculo WITH =,
                daterange(vigente_desde, vigente_hasta, '[]') WITH &&
            )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pag_tarifas');
        Schema::dropIfExists('pag_conceptos');
    }
};
