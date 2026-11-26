<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> code => name, in the order the forms offer them */
    private const array VINCULOS = [
        'estudiante_unasam' => 'Estudiante UNASAM',
        'docente_administrativo_unasam' => 'Docente/Administrativo UNASAM',
        'publico_general' => 'Público general',
    ];

    private const string POR_DEFECTO = 'publico_general';

    /** @var array<string, array{0: string, 1: string}> table => [comment with the catalog, comment before it] */
    private const array COMENTARIOS = [
        'per_personas' => [
            'Vínculo con la UNASAM, que define la tarifa. Referencia a `per_tipos_vinculo` por su código.',
            'Vínculo con la UNASAM, que define la tarifa: estudiante, docente o administrativo, o público general.',
        ],
        'pag_tarifas' => [
            'Vínculo de la persona al que se aplica. Referencia a `per_tipos_vinculo` por su código.',
            'Vínculo de la persona al que se aplica.',
        ],
    ];

    public function up(): void
    {
        Schema::create('per_tipos_vinculo', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 80)->unique();
            $table->boolean('activo')->default(true);
            $table->boolean('por_defecto')->default(false);
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE per_tipos_vinculo ADD CONSTRAINT per_tipos_vinculo_codigo_check CHECK (codigo ~ '^[a-z0-9_]+$')");
        DB::statement("ALTER TABLE per_tipos_vinculo ADD CONSTRAINT per_tipos_vinculo_nombre_check CHECK (btrim(nombre) <> '')");
        DB::statement('ALTER TABLE per_tipos_vinculo ADD CONSTRAINT per_tipos_vinculo_orden_check CHECK (orden >= 0)');
        // Self-registration takes the default, so it must stay offered.
        DB::statement('ALTER TABLE per_tipos_vinculo ADD CONSTRAINT per_tipos_vinculo_por_defecto_activo_check CHECK (NOT por_defecto OR activo)');
        DB::statement('CREATE UNIQUE INDEX per_tipos_vinculo_por_defecto_unique ON per_tipos_vinculo (por_defecto) WHERE por_defecto');
        DB::statement('CREATE TRIGGER per_tipos_vinculo_auditoria AFTER INSERT OR UPDATE OR DELETE ON per_tipos_vinculo FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        $orden = 0;
        DB::table('per_tipos_vinculo')->insert(array_map(function (string $codigo, string $nombre) use (&$orden): array {
            return [
                'codigo' => $codigo, 'nombre' => $nombre, 'orden' => ++$orden, 'activo' => true,
                'por_defecto' => $codigo === self::POR_DEFECTO, 'created_at' => now(), 'updated_at' => now(),
            ];
        }, array_keys(self::VINCULOS), self::VINCULOS));

        foreach (array_keys(self::COMENTARIOS) as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT {$tabla}_tipo_vinculo_check");
            DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_tipo_vinculo_foreign FOREIGN KEY (tipo_vinculo) REFERENCES per_tipos_vinculo (codigo) ON UPDATE RESTRICT ON DELETE RESTRICT");
            DB::statement("CREATE INDEX {$tabla}_tipo_vinculo_index ON {$tabla} (tipo_vinculo)");
        }

        $this->comentar(0);
        DB::unprepared(<<<'SQL'
            COMMENT ON TABLE per_tipos_vinculo IS 'Tipos de vínculo con la UNASAM que el CILN administra; cada persona tiene uno y define la tarifa que paga.';
            COMMENT ON COLUMN per_tipos_vinculo.id IS 'Identificador interno.';
            COMMENT ON COLUMN per_tipos_vinculo.codigo IS 'Código único del vínculo; se genera al crearlo y no cambia.';
            COMMENT ON COLUMN per_tipos_vinculo.nombre IS 'Nombre único del vínculo, como aparece en el portal y en los reportes.';
            COMMENT ON COLUMN per_tipos_vinculo.activo IS 'Indica si se ofrece en los formularios y admite tarifas nuevas.';
            COMMENT ON COLUMN per_tipos_vinculo.por_defecto IS 'Vínculo que recibe quien crea su cuenta en el portal; solo uno a la vez y siempre activo.';
            COMMENT ON COLUMN per_tipos_vinculo.orden IS 'Orden en las listas y en la tabla de tarifas del portal.';
            COMMENT ON COLUMN per_tipos_vinculo.created_at IS 'Fecha y hora de registro.';
            COMMENT ON COLUMN per_tipos_vinculo.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        $anteriores = "'".implode("', '", array_keys(self::VINCULOS))."'";

        foreach (array_keys(self::COMENTARIOS) as $tabla) {
            DB::statement("DROP INDEX {$tabla}_tipo_vinculo_index");
            DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT {$tabla}_tipo_vinculo_foreign");
            // A fee for a vínculo created later blocks the rollback here instead of being lost.
            DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_tipo_vinculo_check CHECK (tipo_vinculo::text IN ({$anteriores}))");
        }

        $this->comentar(1);
        Schema::drop('per_tipos_vinculo');
    }

    private function comentar(int $version): void
    {
        foreach (self::COMENTARIOS as $tabla => $textos) {
            DB::statement(sprintf('COMMENT ON COLUMN %s.tipo_vinculo IS %s', $tabla, DB::getPdo()->quote($textos[$version])));
        }
    }
};
