<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The postgraduate accreditation record: the per-level grade record plus the degree mention, numbered on its own each year.
return new class extends Migration
{
    public function up(): void
    {
        $this->tipos("'constancia_matricula', 'constancia_notas', 'certificado_nivel', 'constancia_acreditacion'");
        DB::statement("ALTER TABLE cer_documentos ADD CONSTRAINT cer_documentos_acreditacion_check CHECK (tipo <> 'constancia_acreditacion' OR nivel IS NOT NULL)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cer_documentos DROP CONSTRAINT cer_documentos_acreditacion_check');
        $this->tipos("'constancia_matricula', 'constancia_notas', 'certificado_nivel'");
    }

    /**
     * @param  literal-string  $valores
     */
    private function tipos(string $valores): void
    {
        DB::statement('ALTER TABLE cer_documentos DROP CONSTRAINT cer_documentos_tipo_check');
        DB::statement("ALTER TABLE cer_documentos ADD CONSTRAINT cer_documentos_tipo_check CHECK (tipo IN ({$valores}))");
    }
};
