<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, string>> table => code => name, in the order the forms offer them */
    private const array CATALOGOS = [
        'doc_condiciones_laborales' => ['nombrado' => 'Nombrado', 'contratado' => 'Contratado', 'tercero' => 'Tercero'],
        'doc_grados_academicos' => ['certificado' => 'Certificado', 'bachiller' => 'Bachiller', 'titulado' => 'Titulado', 'magister' => 'Magíster', 'doctor' => 'Doctor'],
    ];

    public function up(): void
    {
        foreach (self::CATALOGOS as $tabla => $filas) {
            $this->catalogo($tabla, $filas);
        }

        Schema::table('doc_docentes', function (Blueprint $table): void {
            $table->foreignId('condicion_laboral_id')->nullable()->constrained('doc_condiciones_laborales')->restrictOnDelete();
            $table->foreignId('grado_academico_id')->nullable()->constrained('doc_grados_academicos')->restrictOnDelete();
            $table->index('condicion_laboral_id');
            $table->index('grado_academico_id');
        });

        DB::statement('UPDATE doc_docentes SET condicion_laboral_id = c.id FROM doc_condiciones_laborales c WHERE c.codigo = doc_docentes.condicion_laboral');
        DB::statement('ALTER TABLE doc_docentes ALTER COLUMN condicion_laboral_id SET NOT NULL');
        DB::statement('ALTER TABLE doc_docentes DROP COLUMN condicion_laboral');

        // Rates registered by employment condition stay as history; new rates are registered by academic degree.
        Schema::table('doc_costos_hora', function (Blueprint $table): void {
            $table->foreignId('condicion_laboral_id')->nullable()->constrained('doc_condiciones_laborales')->restrictOnDelete();
            $table->foreignId('grado_academico_id')->nullable()->constrained('doc_grados_academicos')->restrictOnDelete();
            $table->index('condicion_laboral_id');
            $table->index('grado_academico_id');
        });

        DB::statement('UPDATE doc_costos_hora SET condicion_laboral_id = c.id FROM doc_condiciones_laborales c WHERE c.codigo = doc_costos_hora.condicion_laboral');
        DB::statement('ALTER TABLE doc_costos_hora DROP CONSTRAINT doc_costos_hora_sin_superposicion');
        DB::statement('ALTER TABLE doc_costos_hora DROP COLUMN condicion_laboral');
        DB::statement('ALTER TABLE doc_costos_hora ADD CONSTRAINT doc_costos_hora_categoria_check CHECK ((condicion_laboral_id IS NULL) <> (grado_academico_id IS NULL))');
        DB::statement("ALTER TABLE doc_costos_hora ADD CONSTRAINT doc_costos_hora_condicion_sin_superposicion EXCLUDE USING gist (condicion_laboral_id WITH =, daterange(vigente_desde, vigente_hasta, '[]') WITH &&) WHERE (condicion_laboral_id IS NOT NULL)");
        DB::statement("ALTER TABLE doc_costos_hora ADD CONSTRAINT doc_costos_hora_grado_sin_superposicion EXCLUDE USING gist (grado_academico_id WITH =, daterange(vigente_desde, vigente_hasta, '[]') WITH &&) WHERE (grado_academico_id IS NOT NULL)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE doc_costos_hora DROP CONSTRAINT IF EXISTS doc_costos_hora_grado_sin_superposicion');
        DB::statement('ALTER TABLE doc_costos_hora DROP CONSTRAINT IF EXISTS doc_costos_hora_condicion_sin_superposicion');
        DB::statement('ALTER TABLE doc_costos_hora DROP CONSTRAINT IF EXISTS doc_costos_hora_categoria_check');
        // Degree rates have no place in the earlier shape.
        DB::statement('DELETE FROM doc_costos_hora WHERE grado_academico_id IS NOT NULL');

        Schema::table('doc_costos_hora', function (Blueprint $table): void {
            $table->enum('condicion_laboral', ['nombrado', 'contratado', 'tercero'])->nullable();
        });
        DB::statement("UPDATE doc_costos_hora SET condicion_laboral = CASE WHEN c.codigo IN ('nombrado', 'contratado', 'tercero') THEN c.codigo ELSE 'tercero' END FROM doc_condiciones_laborales c WHERE c.id = doc_costos_hora.condicion_laboral_id");
        DB::statement('ALTER TABLE doc_costos_hora ALTER COLUMN condicion_laboral SET NOT NULL');
        DB::statement("ALTER TABLE doc_costos_hora ADD CONSTRAINT doc_costos_hora_sin_superposicion EXCLUDE USING gist (condicion_laboral WITH =, daterange(vigente_desde, vigente_hasta, '[]') WITH &&)");

        Schema::table('doc_docentes', function (Blueprint $table): void {
            $table->string('condicion_laboral', 20)->nullable();
        });
        DB::statement("UPDATE doc_docentes SET condicion_laboral = CASE WHEN c.codigo IN ('nombrado', 'contratado', 'tercero') THEN c.codigo ELSE 'tercero' END FROM doc_condiciones_laborales c WHERE c.id = doc_docentes.condicion_laboral_id");
        DB::statement('ALTER TABLE doc_docentes ALTER COLUMN condicion_laboral SET NOT NULL');
        DB::statement("ALTER TABLE doc_docentes ADD CONSTRAINT doc_docentes_condicion_valida_check CHECK (condicion_laboral IN ('nombrado', 'contratado', 'tercero'))");

        foreach (['doc_costos_hora', 'doc_docentes'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('grado_academico_id');
                $table->dropConstrainedForeignId('condicion_laboral_id');
            });
        }

        Schema::drop('doc_grados_academicos');
        Schema::drop('doc_condiciones_laborales');
    }

    /**
     * @param  array<string, string>  $filas
     */
    private function catalogo(string $tabla, array $filas): void
    {
        Schema::create($tabla, function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 60)->unique();
            $table->boolean('activo')->default(true);
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_codigo_check CHECK (codigo ~ '^[a-z0-9_]+$')");
        DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_nombre_check CHECK (btrim(nombre) <> '')");
        DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_orden_check CHECK (orden >= 0)");
        DB::statement("CREATE TRIGGER {$tabla}_auditoria AFTER INSERT OR UPDATE OR DELETE ON {$tabla} FOR EACH ROW EXECUTE FUNCTION aud_registrar()");

        $orden = 0;
        DB::table($tabla)->insert(array_map(function (string $codigo, string $nombre) use (&$orden): array {
            return ['codigo' => $codigo, 'nombre' => $nombre, 'orden' => ++$orden, 'activo' => true, 'created_at' => now(), 'updated_at' => now()];
        }, array_keys($filas), $filas));
    }
};
