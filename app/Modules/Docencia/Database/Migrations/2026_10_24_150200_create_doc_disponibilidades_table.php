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
        Schema::create('doc_disponibilidades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('docente_id')->constrained('doc_docentes')->cascadeOnDelete();
            $table->smallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestampsTz();

            $table->unique(['docente_id', 'dia_semana', 'hora_inicio']);
        });

        DB::statement('ALTER TABLE doc_disponibilidades ADD CONSTRAINT doc_disponibilidades_dia_valido_check CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE doc_disponibilidades ADD CONSTRAINT doc_disponibilidades_franja_valida_check CHECK (hora_fin > hora_inicio)');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER doc_disponibilidades_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_disponibilidades
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_disponibilidades');
    }
};
