<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("COMMENT ON COLUMN per_personas.tratamiento_aceptado_en IS 'Fecha y hora en que aceptó el tratamiento de sus datos personales (Ley 29733), al crear su cuenta o, si la registró el personal, al firmar su autorización.'");
    }

    public function down(): void
    {
        DB::statement("COMMENT ON COLUMN per_personas.tratamiento_aceptado_en IS 'Fecha y hora en que aceptó el tratamiento de sus datos personales (Ley 29733) al registrarse.'");
    }
};
