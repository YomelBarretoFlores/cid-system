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
        Schema::table('aud_registros', function (Blueprint $table): void {
            $table->string('solicitud_id', 64)->nullable();
            $table->index('solicitud_id');
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION aud_registrar() RETURNS trigger
            LANGUAGE plpgsql
            SECURITY DEFINER
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                INSERT INTO public.aud_registros (tabla, registro_id, operacion, datos_anteriores, datos_nuevos, usuario_id, ip, ocurrido_en, solicitud_id)
                VALUES (
                    TG_TABLE_NAME,
                    CASE WHEN TG_OP = 'DELETE' THEN OLD.id ELSE NEW.id END,
                    TG_OP,
                    CASE WHEN TG_OP IN ('UPDATE', 'DELETE') THEN to_jsonb(OLD) END,
                    CASE WHEN TG_OP IN ('INSERT', 'UPDATE') THEN to_jsonb(NEW) END,
                    nullif(current_setting('app.usuario_id', true), '')::bigint,
                    nullif(current_setting('app.ip', true), '')::inet,
                    clock_timestamp(),
                    nullif(current_setting('app.solicitud_id', true), '')
                );

                RETURN NULL;
            END;
            $$;
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION aud_registrar() RETURNS trigger
            LANGUAGE plpgsql
            SECURITY DEFINER
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                INSERT INTO public.aud_registros (tabla, registro_id, operacion, datos_anteriores, datos_nuevos, usuario_id, ip, ocurrido_en)
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
            SQL);

        Schema::table('aud_registros', function (Blueprint $table): void {
            $table->dropIndex(['solicitud_id']);
            $table->dropColumn('solicitud_id');
        });
    }
};
