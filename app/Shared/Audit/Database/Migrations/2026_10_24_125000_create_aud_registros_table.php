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
        Schema::create('aud_registros', function (Blueprint $table): void {
            $table->id();
            $table->string('tabla', 63);
            $table->bigInteger('registro_id')->nullable();
            $table->string('operacion', 6);
            $table->jsonb('datos_anteriores')->nullable();
            $table->jsonb('datos_nuevos')->nullable();
            $table->bigInteger('usuario_id')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestampTz('ocurrido_en')->useCurrent();

            $table->index(['tabla', 'registro_id']);
            $table->index('ocurrido_en');
        });

        DB::statement("ALTER TABLE aud_registros ADD CONSTRAINT aud_registros_operacion_check CHECK (operacion IN ('INSERT', 'UPDATE', 'DELETE'))");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION aud_registrar() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public
            AS $$
            BEGIN
                INSERT INTO aud_registros (tabla, registro_id, operacion, datos_anteriores, datos_nuevos, usuario_id, ip, ocurrido_en)
                VALUES (
                    TG_TABLE_NAME,
                    CASE WHEN TG_OP = 'DELETE' THEN OLD.id ELSE NEW.id END,
                    TG_OP,
                    CASE WHEN TG_OP IN ('UPDATE', 'DELETE') THEN to_jsonb(OLD) END,
                    CASE WHEN TG_OP IN ('INSERT', 'UPDATE') THEN to_jsonb(NEW) END,
                    nullif(current_setting('app.usuario_id', true), '')::bigint,
                    nullif(current_setting('app.ip', true), '')::inet,
                    clock_timestamp()
                );

                RETURN NULL;
            END;
            $$;

            CREATE OR REPLACE FUNCTION aud_impedir_cambios() RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION 'aud_registros es inmutable: % no permitido', TG_OP USING ERRCODE = 'insufficient_privilege';
            END;
            $$;

            CREATE TRIGGER aud_registros_inmutable BEFORE UPDATE OR DELETE ON aud_registros
                FOR EACH ROW EXECUTE FUNCTION aud_impedir_cambios();

            CREATE TRIGGER aud_registros_sin_truncate BEFORE TRUNCATE ON aud_registros
                FOR EACH STATEMENT EXECUTE FUNCTION aud_impedir_cambios();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('aud_registros');
        DB::unprepared('DROP FUNCTION IF EXISTS aud_impedir_cambios(); DROP FUNCTION IF EXISTS aud_registrar();');
    }
};
