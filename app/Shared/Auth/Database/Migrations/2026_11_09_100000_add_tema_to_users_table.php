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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tema', 10)->default('sistema');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_tema_valido_check CHECK (tema IN ('claro', 'oscuro', 'sistema'))");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('tema');
        });
    }
};
