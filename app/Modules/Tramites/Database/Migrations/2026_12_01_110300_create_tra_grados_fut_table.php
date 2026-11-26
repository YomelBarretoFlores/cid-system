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
        Schema::create('tra_grados_fut', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 60)->unique();
            $table->string('titulo_otorgado', 60);
            $table->boolean('activo')->default(true);
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE tra_grados_fut ADD CONSTRAINT tra_grados_fut_codigo_check CHECK (codigo ~ '^[a-z0-9_]+$')");
        DB::statement("ALTER TABLE tra_grados_fut ADD CONSTRAINT tra_grados_fut_nombre_check CHECK (btrim(nombre) <> '' AND btrim(titulo_otorgado) <> '')");
        DB::statement('ALTER TABLE tra_grados_fut ADD CONSTRAINT tra_grados_fut_orden_check CHECK (orden >= 0)');
        DB::statement('CREATE TRIGGER tra_grados_fut_auditoria AFTER INSERT OR UPDATE OR DELETE ON tra_grados_fut FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        $ahora = now();
        DB::table('tra_grados_fut')->insert([
            ['codigo' => 'maestria', 'nombre' => 'Maestría', 'titulo_otorgado' => 'Maestro', 'activo' => true, 'orden' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['codigo' => 'doctorado', 'nombre' => 'Doctorado', 'titulo_otorgado' => 'Doctor', 'activo' => true, 'orden' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        DB::unprepared(<<<'SQL'
            COMMENT ON TABLE tra_grados_fut IS 'Grados de posgrado que el FUT ofrece como mención de la acreditación de idioma extranjero.';
            COMMENT ON COLUMN tra_grados_fut.id IS 'Identificador interno.';
            COMMENT ON COLUMN tra_grados_fut.codigo IS 'Código único del grado; se guarda en el trámite y no cambia.';
            COMMENT ON COLUMN tra_grados_fut.nombre IS 'Nombre único del grado, como se elige en el FUT (Maestría).';
            COMMENT ON COLUMN tra_grados_fut.titulo_otorgado IS 'Grado que obtiene quien lo concluye, como lo imprime la acreditación (Maestro).';
            COMMENT ON COLUMN tra_grados_fut.activo IS 'Indica si el FUT lo ofrece.';
            COMMENT ON COLUMN tra_grados_fut.orden IS 'Orden en la lista del FUT.';
            COMMENT ON COLUMN tra_grados_fut.created_at IS 'Fecha y hora de registro.';
            COMMENT ON COLUMN tra_grados_fut.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        Schema::drop('tra_grados_fut');
    }
};
