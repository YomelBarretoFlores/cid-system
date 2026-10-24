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
        Schema::create('doc_notas_parciales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->constrained('aca_grupos')->restrictOnDelete();
            $table->foreignId('matricula_id')->constrained('mat_matriculas')->restrictOnDelete();
            $table->foreignId('componente_id')->constrained('aca_componentes_evaluacion')->restrictOnDelete();
            $table->decimal('nota', 4, 2)->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['grupo_id', 'matricula_id', 'componente_id']);
            $table->index('matricula_id');
            $table->index('componente_id');
            $table->index('registrado_por');
        });

        DB::statement('ALTER TABLE doc_notas_parciales ADD CONSTRAINT doc_notas_parciales_nota_check CHECK (nota IS NULL OR nota >= 0)');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER doc_notas_parciales_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_notas_parciales
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_notas_parciales');
    }
};
