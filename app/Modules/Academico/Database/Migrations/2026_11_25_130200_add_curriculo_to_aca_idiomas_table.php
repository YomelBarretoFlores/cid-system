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
        Schema::table('aca_idiomas', function (Blueprint $table): void {
            $table->string('curriculo', 10)->default('01');
        });

        DB::statement("COMMENT ON COLUMN aca_idiomas.curriculo IS 'Código del currículo vigente del idioma, impreso en la constancia de notas.'");
    }

    public function down(): void
    {
        Schema::table('aca_idiomas', function (Blueprint $table): void {
            $table->dropColumn('curriculo');
        });
    }
};
