<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array CONCEPTOS = ['constancia', 'certificado', 'tramite', 'otro'];

    private const array ACCIONES = ['ninguna', 'constancia_matricula', 'constancia_notas', 'certificado_nivel', 'traslado', 'retiro'];

    public function up(): void
    {
        Schema::create('tra_tipos_tramite', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 120);
            $table->string('requisitos', 1000)->nullable();
            $table->enum('tipo_concepto', self::CONCEPTOS)->nullable();
            $table->enum('accion_final', self::ACCIONES);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
        });

        Schema::create('tra_etapas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tipo_tramite_id')->constrained('tra_tipos_tramite')->cascadeOnDelete();
            $table->smallInteger('orden');
            $table->string('nombre', 80);
            $table->smallInteger('avance');
            $table->enum('condicion', ['solicitud', 'pago_validado', 'accion_del_personal']);
            $table->timestampsTz();

            $table->unique(['tipo_tramite_id', 'orden']);
        });

        DB::statement('ALTER TABLE tra_etapas ADD CONSTRAINT tra_etapas_avance_check CHECK (avance BETWEEN 0 AND 100 AND orden >= 1)');

        // The trámite keeps a copy of its type and stages, so editing the configuration never changes one in progress.
        DB::statement('CREATE SEQUENCE tra_tramites_numero_seq');
        Schema::create('tra_tramites', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->foreignId('tipo_tramite_id')->constrained('tra_tipos_tramite')->restrictOnDelete();
            $table->string('tipo_nombre', 120);
            $table->enum('accion_final', self::ACCIONES);
            $table->enum('tipo_concepto', self::CONCEPTOS)->nullable();
            $table->jsonb('etapas');
            $table->smallInteger('etapa_actual')->default(0);
            $table->enum('estado', ['pendiente_pago', 'en_proceso', 'observado', 'finalizado']);
            $table->jsonb('datos');
            $table->foreignId('pago_id')->nullable()->unique()->constrained('pag_pagos')->restrictOnDelete();
            $table->string('observacion', 500)->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('cer_documentos')->restrictOnDelete();
            $table->timestampTz('finalizado_en')->nullable();
            $table->timestampsTz();

            $table->index(['persona_id', 'estado']);
            $table->index('tipo_tramite_id');
            $table->index('documento_id');
            $table->index(['estado', 'created_at']);
        });

        DB::statement("ALTER TABLE tra_tramites ALTER COLUMN codigo SET DEFAULT ('TR-' || to_char(now() AT TIME ZONE 'America/Lima', 'YYYY') || '-' || lpad(nextval('tra_tramites_numero_seq')::text, 6, '0'))");
        DB::statement('ALTER SEQUENCE tra_tramites_numero_seq OWNED BY tra_tramites.codigo');
        DB::statement("ALTER TABLE tra_tramites ADD CONSTRAINT tra_tramites_etapa_check CHECK (jsonb_typeof(etapas) = 'array' AND jsonb_array_length(etapas) >= 2 AND etapa_actual >= 0 AND etapa_actual < jsonb_array_length(etapas))");
        DB::statement("ALTER TABLE tra_tramites ADD CONSTRAINT tra_tramites_observacion_check CHECK ((estado = 'observado') = (observacion IS NOT NULL) AND (estado = 'finalizado') = (finalizado_en IS NOT NULL) AND (observacion IS NULL OR char_length(observacion) BETWEEN 5 AND 500))");

        Schema::create('tra_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tramite_id')->constrained('tra_tramites')->restrictOnDelete();
            $table->enum('estado', ['pendiente_pago', 'en_proceso', 'observado', 'finalizado']);
            $table->smallInteger('etapa');
            $table->string('etapa_nombre', 80);
            $table->string('comentario', 500)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('tramite_id');
            $table->index('actor_id');
        });

        Schema::create('tra_notificaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tramite_id')->constrained('tra_tramites')->restrictOnDelete();
            $table->string('destinatario', 255);
            $table->string('asunto', 200);
            $table->enum('estado', ['pendiente', 'enviado', 'fallido'])->default('pendiente');
            $table->smallInteger('intentos')->default(0);
            $table->timestampTz('enviado_en')->nullable();
            $table->string('error', 200)->nullable();
            $table->timestampsTz();

            $table->index('tramite_id');
            $table->index(['estado', 'created_at']);
        });

        foreach (['tra_tipos_tramite', 'tra_etapas', 'tra_tramites'] as $tabla) {
            DB::unprepared("CREATE TRIGGER {$tabla}_auditoria AFTER INSERT OR UPDATE OR DELETE ON {$tabla} FOR EACH ROW EXECUTE FUNCTION aud_registrar();");
        }

        $this->bloqueos();
    }

    public function down(): void
    {
        Schema::dropIfExists('tra_notificaciones');
        Schema::dropIfExists('tra_movimientos');
        Schema::dropIfExists('tra_tramites');
        Schema::dropIfExists('tra_etapas');
        Schema::dropIfExists('tra_tipos_tramite');

        DB::unprepared('DROP FUNCTION IF EXISTS tra_controlar_tramites(); DROP FUNCTION IF EXISTS tra_impedir_borrado(); DROP FUNCTION IF EXISTS tra_impedir_truncate();');
    }

    // A trámite is never deleted, its request never changes, and once finished it is closed; history rows are append-only.
    private function bloqueos(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION tra_controlar_tramites() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                solicitud text[] := ARRAY['codigo', 'persona_id', 'tipo_tramite_id', 'tipo_nombre', 'accion_final', 'tipo_concepto', 'etapas', 'datos', 'created_at'];
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'tra_tramites: un trámite no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.estado = 'finalizado' THEN
                    RAISE EXCEPTION 'tra_tramites: un trámite finalizado no cambia' USING ERRCODE = 'check_violation';
                END IF;

                IF EXISTS (SELECT 1 FROM unnest(solicitud) AS clave WHERE (to_jsonb(NEW) -> clave) IS DISTINCT FROM (to_jsonb(OLD) -> clave)) THEN
                    RAISE EXCEPTION 'tra_tramites: la solicitud de un trámite no cambia' USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER tra_tramites_bloqueo BEFORE UPDATE OR DELETE ON tra_tramites
                FOR EACH ROW EXECUTE FUNCTION tra_controlar_tramites();

            CREATE OR REPLACE FUNCTION tra_impedir_borrado() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF TG_OP = 'DELETE' OR TG_TABLE_NAME = 'tra_movimientos' THEN
                    RAISE EXCEPTION '%: el historial de trámites no se modifica ni se elimina', TG_TABLE_NAME USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER tra_movimientos_bloqueo BEFORE UPDATE OR DELETE ON tra_movimientos
                FOR EACH ROW EXECUTE FUNCTION tra_impedir_borrado();
            CREATE TRIGGER tra_notificaciones_bloqueo BEFORE DELETE ON tra_notificaciones
                FOR EACH ROW EXECUTE FUNCTION tra_impedir_borrado();
            CREATE TRIGGER tra_tipos_tramite_bloqueo BEFORE DELETE ON tra_tipos_tramite
                FOR EACH ROW EXECUTE FUNCTION tra_impedir_borrado();

            CREATE OR REPLACE FUNCTION tra_impedir_truncate() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                con_filas boolean;
            BEGIN
                EXECUTE format('SELECT EXISTS (SELECT 1 FROM public.%I)', TG_TABLE_NAME) INTO con_filas;

                IF con_filas THEN
                    RAISE EXCEPTION '%: los trámites y su historial no se borran', TG_TABLE_NAME USING ERRCODE = 'check_violation';
                END IF;

                RETURN NULL;
            END;
            $$;
            CREATE TRIGGER tra_tramites_sin_truncate BEFORE TRUNCATE ON tra_tramites FOR EACH STATEMENT EXECUTE FUNCTION tra_impedir_truncate();
            CREATE TRIGGER tra_movimientos_sin_truncate BEFORE TRUNCATE ON tra_movimientos FOR EACH STATEMENT EXECUTE FUNCTION tra_impedir_truncate();
            CREATE TRIGGER tra_notificaciones_sin_truncate BEFORE TRUNCATE ON tra_notificaciones FOR EACH STATEMENT EXECUTE FUNCTION tra_impedir_truncate();
            SQL);
    }
};
