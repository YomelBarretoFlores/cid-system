<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, string> code => name, the banks suggested before the catalog */
    private const array BANCOS = [
        'banco_de_la_nacion' => 'Banco de la Nación',
        'bcp' => 'BCP',
        'bbva' => 'BBVA',
        'interbank' => 'Interbank',
        'scotiabank' => 'Scotiabank',
        'banco_pichincha' => 'Banco Pichincha',
        'banbif' => 'BanBif',
        'caja_arequipa' => 'Caja Arequipa',
        'caja_huancayo' => 'Caja Huancayo',
        'caja_piura' => 'Caja Piura',
        'caja_trujillo' => 'Caja Trujillo',
        'caja_cusco' => 'Caja Cusco',
    ];

    public function up(): void
    {
        Schema::create('doc_bancos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 80)->unique();
            $table->boolean('activo')->default(true);
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE doc_bancos ADD CONSTRAINT doc_bancos_codigo_check CHECK (codigo ~ '^[a-z0-9_]+$')");
        DB::statement("ALTER TABLE doc_bancos ADD CONSTRAINT doc_bancos_nombre_check CHECK (btrim(nombre) <> '')");
        DB::statement('ALTER TABLE doc_bancos ADD CONSTRAINT doc_bancos_orden_check CHECK (orden >= 0)');
        DB::statement('CREATE TRIGGER doc_bancos_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_bancos FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        $orden = 0;
        foreach (self::BANCOS as $codigo => $nombre) {
            $this->insertar($codigo, $nombre, ++$orden);
        }

        // Banks typed freely before the catalog join it, so every teacher keeps the bank on file.
        foreach (DB::table('doc_docentes')->whereNotIn('banco', array_values(self::BANCOS))->distinct()->pluck('banco') as $nombre) {
            $this->insertar($this->codigoLibre((string) $nombre), (string) $nombre, ++$orden);
        }

        DB::statement('UPDATE doc_docentes SET banco = b.codigo FROM doc_bancos b WHERE b.nombre = doc_docentes.banco');
        DB::statement('ALTER TABLE doc_docentes ALTER COLUMN banco TYPE varchar(40)');
        DB::statement('ALTER TABLE doc_docentes ADD CONSTRAINT doc_docentes_banco_foreign FOREIGN KEY (banco) REFERENCES doc_bancos (codigo) ON UPDATE RESTRICT ON DELETE RESTRICT');
        DB::statement('CREATE INDEX doc_docentes_banco_index ON doc_docentes (banco)');

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN doc_docentes.banco IS 'Banco de la cuenta de abono. Referencia a `doc_bancos` por su código.';
            COMMENT ON TABLE doc_bancos IS 'Bancos y cajas donde los docentes reciben su abono; el CILN los administra.';
            COMMENT ON COLUMN doc_bancos.id IS 'Identificador interno.';
            COMMENT ON COLUMN doc_bancos.codigo IS 'Código único del banco; se genera al crearlo y no cambia.';
            COMMENT ON COLUMN doc_bancos.nombre IS 'Nombre único del banco, como aparece en el padrón docente.';
            COMMENT ON COLUMN doc_bancos.activo IS 'Indica si se ofrece en el formulario del docente.';
            COMMENT ON COLUMN doc_bancos.orden IS 'Orden en las listas.';
            COMMENT ON COLUMN doc_bancos.created_at IS 'Fecha y hora de registro.';
            COMMENT ON COLUMN doc_bancos.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX doc_docentes_banco_index');
        DB::statement('ALTER TABLE doc_docentes DROP CONSTRAINT doc_docentes_banco_foreign');
        DB::statement('ALTER TABLE doc_docentes ALTER COLUMN banco TYPE varchar(80)');
        DB::statement('UPDATE doc_docentes SET banco = b.nombre FROM doc_bancos b WHERE b.codigo = doc_docentes.banco');
        DB::statement("COMMENT ON COLUMN doc_docentes.banco IS 'Banco de la cuenta de abono.'");
        Schema::drop('doc_bancos');
    }

    private function insertar(string $codigo, string $nombre, int $orden): void
    {
        DB::table('doc_bancos')->insert(['codigo' => $codigo, 'nombre' => $nombre, 'activo' => true, 'orden' => $orden, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function codigoLibre(string $nombre): string
    {
        $base = rtrim(mb_substr(Str::slug($nombre, '_'), 0, 36), '_');
        $base = $base === '' ? 'item' : $base;
        $codigo = $base;

        for ($sufijo = 2; DB::table('doc_bancos')->where('codigo', $codigo)->exists(); $sufijo++) {
            $codigo = "{$base}_{$sufijo}";
        }

        return $codigo;
    }
};
