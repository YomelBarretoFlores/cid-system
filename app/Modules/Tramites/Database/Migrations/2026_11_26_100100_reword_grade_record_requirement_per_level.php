<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The grade record is issued per level now; a text the CILN already rewrote is left alone.
return new class extends Migration
{
    private const string ANTERIOR = 'Al menos un ciclo con acta final cerrada en el idioma y el pago de la tasa.';

    private const string ACTUAL = 'Al menos un ciclo aprobado del nivel que solicitas y el pago de la tasa (una constancia por nivel).';

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
        DB::table('tra_tipos_tramite')->where(['codigo' => 'CONSTANCIA-NOTAS', 'requisitos' => $de])->update(['requisitos' => $a, 'updated_at' => now()]);
    }
};
