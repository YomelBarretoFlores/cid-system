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
        Schema::create('doc_cargas_academicas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->unique()->constrained('aca_grupos')->restrictOnDelete();
            $table->foreignId('docente_id')->constrained('doc_docentes')->restrictOnDelete();
            $table->foreignId('asignado_por')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index('docente_id');
            $table->index('asignado_por');
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER doc_cargas_academicas_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_cargas_academicas
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_cargas_academicas');
    }
};
