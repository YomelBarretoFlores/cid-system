<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> code => name, in the order the forms offer them */
    private const array MEDIOS = [
        'caja_presencial' => 'Caja presencial',
        'tesoreria_unasam' => 'Tesorería UNASAM',
        'pagalo_pe' => 'Págalo.pe',
        'banco_nacion' => 'Banco de la Nación',
        'transferencia' => 'Transferencia',
        'otro' => 'Otro',
    ];

    private const array CODIGOS_ANTERIORES = ['caja_presencial', 'pagalo_pe', 'banco_nacion', 'transferencia', 'otro'];

    public function up(): void
    {
        Schema::create('pag_medios_pago', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 60)->unique();
            $table->boolean('activo')->default(true);
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE pag_medios_pago ADD CONSTRAINT pag_medios_pago_codigo_check CHECK (codigo ~ '^[a-z0-9_]+$')");
        DB::statement("ALTER TABLE pag_medios_pago ADD CONSTRAINT pag_medios_pago_nombre_check CHECK (btrim(nombre) <> '')");
        DB::statement('ALTER TABLE pag_medios_pago ADD CONSTRAINT pag_medios_pago_orden_check CHECK (orden >= 0)');
        DB::statement('CREATE TRIGGER pag_medios_pago_auditoria AFTER INSERT OR UPDATE OR DELETE ON pag_medios_pago FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        $orden = 0;
        DB::table('pag_medios_pago')->insert(array_map(function (string $codigo, string $nombre) use (&$orden): array {
            return ['codigo' => $codigo, 'nombre' => $nombre, 'orden' => ++$orden, 'activo' => true, 'created_at' => now(), 'updated_at' => now()];
        }, array_keys(self::MEDIOS), self::MEDIOS));

        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->foreignId('medio_pago_id')->nullable()->constrained('pag_medios_pago')->restrictOnDelete();
            $table->index('medio_pago_id');
        });

        // The lifecycle trigger freezes every column of a reviewed payment; this rewrite keeps the same channel under its new key.
        DB::statement('ALTER TABLE pag_pagos DISABLE TRIGGER pag_pagos_ciclo_de_vida');
        DB::statement('UPDATE pag_pagos SET medio_pago_id = m.id FROM pag_medios_pago m WHERE m.codigo = pag_pagos.medio_pago');
        DB::statement('ALTER TABLE pag_pagos ENABLE TRIGGER pag_pagos_ciclo_de_vida');

        DB::statement('DROP INDEX pag_pagos_operacion_unique');
        DB::statement('ALTER TABLE pag_pagos ALTER COLUMN medio_pago_id SET NOT NULL');
        DB::statement('ALTER TABLE pag_pagos DROP COLUMN medio_pago');
        DB::statement("CREATE UNIQUE INDEX pag_pagos_operacion_unique ON pag_pagos (medio_pago_id, numero_operacion) WHERE estado <> 'rechazado'");
    }

    public function down(): void
    {
        $anteriores = "'".implode("', '", self::CODIGOS_ANTERIORES)."'";

        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->enum('medio_pago', self::CODIGOS_ANTERIORES)->nullable();
        });

        DB::statement('ALTER TABLE pag_pagos DISABLE TRIGGER pag_pagos_ciclo_de_vida');
        DB::statement("UPDATE pag_pagos SET medio_pago = CASE WHEN m.codigo IN ({$anteriores}) THEN m.codigo ELSE 'otro' END FROM pag_medios_pago m WHERE m.id = pag_pagos.medio_pago_id");
        DB::statement('ALTER TABLE pag_pagos ENABLE TRIGGER pag_pagos_ciclo_de_vida');

        DB::statement('DROP INDEX pag_pagos_operacion_unique');
        DB::statement('ALTER TABLE pag_pagos ALTER COLUMN medio_pago SET NOT NULL');
        DB::statement("CREATE UNIQUE INDEX pag_pagos_operacion_unique ON pag_pagos (medio_pago, numero_operacion) WHERE estado <> 'rechazado'");

        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('medio_pago_id');
        });

        Schema::drop('pag_medios_pago');
    }
};
