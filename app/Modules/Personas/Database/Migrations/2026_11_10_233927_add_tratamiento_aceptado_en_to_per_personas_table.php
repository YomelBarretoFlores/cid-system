<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('per_personas', function (Blueprint $table): void {
            // Ley 29733 consent given at self-registration; records the staff registered have none.
            $table->timestampTz('tratamiento_aceptado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('per_personas', function (Blueprint $table): void {
            $table->dropColumn('tratamiento_aceptado_en');
        });
    }
};
