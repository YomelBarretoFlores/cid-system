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
        Schema::create('doc_delegados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->unique()->constrained('aca_grupos')->restrictOnDelete();
            $table->foreignId('matricula_id')->constrained('mat_matriculas')->restrictOnDelete();
            $table->foreignId('designado_por')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index('matricula_id');
            $table->index('designado_por');
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER doc_delegados_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_delegados
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_delegados');
    }
};
