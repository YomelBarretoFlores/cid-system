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
        Schema::table('aca_grupos', function (Blueprint $table): void {
            $table->foreignId('ciclo_hasta_id')->nullable()->constrained('aca_ciclos')->restrictOnDelete();
            $table->index('ciclo_hasta_id');
        });

        // Accelerated groups created while the flag was display-only: they cover the next active cycle of their level, or become regular when there is none.
        DB::statement(<<<'SQL'
            UPDATE aca_grupos g SET ciclo_hasta_id = h.id
            FROM aca_ciclos d JOIN aca_ciclos h ON h.idioma_id = d.idioma_id AND h.nivel = d.nivel AND h.numero = d.numero + 1 AND h.activo
            WHERE g.es_acelerado AND d.id = g.ciclo_id
            SQL);
        DB::table('aca_grupos')->where('es_acelerado', true)->whereNull('ciclo_hasta_id')->update(['es_acelerado' => false]);

        DB::statement('ALTER TABLE aca_grupos ADD CONSTRAINT aca_grupos_ciclo_hasta_check CHECK ((es_acelerado AND ciclo_hasta_id IS NOT NULL) OR (NOT es_acelerado AND ciclo_hasta_id IS NULL))');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION aca_controlar_ciclo_hasta() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF NEW.ciclo_hasta_id IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM public.aca_ciclos d
                    JOIN public.aca_ciclos h ON h.idioma_id = d.idioma_id AND h.nivel = d.nivel AND h.numero = d.numero + 1
                    WHERE d.id = NEW.ciclo_id AND h.id = NEW.ciclo_hasta_id
                ) THEN
                    RAISE EXCEPTION 'aca_grupos: el segundo ciclo de un grupo acelerado es el siguiente del mismo idioma y nivel' USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER aca_grupos_ciclo_hasta BEFORE INSERT OR UPDATE OF ciclo_id, ciclo_hasta_id ON aca_grupos
                FOR EACH ROW EXECUTE FUNCTION aca_controlar_ciclo_hasta();
            SQL);

        DB::statement("COMMENT ON COLUMN aca_grupos.ciclo_hasta_id IS 'Segundo ciclo que cubre un grupo acelerado: el siguiente del mismo idioma y nivel; vacío en un grupo regular. Referencia a aca_ciclos.'");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS aca_grupos_ciclo_hasta ON aca_grupos; DROP FUNCTION IF EXISTS aca_controlar_ciclo_hasta();');
        DB::statement('ALTER TABLE aca_grupos DROP CONSTRAINT IF EXISTS aca_grupos_ciclo_hasta_check');

        Schema::table('aca_grupos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ciclo_hasta_id');
        });
    }
};
