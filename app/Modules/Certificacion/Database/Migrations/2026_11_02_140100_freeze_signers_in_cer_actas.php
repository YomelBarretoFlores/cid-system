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
        // Set when the acta closes, like a document at issue; actas closed before this change keep them null and read them live.
        Schema::table('cer_actas', function (Blueprint $table): void {
            $table->jsonb('firmas')->nullable();
            $table->string('denominacion', 250)->nullable();
        });

        DB::statement('ALTER TABLE cer_actas ADD CONSTRAINT cer_actas_denominacion_check CHECK (denominacion IS NULL OR firmas IS NOT NULL)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cer_actas DROP CONSTRAINT IF EXISTS cer_actas_denominacion_check');

        Schema::table('cer_actas', function (Blueprint $table): void {
            $table->dropColumn(['firmas', 'denominacion']);
        });
    }
};
