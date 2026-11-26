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
        Schema::create('pag_configuracion', function (Blueprint $table): void {
            $table->smallInteger('id')->primary()->default(1);
            $table->smallInteger('recargo_matricula_extemporanea')->default(20);
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE pag_configuracion ADD CONSTRAINT pag_configuracion_unica_check CHECK (id = 1)');
        DB::statement('ALTER TABLE pag_configuracion ADD CONSTRAINT pag_configuracion_recargo_check CHECK (recargo_matricula_extemporanea BETWEEN 0 AND 100)');
        DB::statement('CREATE TRIGGER pag_configuracion_auditoria AFTER INSERT OR UPDATE OR DELETE ON pag_configuracion FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        DB::table('pag_configuracion')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        DB::unprepared(<<<'SQL'
            COMMENT ON TABLE pag_configuracion IS 'Parámetros de pagos que configura el CILN; una sola fila.';
            COMMENT ON COLUMN pag_configuracion.id IS 'Identificador de la única fila; siempre 1.';
            COMMENT ON COLUMN pag_configuracion.recargo_matricula_extemporanea IS 'Porcentaje entero (0 a 100) que se suma a la tarifa de matrícula pagada en el plazo de matrícula extemporánea del periodo.';
            COMMENT ON COLUMN pag_configuracion.created_at IS 'Fecha y hora de creación de la fila.';
            COMMENT ON COLUMN pag_configuracion.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pag_configuracion');
    }
};
