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
            $table->string('direccion', 200)->nullable();
            // Relative path on the private local disk; served only through a controller.
            $table->string('foto', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('per_personas', function (Blueprint $table): void {
            $table->dropColumn(['direccion', 'foto']);
        });
    }
};
