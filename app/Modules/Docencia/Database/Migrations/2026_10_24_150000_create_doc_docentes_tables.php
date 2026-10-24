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
        Schema::create('doc_docentes', function (Blueprint $table): void {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('persona_id')->unique()->constrained('per_personas')->restrictOnDelete();
            $table->string('codigo', 12)->storedAs("'DOC-' || lpad(id::text, 5, '0')")->unique();
            $table->string('correo_institucional', 120)->unique();
            $table->char('ruc', 11)->unique();
            $table->string('banco', 80);
            $table->text('cuenta_bancaria');
            $table->string('condicion_laboral', 20);
            $table->string('estado', 20)->default('activo');
            $table->timestampsTz();

            $table->index('estado');
        });

        DB::statement("ALTER TABLE doc_docentes ADD CONSTRAINT doc_docentes_condicion_valida_check CHECK (condicion_laboral IN ('nombrado', 'contratado', 'tercero'))");
        DB::statement("ALTER TABLE doc_docentes ADD CONSTRAINT doc_docentes_estado_valido_check CHECK (estado IN ('activo', 'inactivo', 'licencia'))");
        DB::statement("ALTER TABLE doc_docentes ADD CONSTRAINT doc_docentes_ruc_formato_check CHECK (ruc ~ '^[0-9]{11}$')");

        Schema::create('doc_docente_idiomas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('docente_id')->constrained('doc_docentes')->cascadeOnDelete();
            $table->foreignId('idioma_id')->constrained('aca_idiomas')->restrictOnDelete();
            $table->string('nivel_maximo', 20);
            $table->timestampsTz();

            $table->unique(['docente_id', 'idioma_id']);
            $table->index('idioma_id');
        });

        DB::statement("ALTER TABLE doc_docente_idiomas ADD CONSTRAINT doc_docente_idiomas_nivel_valido_check CHECK (nivel_maximo IN ('basico', 'intermedio', 'avanzado'))");

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER doc_docentes_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_docentes
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            CREATE TRIGGER doc_docente_idiomas_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_docente_idiomas
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_docente_idiomas');
        Schema::dropIfExists('doc_docentes');
    }
};
