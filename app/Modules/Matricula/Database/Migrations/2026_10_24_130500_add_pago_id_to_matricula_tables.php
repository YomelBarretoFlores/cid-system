<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mat_matriculas', function (Blueprint $table): void {
            $table->foreignId('pago_id')->nullable()->unique()->after('grupo_id')->constrained('pag_pagos')->restrictOnDelete();
        });

        Schema::table('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->foreignId('pago_id')->nullable()->unique()->after('idioma_id')->constrained('pag_pagos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pago_id');
        });

        Schema::table('mat_matriculas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pago_id');
        });
    }
};
