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
        Schema::create('doc_silabos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->unique()->constrained('aca_grupos')->restrictOnDelete();
            $table->enum('estado', ['borrador', 'publicado'])->default('borrador');
            $table->text('sumilla')->nullable();
            $table->text('competencias')->nullable();
            $table->text('capacidades')->nullable();
            $table->text('metodologia')->nullable();
            $table->text('bibliografia')->nullable();
            $table->timestampTz('publicado_en')->nullable();
            $table->foreignId('publicado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index('estado');
            $table->index('publicado_por');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE doc_silabos ADD CONSTRAINT doc_silabos_publicacion_check
                CHECK ((estado = 'publicado') = (publicado_en IS NOT NULL AND publicado_por IS NOT NULL))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE doc_silabos ADD CONSTRAINT doc_silabos_longitud_check CHECK (
                char_length(coalesce(sumilla, '')) <= 2000 AND char_length(coalesce(competencias, '')) <= 2000
                AND char_length(coalesce(capacidades, '')) <= 2000 AND char_length(coalesce(metodologia, '')) <= 2000
                AND char_length(coalesce(bibliografia, '')) <= 2000)
            SQL);

        Schema::create('doc_silabo_semanas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('silabo_id')->constrained('doc_silabos')->restrictOnDelete();
            $table->smallInteger('semana');
            $table->string('unidad', 500)->nullable();
            $table->string('saber', 500)->nullable();
            $table->string('saber_hacer', 500)->nullable();
            $table->string('saber_ser', 500)->nullable();
            $table->string('actividades', 500)->nullable();
            $table->string('recursos', 500)->nullable();
            $table->timestampsTz();

            $table->unique(['silabo_id', 'semana']);
        });

        DB::statement('ALTER TABLE doc_silabo_semanas ADD CONSTRAINT doc_silabo_semanas_semana_check CHECK (semana >= 1)');

        Schema::create('doc_silabo_evaluaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('silabo_id')->constrained('doc_silabos')->restrictOnDelete();
            $table->string('componente_codigo', 30);
            $table->string('indicadores', 500)->nullable();
            $table->string('instrumento', 500)->nullable();
            $table->smallInteger('semana')->nullable();
            $table->timestampsTz();

            $table->unique(['silabo_id', 'componente_codigo']);
        });

        DB::statement('ALTER TABLE doc_silabo_evaluaciones ADD CONSTRAINT doc_silabo_evaluaciones_semana_check CHECK (semana IS NULL OR semana >= 1)');

        foreach (['doc_silabos', 'doc_silabo_semanas', 'doc_silabo_evaluaciones'] as $tabla) {
            DB::unprepared("CREATE TRIGGER {$tabla}_auditoria AFTER INSERT OR UPDATE OR DELETE ON {$tabla} FOR EACH ROW EXECUTE FUNCTION aud_registrar();");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_silabo_evaluaciones');
        Schema::dropIfExists('doc_silabo_semanas');
        Schema::dropIfExists('doc_silabos');
    }
};
