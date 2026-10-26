<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One enrollment record per person and period; its code is the public key printed in the QR and the barcode.
    public function up(): void
    {
        Schema::create('mat_fichas', function (Blueprint $table): void {
            $table->id();
            $table->ulid('codigo')->unique();
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('aca_periodos')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['persona_id', 'periodo_id']);
            $table->index('periodo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mat_fichas');
    }
};
