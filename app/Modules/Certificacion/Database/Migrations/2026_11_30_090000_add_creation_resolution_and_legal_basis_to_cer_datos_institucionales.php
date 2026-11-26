<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The level certificate prints the center's creation resolution and the accreditation its legal basis, both as the real CILN formats do.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cer_datos_institucionales', function (Blueprint $table): void {
            $table->string('resolucion_creacion', 200)->default('Creado por R.R. N° 860-80-UNASAM, del 5 de Setiembre de 1980');
            $table->string('base_legal_acreditacion', 300)->default('(De conformidad con la Normatividad legal. Ley 30220, Art. 43 y Reglamento de Suficiencia de Idioma Extranjero)');
        });

        DB::statement("ALTER TABLE cer_datos_institucionales ADD CONSTRAINT cer_datos_institucionales_leyendas_check CHECK (btrim(resolucion_creacion) <> '' AND btrim(base_legal_acreditacion) <> '')");

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN cer_datos_institucionales.resolucion_creacion IS 'Resolución de creación del centro que imprime el certificado de nivel bajo su nombre.';
            COMMENT ON COLUMN cer_datos_institucionales.base_legal_acreditacion IS 'Base legal que imprime la acreditación de conocimiento de idioma extranjero bajo su título.';
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cer_datos_institucionales DROP CONSTRAINT cer_datos_institucionales_leyendas_check');

        Schema::table('cer_datos_institucionales', function (Blueprint $table): void {
            $table->dropColumn(['resolucion_creacion', 'base_legal_acreditacion']);
        });
    }
};
