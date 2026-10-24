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
        Schema::create('pag_pagos', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->foreignId('concepto_id')->constrained('pag_conceptos')->restrictOnDelete();
            $table->foreignId('tarifa_id')->nullable()->constrained('pag_tarifas')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->enum('medio_pago', ['caja_presencial', 'pagalo_pe', 'banco_nacion', 'transferencia', 'otro']);
            $table->string('numero_operacion', 40);
            $table->date('fecha_pago');
            $table->string('recibo_path');
            $table->enum('estado', ['pendiente', 'validado', 'rechazado', 'anulado'])->default('pendiente');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('validado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('validado_en')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('anulado_en')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->timestampsTz();

            $table->unique(['medio_pago', 'numero_operacion']);
            $table->index(['estado', 'fecha_pago']);
            $table->index('persona_id');
            $table->index('concepto_id');
        });

        DB::statement('ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_monto_check CHECK (monto > 0)');
        DB::statement("ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_numero_operacion_check CHECK (numero_operacion = upper(btrim(numero_operacion)) AND numero_operacion <> '')");
        DB::statement(<<<'SQL'
            ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_datos_estado_check CHECK (
                (estado = 'pendiente' AND validado_por IS NULL AND validado_en IS NULL AND motivo_rechazo IS NULL)
                OR (estado = 'validado' AND validado_por IS NOT NULL AND validado_en IS NOT NULL AND motivo_rechazo IS NULL)
                OR (estado = 'rechazado' AND validado_por IS NOT NULL AND validado_en IS NOT NULL AND btrim(coalesce(motivo_rechazo, '')) <> '')
                OR (estado = 'anulado' AND validado_por IS NOT NULL AND validado_en IS NOT NULL)
            )
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_anulacion_check CHECK (
                (estado = 'anulado') = (anulado_por IS NOT NULL AND anulado_en IS NOT NULL AND btrim(coalesce(motivo_anulacion, '')) <> '')
            )
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION pag_impedir_borrado() RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION 'pag_pagos no admite borrado: anule el pago con motivo' USING ERRCODE = 'insufficient_privilege';
            END;
            $$;

            CREATE TRIGGER pag_pagos_sin_borrado BEFORE DELETE ON pag_pagos
                FOR EACH ROW EXECUTE FUNCTION pag_impedir_borrado();

            CREATE TRIGGER pag_pagos_sin_truncate BEFORE TRUNCATE ON pag_pagos
                FOR EACH STATEMENT EXECUTE FUNCTION pag_impedir_borrado();

            CREATE TRIGGER pag_pagos_auditoria AFTER INSERT OR UPDATE OR DELETE ON pag_pagos
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pag_pagos');
        DB::unprepared('DROP FUNCTION IF EXISTS pag_impedir_borrado();');
    }
};
