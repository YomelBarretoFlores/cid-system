<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Its own table, not columns of cer_datos_institucionales: these texts are frozen into each document when issued, while the institutional data are read again on every reprint.
return new class extends Migration
{
    private const array TEXTOS = [
        'cierre_constancia_matricula', 'cierre_constancia_notas', 'escala_notas', 'programa_acreditacion', 'suficiencia_acreditacion',
        'constancia_acta_acreditacion', 'validez_acreditacion', 'texto_certificado',
    ];

    public function up(): void
    {
        Schema::create('cer_textos_documentos', function (Blueprint $table): void {
            $table->smallInteger('id')->primary()->default(1);
            $table->string('cierre_constancia_matricula', 300);
            $table->string('cierre_constancia_notas', 300);
            $table->string('escala_notas', 200);
            $table->string('programa_acreditacion', 150);
            $table->string('suficiencia_acreditacion', 150);
            $table->string('constancia_acta_acreditacion', 300);
            $table->string('validez_acreditacion', 300);
            $table->string('texto_certificado', 500);
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE cer_textos_documentos ADD CONSTRAINT cer_textos_documentos_unica_check CHECK (id = 1)');
        DB::statement('ALTER TABLE cer_textos_documentos ADD CONSTRAINT cer_textos_documentos_textos_check CHECK ('.implode(' AND ', array_map(fn (string $columna): string => "btrim({$columna}) <> ''", self::TEXTOS)).')');
        DB::statement("ALTER TABLE cer_textos_documentos ADD CONSTRAINT cer_textos_documentos_marcas_check CHECK (strpos(validez_acreditacion, '{mencion}') > 0 AND strpos(texto_certificado, '{nivel}') > 0 AND strpos(texto_certificado, '{idioma}') > 0)");
        DB::statement('CREATE TRIGGER cer_textos_documentos_auditoria AFTER INSERT OR UPDATE OR DELETE ON cer_textos_documentos FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        DB::table('cer_textos_documentos')->insert([
            'id' => 1,
            'cierre_constancia_matricula' => 'Se expide la presente a solicitud de la persona interesada, para los fines que estime conveniente.',
            'cierre_constancia_notas' => 'Se expide la presente a solicitud de la persona interesada, para los fines que estime conveniente.',
            'escala_notas' => 'Escala vigesimal de 0 a 20.',
            'programa_acreditacion' => 'Programa de Enseñanza de Idioma Extranjero',
            'suficiencia_acreditacion' => 'Examen de Suficiencia',
            'constancia_acta_acreditacion' => 'Así consta en el Acta Final correspondiente, disponible para verificación en caso de estimarse necesario.',
            'validez_acreditacion' => 'Válido para obtener el Grado de {mencion}.',
            'texto_certificado' => 'Por haber aprobado satisfactoriamente el nivel {nivel} del idioma {idioma} desarrollado en el Centro de Idiomas de la UNASAM; haciendo un total de {horas}.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::unprepared(<<<'SQL'
            COMMENT ON TABLE cer_textos_documentos IS 'Textos que imprimen las constancias, la acreditación y el certificado; cada documento copia los vigentes al emitirse, así que editarlos no cambia los ya emitidos. Una sola fila.';
            COMMENT ON COLUMN cer_textos_documentos.id IS 'Identificador de la única fila; siempre 1.';
            COMMENT ON COLUMN cer_textos_documentos.cierre_constancia_matricula IS 'Frase final de la constancia de matrícula y de la constancia de inscripción.';
            COMMENT ON COLUMN cer_textos_documentos.cierre_constancia_notas IS 'Frase final de la constancia de notas del idioma.';
            COMMENT ON COLUMN cer_textos_documentos.escala_notas IS 'Nota de la escala de calificación al pie de la tabla de notas.';
            COMMENT ON COLUMN cer_textos_documentos.programa_acreditacion IS 'Modalidad que imprime la acreditación cuando el nivel se aprobó llevando ciclos.';
            COMMENT ON COLUMN cer_textos_documentos.suficiencia_acreditacion IS 'Modalidad que imprime la acreditación cuando el nivel se aprobó solo por examen de suficiencia.';
            COMMENT ON COLUMN cer_textos_documentos.constancia_acta_acreditacion IS 'Frase de la acreditación que remite al acta final.';
            COMMENT ON COLUMN cer_textos_documentos.validez_acreditacion IS 'Frase de validez de la acreditación; {mencion} es el grado de la mención.';
            COMMENT ON COLUMN cer_textos_documentos.texto_certificado IS 'Texto del certificado de nivel; {nivel}, {idioma} y {horas} se imprimen en negrita.';
            COMMENT ON COLUMN cer_textos_documentos.created_at IS 'Fecha y hora de creación de la fila.';
            COMMENT ON COLUMN cer_textos_documentos.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cer_textos_documentos');
    }
};
