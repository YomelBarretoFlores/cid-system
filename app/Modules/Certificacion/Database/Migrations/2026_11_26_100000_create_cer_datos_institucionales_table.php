<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array TEXTOS = ['universidad', 'lema', 'centro', 'direccion', 'telefono', 'web', 'facebook', 'ciudad', 'formato_numero', 'formato_numero_constancia_notas'];

    public function up(): void
    {
        // One row: the texts every official document prints; the defaults keep issued documents looking as they did.
        Schema::create('cer_datos_institucionales', function (Blueprint $table): void {
            $table->smallInteger('id')->primary()->default(1);
            $table->string('universidad', 150);
            $table->string('lema', 150);
            $table->string('centro', 150);
            $table->string('direccion', 200);
            $table->string('telefono', 100);
            $table->string('web', 100);
            $table->string('facebook', 100);
            $table->string('ciudad', 60)->default('Huaraz');
            $table->string('formato_numero', 60)->default('N.º {numero:06}-{anio}');
            $table->string('formato_numero_constancia_notas', 60)->default('N° {anio} - {numero:05}');
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE cer_datos_institucionales ADD CONSTRAINT cer_datos_institucionales_unica_check CHECK (id = 1)');
        DB::statement('ALTER TABLE cer_datos_institucionales ADD CONSTRAINT cer_datos_institucionales_textos_check CHECK ('.implode(' AND ', array_map(fn (string $columna): string => "btrim({$columna}) <> ''", self::TEXTOS)).')');
        DB::statement("ALTER TABLE cer_datos_institucionales ADD CONSTRAINT cer_datos_institucionales_formatos_check CHECK (formato_numero ~ '\\{numero(:[0-9]+)?\\}' AND formato_numero_constancia_notas ~ '\\{numero(:[0-9]+)?\\}')");
        DB::statement('CREATE TRIGGER cer_datos_institucionales_auditoria AFTER INSERT OR UPDATE OR DELETE ON cer_datos_institucionales FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        DB::table('cer_datos_institucionales')->insert([
            'id' => 1,
            'universidad' => 'Universidad Nacional Santiago Antúnez de Mayolo',
            'lema' => 'Una Nueva Universidad para el Desarrollo',
            'centro' => 'Centro de Idiomas y Lenguas Nativas',
            'direccion' => 'Prolongación Simón Bolívar S/N – Huaraz',
            'telefono' => '043-640020 / Anexo 3105',
            'web' => 'cid.unasam.edu.pe',
            'facebook' => 'cidunasam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::unprepared(<<<'SQL'
            COMMENT ON TABLE cer_datos_institucionales IS 'Datos de la institución que imprimen los documentos oficiales (encabezado, pie, ciudad y formato del número); una sola fila.';
            COMMENT ON COLUMN cer_datos_institucionales.id IS 'Identificador de la única fila; siempre 1.';
            COMMENT ON COLUMN cer_datos_institucionales.universidad IS 'Nombre de la universidad en el encabezado y en el texto de la constancia de notas.';
            COMMENT ON COLUMN cer_datos_institucionales.lema IS 'Lema de la universidad bajo su nombre.';
            COMMENT ON COLUMN cer_datos_institucionales.centro IS 'Nombre del centro en el encabezado y en el texto de la constancia de notas.';
            COMMENT ON COLUMN cer_datos_institucionales.direccion IS 'Dirección del centro en el pie de página.';
            COMMENT ON COLUMN cer_datos_institucionales.telefono IS 'Teléfono y anexo del centro en el pie de página.';
            COMMENT ON COLUMN cer_datos_institucionales.web IS 'Sitio web del centro en el pie de página.';
            COMMENT ON COLUMN cer_datos_institucionales.facebook IS 'Página de Facebook del centro en el pie de página.';
            COMMENT ON COLUMN cer_datos_institucionales.ciudad IS 'Ciudad que precede a la fecha de emisión ("Huaraz, 16 de noviembre de 2026").';
            COMMENT ON COLUMN cer_datos_institucionales.formato_numero IS 'Formato del número anual de los documentos: {anio} es el año y {numero:N} el correlativo con N dígitos.';
            COMMENT ON COLUMN cer_datos_institucionales.formato_numero_constancia_notas IS 'Formato del número anual de la constancia de notas por nivel, con las mismas marcas que formato_numero.';
            COMMENT ON COLUMN cer_datos_institucionales.created_at IS 'Fecha y hora de creación de la fila.';
            COMMENT ON COLUMN cer_datos_institucionales.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cer_datos_institucionales');
    }
};
