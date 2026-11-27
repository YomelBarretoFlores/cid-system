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
        Schema::table('pag_configuracion', function (Blueprint $table): void {
            $table->boolean('permite_pagos_anteriores_al_sistema')->default(true);
        });

        DB::statement("COMMENT ON COLUMN pag_configuracion.permite_pagos_anteriores_al_sistema IS 'Si es verdadero, el personal puede matricular en ventanilla con un pago hecho antes del sistema, sin el archivo del recibo y con motivo (decisión pendiente 153).'");
    }

    public function down(): void
    {
        Schema::table('pag_configuracion', function (Blueprint $table): void {
            $table->dropColumn('permite_pagos_anteriores_al_sistema');
        });
    }
};
