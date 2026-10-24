<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mat_habilitaciones', function (Blueprint $table): void {
            $table->index('idioma_id');
            $table->index(['ciclo_id', 'idioma_id']);
            $table->index('actualizado_por');
        });

        Schema::table('mat_movimientos', function (Blueprint $table): void {
            $table->index('grupo_origen_id');
            $table->index('grupo_destino_id');
            $table->index('autorizado_por');
        });

        Schema::table('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->index('idioma_id');
            $table->index(['ciclo_asignado_id', 'idioma_id']);
            $table->index('evaluado_por');
        });
    }

    public function down(): void
    {
        Schema::table('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->dropIndex(['evaluado_por']);
            $table->dropIndex(['ciclo_asignado_id', 'idioma_id']);
            $table->dropIndex(['idioma_id']);
        });

        Schema::table('mat_movimientos', function (Blueprint $table): void {
            $table->dropIndex(['autorizado_por']);
            $table->dropIndex(['grupo_destino_id']);
            $table->dropIndex(['grupo_origen_id']);
        });

        Schema::table('mat_habilitaciones', function (Blueprint $table): void {
            $table->dropIndex(['actualizado_por']);
            $table->dropIndex(['ciclo_id', 'idioma_id']);
            $table->dropIndex(['idioma_id']);
        });
    }
};
