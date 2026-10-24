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
        Schema::create('doc_clases_dictadas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->constrained('aca_grupos')->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->foreignId('docente_id')->constrained('doc_docentes')->restrictOnDelete();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['grupo_id', 'fecha', 'hora_inicio']);
            $table->index('docente_id');
            $table->index('registrado_por');
        });

        DB::statement('ALTER TABLE doc_clases_dictadas ADD CONSTRAINT doc_clases_dictadas_horas_check CHECK (hora_fin > hora_inicio)');

        Schema::create('doc_asistencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clase_id')->constrained('doc_clases_dictadas')->restrictOnDelete();
            $table->foreignId('matricula_id')->constrained('mat_matriculas')->restrictOnDelete();
            $table->enum('estado', ['presente', 'tardanza', 'falta']);
            $table->timestampsTz();

            $table->unique(['clase_id', 'matricula_id']);
            $table->index('matricula_id');
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER doc_clases_dictadas_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_clases_dictadas
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            CREATE TRIGGER doc_asistencias_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_asistencias
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_asistencias');
        Schema::dropIfExists('doc_clases_dictadas');
    }
};
