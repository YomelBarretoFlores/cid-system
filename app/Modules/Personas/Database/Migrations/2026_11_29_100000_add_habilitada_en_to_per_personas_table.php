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
        Schema::table('per_personas', function (Blueprint $table): void {
            // Records staff register are enabled at once; self-registered accounts wait for their first validated payment.
            $table->timestampTz('habilitada_en')->nullable()->useCurrent();
        });

        DB::statement("COMMENT ON COLUMN per_personas.habilitada_en IS 'Cuándo se habilitó la cuenta del estudiante (al validarse su primer pago); vacío mientras solo ve las secciones generales del portal.'");
    }

    public function down(): void
    {
        Schema::table('per_personas', function (Blueprint $table): void {
            $table->dropColumn('habilitada_en');
        });
    }
};
