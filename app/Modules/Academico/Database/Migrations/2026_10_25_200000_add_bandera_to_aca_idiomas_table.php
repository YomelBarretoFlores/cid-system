<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_idiomas', function (Blueprint $table): void {
            // Null falls back to the flag suggested by the name; no existing row is changed.
            $table->string('bandera', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('aca_idiomas', function (Blueprint $table): void {
            $table->dropColumn('bandera');
        });
    }
};
