<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{nombre: string, descripcion: string, usa_aula: bool, usa_enlace: bool}> code => row, in the order the forms offer them */
    private const array MODALIDADES = [
        'presencial' => ['nombre' => 'Presencial', 'descripcion' => 'En las aulas del CILN, con su grupo y su docente.', 'usa_aula' => true, 'usa_enlace' => false],
        'virtual' => ['nombre' => 'Virtual', 'descripcion' => 'Clases en línea con el enlace del grupo.', 'usa_aula' => false, 'usa_enlace' => true],
        'semipresencial' => ['nombre' => 'Semipresencial', 'descripcion' => 'Sesiones en aula y sesiones en línea.', 'usa_aula' => true, 'usa_enlace' => true],
    ];

    public function up(): void
    {
        Schema::create('aca_modalidades', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 60)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('usa_aula');
            $table->boolean('usa_enlace');
            $table->boolean('activo')->default(true);
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE aca_modalidades ADD CONSTRAINT aca_modalidades_codigo_check CHECK (codigo ~ '^[a-z0-9_]+$')");
        DB::statement("ALTER TABLE aca_modalidades ADD CONSTRAINT aca_modalidades_nombre_check CHECK (btrim(nombre) <> '')");
        DB::statement('ALTER TABLE aca_modalidades ADD CONSTRAINT aca_modalidades_orden_check CHECK (orden >= 0)');
        // A group meets somewhere: in a classroom, online, or both.
        DB::statement('ALTER TABLE aca_modalidades ADD CONSTRAINT aca_modalidades_lugar_check CHECK (usa_aula OR usa_enlace)');
        DB::statement('CREATE TRIGGER aca_modalidades_auditoria AFTER INSERT OR UPDATE OR DELETE ON aca_modalidades FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        $orden = 0;
        foreach (self::MODALIDADES as $codigo => $fila) {
            DB::table('aca_modalidades')->insert([...$fila, 'codigo' => $codigo, 'activo' => true, 'orden' => ++$orden, 'created_at' => now(), 'updated_at' => now()]);
        }

        DB::statement('ALTER TABLE aca_grupos DROP CONSTRAINT aca_grupos_modalidad_check');
        DB::statement('ALTER TABLE aca_grupos ADD CONSTRAINT aca_grupos_modalidad_foreign FOREIGN KEY (modalidad) REFERENCES aca_modalidades (codigo) ON UPDATE RESTRICT ON DELETE RESTRICT');
        DB::statement('CREATE INDEX aca_grupos_modalidad_index ON aca_grupos (modalidad)');

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN aca_grupos.modalidad IS 'Modalidad de dictado. Referencia a `aca_modalidades` por su código.';
            COMMENT ON TABLE aca_modalidades IS 'Modalidades de dictado que el CILN administra; cada grupo tiene una y el portal muestra su descripción.';
            COMMENT ON COLUMN aca_modalidades.id IS 'Identificador interno.';
            COMMENT ON COLUMN aca_modalidades.codigo IS 'Código único de la modalidad; se genera al crearla y no cambia.';
            COMMENT ON COLUMN aca_modalidades.nombre IS 'Nombre único de la modalidad, como aparece en el portal y en los documentos.';
            COMMENT ON COLUMN aca_modalidades.descripcion IS 'Descripción que muestra la página pública.';
            COMMENT ON COLUMN aca_modalidades.usa_aula IS 'Indica si sus grupos se dictan en un aula; si no, el grupo necesita el enlace de la clase.';
            COMMENT ON COLUMN aca_modalidades.usa_enlace IS 'Indica si sus grupos admiten el enlace de una clase en línea.';
            COMMENT ON COLUMN aca_modalidades.activo IS 'Indica si se ofrece para grupos nuevos.';
            COMMENT ON COLUMN aca_modalidades.orden IS 'Orden en las listas y en la página pública.';
            COMMENT ON COLUMN aca_modalidades.created_at IS 'Fecha y hora de registro.';
            COMMENT ON COLUMN aca_modalidades.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX aca_grupos_modalidad_index');
        DB::statement('ALTER TABLE aca_grupos DROP CONSTRAINT aca_grupos_modalidad_foreign');
        // A group of a modality created later blocks the rollback here instead of being lost.
        DB::statement("ALTER TABLE aca_grupos ADD CONSTRAINT aca_grupos_modalidad_check CHECK (modalidad::text IN ('presencial', 'virtual', 'semipresencial'))");
        DB::statement("COMMENT ON COLUMN aca_grupos.modalidad IS 'Modalidad de dictado.'");
        Schema::drop('aca_modalidades');
    }
};
