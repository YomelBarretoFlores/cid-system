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
        // The official name of each year, printed above the title of issued documents.
        Schema::create('cer_denominaciones', function (Blueprint $table): void {
            $table->id();
            $table->smallInteger('anio')->unique();
            $table->string('texto', 250);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE cer_denominaciones ADD CONSTRAINT cer_denominaciones_texto_check CHECK (btrim(texto) <> '')");
        DB::statement('ALTER TABLE cer_denominaciones ADD CONSTRAINT cer_denominaciones_anio_check CHECK (anio BETWEEN 2000 AND 2100)');
        DB::statement('CREATE TRIGGER cer_denominaciones_auditoria AFTER INSERT OR UPDATE OR DELETE ON cer_denominaciones FOR EACH ROW EXECUTE FUNCTION aud_registrar()');
    }

    public function down(): void
    {
        Schema::dropIfExists('cer_denominaciones');
    }
};
