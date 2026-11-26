<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{0: string, 1: bool, 2: int, 3: int}> code => [name, only digits, minimum length, maximum length], in form order */
    private const array DOCUMENTOS = [
        'dni' => ['DNI', true, 8, 8],
        'ce' => ['Carné de Extranjería', false, 6, 12],
        'pasaporte' => ['Pasaporte', false, 6, 12],
    ];

    private const string POR_DEFECTO = 'dni';

    public function up(): void
    {
        Schema::create('per_tipos_documento', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 60)->unique();
            $table->boolean('solo_digitos')->default(false);
            $table->smallInteger('longitud_minima');
            $table->smallInteger('longitud_maxima');
            $table->boolean('activo')->default(true);
            $table->boolean('por_defecto')->default(false);
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE per_tipos_documento ADD CONSTRAINT per_tipos_documento_codigo_check CHECK (codigo ~ '^[a-z0-9_]+$')");
        DB::statement("ALTER TABLE per_tipos_documento ADD CONSTRAINT per_tipos_documento_nombre_check CHECK (btrim(nombre) <> '')");
        DB::statement('ALTER TABLE per_tipos_documento ADD CONSTRAINT per_tipos_documento_orden_check CHECK (orden >= 0)');
        DB::statement('ALTER TABLE per_tipos_documento ADD CONSTRAINT per_tipos_documento_longitud_check CHECK (1 <= longitud_minima AND longitud_minima <= longitud_maxima AND longitud_maxima <= 20)');
        DB::statement('ALTER TABLE per_tipos_documento ADD CONSTRAINT per_tipos_documento_por_defecto_activo_check CHECK (NOT por_defecto OR activo)');
        DB::statement('CREATE UNIQUE INDEX per_tipos_documento_por_defecto_unique ON per_tipos_documento (por_defecto) WHERE por_defecto');
        DB::statement('CREATE TRIGGER per_tipos_documento_auditoria AFTER INSERT OR UPDATE OR DELETE ON per_tipos_documento FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        $orden = 0;
        foreach (self::DOCUMENTOS as $codigo => [$nombre, $soloDigitos, $minima, $maxima]) {
            DB::table('per_tipos_documento')->insert([
                'codigo' => $codigo, 'nombre' => $nombre, 'solo_digitos' => $soloDigitos, 'longitud_minima' => $minima, 'longitud_maxima' => $maxima,
                'orden' => ++$orden, 'activo' => true, 'por_defecto' => $codigo === self::POR_DEFECTO, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::statement('ALTER TABLE per_personas DROP CONSTRAINT per_personas_tipo_documento_check');
        DB::statement('ALTER TABLE per_personas DROP CONSTRAINT per_personas_documento_check');
        DB::statement('ALTER TABLE per_personas ALTER COLUMN numero_documento TYPE varchar(20)');
        // The unique (tipo_documento, numero_documento) index already serves this key.
        DB::statement('ALTER TABLE per_personas ADD CONSTRAINT per_personas_tipo_documento_foreign FOREIGN KEY (tipo_documento) REFERENCES per_tipos_documento (codigo) ON UPDATE RESTRICT ON DELETE RESTRICT');

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN per_personas.tipo_documento IS 'Tipo de documento de identidad. Referencia a `per_tipos_documento` por su código.';
            COMMENT ON TABLE per_tipos_documento IS 'Tipos de documento de identidad que el CILN administra, con la regla de formato de su número.';
            COMMENT ON COLUMN per_tipos_documento.id IS 'Identificador interno.';
            COMMENT ON COLUMN per_tipos_documento.codigo IS 'Código único del tipo de documento; se genera al crearlo y no cambia.';
            COMMENT ON COLUMN per_tipos_documento.nombre IS 'Nombre único del tipo de documento, como aparece en los formularios, las fichas y los documentos emitidos.';
            COMMENT ON COLUMN per_tipos_documento.solo_digitos IS 'Indica si el número admite solo dígitos; si no, admite letras y dígitos sin espacios ni guiones.';
            COMMENT ON COLUMN per_tipos_documento.longitud_minima IS 'Cantidad mínima de caracteres del número.';
            COMMENT ON COLUMN per_tipos_documento.longitud_maxima IS 'Cantidad máxima de caracteres del número, hasta 20.';
            COMMENT ON COLUMN per_tipos_documento.activo IS 'Indica si se ofrece en los formularios para registros nuevos.';
            COMMENT ON COLUMN per_tipos_documento.por_defecto IS 'Tipo de documento preseleccionado al crear una cuenta en el portal; solo uno a la vez y siempre activo.';
            COMMENT ON COLUMN per_tipos_documento.orden IS 'Orden en las listas.';
            COMMENT ON COLUMN per_tipos_documento.created_at IS 'Fecha y hora de registro.';
            COMMENT ON COLUMN per_tipos_documento.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        $anteriores = "'".implode("', '", array_keys(self::DOCUMENTOS))."'";

        DB::statement('ALTER TABLE per_personas DROP CONSTRAINT per_personas_tipo_documento_foreign');
        // A person with a type or a number allowed only by the catalog blocks the rollback here instead of being lost.
        DB::statement("ALTER TABLE per_personas ADD CONSTRAINT per_personas_tipo_documento_check CHECK (tipo_documento::text IN ({$anteriores}))");
        DB::statement(<<<'SQL'
            ALTER TABLE per_personas ADD CONSTRAINT per_personas_documento_check CHECK (
                (tipo_documento = 'dni' AND numero_documento ~ '^[0-9]{8}$')
                OR (tipo_documento <> 'dni' AND numero_documento ~ '^[A-Z0-9]{6,12}$')
            )
            SQL);
        DB::statement('ALTER TABLE per_personas ALTER COLUMN numero_documento TYPE varchar(12)');
        DB::statement("COMMENT ON COLUMN per_personas.tipo_documento IS 'Tipo de documento de identidad.'");
        Schema::drop('per_tipos_documento');
    }
};
