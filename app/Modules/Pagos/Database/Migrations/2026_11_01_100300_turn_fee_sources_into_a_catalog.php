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
        Schema::create('pag_fuentes_concepto', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 60)->unique();
            $table->boolean('activo')->default(true);
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE pag_fuentes_concepto ADD CONSTRAINT pag_fuentes_concepto_codigo_check CHECK (codigo ~ '^[a-z0-9_]+$')");
        DB::statement("ALTER TABLE pag_fuentes_concepto ADD CONSTRAINT pag_fuentes_concepto_nombre_check CHECK (btrim(nombre) <> '')");
        DB::statement('ALTER TABLE pag_fuentes_concepto ADD CONSTRAINT pag_fuentes_concepto_orden_check CHECK (orden >= 0)');
        DB::statement('CREATE TRIGGER pag_fuentes_concepto_auditoria AFTER INSERT OR UPDATE OR DELETE ON pag_fuentes_concepto FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        DB::table('pag_fuentes_concepto')->insert([
            ['codigo' => 'tupa', 'nombre' => 'TUPA', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'tusne', 'nombre' => 'TUSNE', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('pag_conceptos', function (Blueprint $table): void {
            $table->foreignId('fuente_id')->nullable()->constrained('pag_fuentes_concepto')->restrictOnDelete();
            $table->index('fuente_id');
        });

        DB::statement('UPDATE pag_conceptos SET fuente_id = f.id FROM pag_fuentes_concepto f WHERE f.codigo = pag_conceptos.fuente');
        DB::statement('ALTER TABLE pag_conceptos ALTER COLUMN fuente_id SET NOT NULL');
        DB::statement('ALTER TABLE pag_conceptos DROP COLUMN fuente');
    }

    public function down(): void
    {
        Schema::table('pag_conceptos', function (Blueprint $table): void {
            $table->enum('fuente', ['tupa', 'tusne'])->nullable();
        });

        DB::statement("UPDATE pag_conceptos SET fuente = CASE WHEN f.codigo = 'tusne' THEN 'tusne' ELSE 'tupa' END FROM pag_fuentes_concepto f WHERE f.id = pag_conceptos.fuente_id");
        DB::statement('ALTER TABLE pag_conceptos ALTER COLUMN fuente SET NOT NULL');

        Schema::table('pag_conceptos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fuente_id');
        });

        Schema::drop('pag_fuentes_concepto');
    }
};
