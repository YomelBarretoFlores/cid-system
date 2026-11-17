<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Actas closed before the skills and exams groups keep their weights in the JSON; reading them derives the group from the code.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("COMMENT ON COLUMN cer_actas.componentes IS 'Componentes aplicados con su grupo, habilidades o examenes (JSON).'");
    }

    public function down(): void
    {
        DB::statement("COMMENT ON COLUMN cer_actas.componentes IS 'Componentes y pesos aplicados (JSON).'");
    }
};
