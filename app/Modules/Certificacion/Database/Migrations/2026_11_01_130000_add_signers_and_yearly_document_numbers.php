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
        Schema::create('cer_firmantes', function (Blueprint $table): void {
            $table->id();
            $table->string('cargo', 80);
            $table->string('nombre', 120);
            $table->boolean('firma_certificados')->default(false);
            $table->boolean('firma_constancias')->default(false);
            $table->boolean('firma_actas')->default(false);
            $table->smallInteger('orden')->default(1);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE cer_firmantes ADD CONSTRAINT cer_firmantes_textos_check CHECK (btrim(cargo) <> '' AND btrim(nombre) <> '')");
        DB::statement('ALTER TABLE cer_firmantes ADD CONSTRAINT cer_firmantes_documentos_check CHECK (firma_certificados OR firma_constancias OR firma_actas)');
        DB::statement('ALTER TABLE cer_firmantes ADD CONSTRAINT cer_firmantes_vigencia_check CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
        DB::statement('ALTER TABLE cer_firmantes ADD CONSTRAINT cer_firmantes_orden_check CHECK (orden >= 1)');
        DB::statement('CREATE TRIGGER cer_firmantes_auditoria AFTER INSERT OR UPDATE OR DELETE ON cer_firmantes FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        // One counter row per document type and year, read FOR UPDATE when a document is issued.
        Schema::create('cer_correlativos', function (Blueprint $table): void {
            $table->string('tipo', 40);
            $table->smallInteger('anio');
            $table->integer('ultimo')->default(0);
            $table->timestampsTz();

            $table->primary(['tipo', 'anio']);
        });

        DB::statement('ALTER TABLE cer_correlativos ADD CONSTRAINT cer_correlativos_ultimo_check CHECK (ultimo >= 0)');

        // Set at insert only: the lock trigger of cer_documentos freezes every column but the voiding ones.
        Schema::table('cer_documentos', function (Blueprint $table): void {
            $table->smallInteger('anio_numero')->nullable();
            $table->integer('numero')->nullable();
        });

        DB::statement('ALTER TABLE cer_documentos ADD CONSTRAINT cer_documentos_numero_check CHECK ((numero IS NULL) = (anio_numero IS NULL) AND (numero IS NULL OR numero > 0))');
        DB::statement('CREATE UNIQUE INDEX cer_documentos_numero_unique ON cer_documentos (tipo, anio_numero, numero) WHERE numero IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS cer_documentos_numero_unique');
        DB::statement('ALTER TABLE cer_documentos DROP CONSTRAINT IF EXISTS cer_documentos_numero_check');
        DB::statement('ALTER TABLE cer_documentos DISABLE TRIGGER cer_documentos_bloqueo');

        Schema::table('cer_documentos', function (Blueprint $table): void {
            $table->dropColumn(['anio_numero', 'numero']);
        });

        DB::statement('ALTER TABLE cer_documentos ENABLE TRIGGER cer_documentos_bloqueo');
        Schema::drop('cer_correlativos');
        Schema::drop('cer_firmantes');
    }
};
