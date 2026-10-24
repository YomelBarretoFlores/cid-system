<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array COLUMNAS = ['tarifa_id', 'registrado_por', 'validado_por', 'anulado_por'];

    public function up(): void
    {
        Schema::table('pag_pagos', function (Blueprint $table): void {
            foreach (self::COLUMNAS as $columna) {
                $table->index($columna);
            }
        });
    }

    public function down(): void
    {
        Schema::table('pag_pagos', function (Blueprint $table): void {
            foreach (self::COLUMNAS as $columna) {
                $table->dropIndex([$columna]);
            }
        });
    }
};
