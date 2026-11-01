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
        // A late arrival already counted as attended, so turning it into present changes no percentage; the audit trigger keeps the old value.
        DB::table('doc_asistencias')->where('estado', 'tardanza')->update(['estado' => 'presente']);
        DB::statement('ALTER TABLE doc_asistencias DROP CONSTRAINT doc_asistencias_estado_check');
        DB::statement("ALTER TABLE doc_asistencias ADD CONSTRAINT doc_asistencias_estado_check CHECK (estado IN ('presente', 'falta'))");

        Schema::table('doc_clases_dictadas', function (Blueprint $table): void {
            $table->text('tema')->nullable();
            $table->foreignId('silabo_semana_id')->nullable()->constrained('doc_silabo_semanas')->nullOnDelete();

            $table->index('silabo_semana_id');
        });
    }

    public function down(): void
    {
        Schema::table('doc_clases_dictadas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('silabo_semana_id');
            $table->dropColumn('tema');
        });

        DB::statement('ALTER TABLE doc_asistencias DROP CONSTRAINT doc_asistencias_estado_check');
        DB::statement("ALTER TABLE doc_asistencias ADD CONSTRAINT doc_asistencias_estado_check CHECK (estado IN ('presente', 'tardanza', 'falta'))");
    }
};
