<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The hours report filters classes by month and teacher.
        Schema::table('doc_clases_dictadas', function (Blueprint $table): void {
            $table->index(['fecha', 'docente_id']);
        });
    }

    public function down(): void
    {
        Schema::table('doc_clases_dictadas', function (Blueprint $table): void {
            $table->dropIndex(['fecha', 'docente_id']);
        });
    }
};
