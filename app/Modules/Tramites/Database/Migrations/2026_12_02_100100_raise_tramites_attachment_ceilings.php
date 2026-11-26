<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // «Parámetros del sistema» sets the real limits (up to 10 attachments of up to 10 MB); these CHECKs are only the safe ceiling.
    public function up(): void
    {
        DB::statement('ALTER TABLE tra_tipos_tramite DROP CONSTRAINT tra_tipos_tramite_fut_check');
        DB::statement("ALTER TABLE tra_tipos_tramite ADD CONSTRAINT tra_tipos_tramite_fut_check CHECK (jsonb_typeof(campos) = 'array' AND jsonb_typeof(adjuntos) = 'array' AND jsonb_array_length(adjuntos) <= 10 AND requiere_tipo_previo_id IS DISTINCT FROM id)");
        DB::statement('ALTER TABLE tra_adjuntos DROP CONSTRAINT tra_adjuntos_bytes_check');
        DB::statement('ALTER TABLE tra_adjuntos ADD CONSTRAINT tra_adjuntos_bytes_check CHECK (bytes BETWEEN 1 AND 20971520)');
        DB::statement("COMMENT ON COLUMN tra_adjuntos.bytes IS 'Tamaño del archivo en bytes (máximo 20 MB; el límite vigente está en los parámetros del sistema).'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tra_tipos_tramite DROP CONSTRAINT tra_tipos_tramite_fut_check');
        DB::statement("ALTER TABLE tra_tipos_tramite ADD CONSTRAINT tra_tipos_tramite_fut_check CHECK (jsonb_typeof(campos) = 'array' AND jsonb_typeof(adjuntos) = 'array' AND jsonb_array_length(adjuntos) <= 5 AND requiere_tipo_previo_id IS DISTINCT FROM id)");
        DB::statement('ALTER TABLE tra_adjuntos DROP CONSTRAINT tra_adjuntos_bytes_check');
        DB::statement('ALTER TABLE tra_adjuntos ADD CONSTRAINT tra_adjuntos_bytes_check CHECK (bytes BETWEEN 1 AND 5242880)');
        DB::statement("COMMENT ON COLUMN tra_adjuntos.bytes IS 'Tamaño del archivo en bytes (máximo 5 MB).'");
    }
};
