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
        Schema::table('aca_grupos', function (Blueprint $table): void {
            $table->foreignId('programa_id')->nullable()->constrained('aca_programas')->restrictOnDelete();
            $table->index('programa_id');
        });

        // Every group created before programs existed belongs to Jóvenes y Adultos.
        DB::table('aca_grupos')->whereNull('programa_id')->update([
            'programa_id' => DB::table('aca_programas')->where('codigo', 'JA')->value('id'),
        ]);

        DB::statement('ALTER TABLE aca_grupos ALTER COLUMN programa_id SET NOT NULL');

        DB::statement("COMMENT ON COLUMN aca_grupos.programa_id IS 'Programa al que pertenece el grupo; su régimen decide si el grupo puede ser regular o acelerado. Referencia a aca_programas.'");
    }

    public function down(): void
    {
        Schema::table('aca_grupos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('programa_id');
        });
    }
};
