<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aud_registros', function (Blueprint $table): void {
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::table('aud_registros', function (Blueprint $table): void {
            $table->dropIndex(['usuario_id']);
        });
    }
};
