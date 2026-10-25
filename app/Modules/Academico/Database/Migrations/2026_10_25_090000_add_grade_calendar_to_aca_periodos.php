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
        Schema::table('aca_periodos', function (Blueprint $table): void {
            $table->date('actas_inicio')->nullable()->after('matricula_fin');
            $table->date('actas_fin')->nullable()->after('actas_inicio');
        });

        DB::statement('ALTER TABLE aca_periodos ADD CONSTRAINT aca_periodos_actas_check CHECK ((actas_inicio IS NULL) = (actas_fin IS NULL) AND (actas_fin IS NULL OR actas_fin >= actas_inicio))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE aca_periodos DROP CONSTRAINT IF EXISTS aca_periodos_actas_check');

        Schema::table('aca_periodos', function (Blueprint $table): void {
            $table->dropColumn(['actas_inicio', 'actas_fin']);
        });
    }
};
