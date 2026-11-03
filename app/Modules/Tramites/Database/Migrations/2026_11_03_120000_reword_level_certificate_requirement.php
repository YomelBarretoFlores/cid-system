<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Levels have the cycles the study plan configures, not always four; a text the CILN already rewrote is left alone.
return new class extends Migration
{
    private const string ANTERIOR = 'Los cuatro ciclos del nivel aprobados en actas cerradas y el pago de la tasa.';

    private const string ACTUAL = 'Todos los ciclos del nivel aprobados en actas cerradas y el pago de la tasa.';

    public function up(): void
    {
        $this->reemplazar(self::ANTERIOR, self::ACTUAL);
    }

    public function down(): void
    {
        $this->reemplazar(self::ACTUAL, self::ANTERIOR);
    }

    private function reemplazar(string $de, string $a): void
    {
        DB::table('tra_tipos_tramite')->where(['codigo' => 'CERTIFICADO-NIVEL', 'requisitos' => $de])->update(['requisitos' => $a, 'updated_at' => now()]);
    }
};
