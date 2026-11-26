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
        // One row, created on the first save; until then the portal shows the wording it printed before (Ley 29733 texts).
        Schema::create('per_textos_legales', function (Blueprint $table): void {
            $table->smallInteger('id')->primary()->default(1);
            $table->text('politica_privacidad');
            $table->string('domicilio_titular', 200);
            $table->string('consentimiento_registro', 600);
            $table->date('politica_actualizada_el');
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE per_textos_legales ADD CONSTRAINT per_textos_legales_unica_check CHECK (id = 1)');
        DB::statement("ALTER TABLE per_textos_legales ADD CONSTRAINT per_textos_legales_textos_check CHECK (btrim(politica_privacidad) <> '' AND btrim(domicilio_titular) <> '' AND btrim(consentimiento_registro) <> '')");
        DB::statement("ALTER TABLE per_textos_legales ADD CONSTRAINT per_textos_legales_enlace_check CHECK (strpos(consentimiento_registro, '{politica}') > 0)");
        DB::statement('CREATE TRIGGER per_textos_legales_auditoria AFTER INSERT OR UPDATE OR DELETE ON per_textos_legales FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        DB::unprepared(<<<'SQL'
            COMMENT ON TABLE per_textos_legales IS 'Textos legales del portal (Ley 29733): política de privacidad y frase de consentimiento del registro; una sola fila, creada al guardarlos por primera vez.';
            COMMENT ON COLUMN per_textos_legales.id IS 'Identificador de la única fila; siempre 1.';
            COMMENT ON COLUMN per_textos_legales.politica_privacidad IS 'Cuerpo de la política de privacidad en Markdown; {universidad}, {centro} y {domicilio} se reemplazan al mostrarla. El HTML escrito se muestra como texto.';
            COMMENT ON COLUMN per_textos_legales.domicilio_titular IS 'Domicilio legal de la universidad, titular del banco de datos personales.';
            COMMENT ON COLUMN per_textos_legales.consentimiento_registro IS 'Frase que acepta quien crea su cuenta; {politica} es el enlace a la política de privacidad.';
            COMMENT ON COLUMN per_textos_legales.politica_actualizada_el IS 'Fecha de la última modificación de la política o del domicilio, que la página muestra como última actualización.';
            COMMENT ON COLUMN per_textos_legales.created_at IS 'Fecha y hora de creación de la fila.';
            COMMENT ON COLUMN per_textos_legales.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('per_textos_legales');
    }
};
