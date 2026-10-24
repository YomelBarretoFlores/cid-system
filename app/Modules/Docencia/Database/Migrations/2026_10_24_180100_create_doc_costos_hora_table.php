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
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('doc_costos_hora', function (Blueprint $table): void {
            $table->id();
            $table->enum('condicion_laboral', ['nombrado', 'contratado', 'tercero']);
            $table->decimal('costo_hora', 10, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->foreignId('cerro_costo_id')->nullable()->constrained('doc_costos_hora')->restrictOnDelete();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index('cerro_costo_id');
            $table->index('registrado_por');
        });

        DB::statement('ALTER TABLE doc_costos_hora ADD CONSTRAINT doc_costos_hora_costo_check CHECK (costo_hora > 0)');
        DB::statement('ALTER TABLE doc_costos_hora ADD CONSTRAINT doc_costos_hora_vigencia_check CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
        DB::statement("ALTER TABLE doc_costos_hora ADD CONSTRAINT doc_costos_hora_sin_superposicion EXCLUDE USING gist (condicion_laboral WITH =, daterange(vigente_desde, vigente_hasta, '[]') WITH &&)");

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER doc_costos_hora_auditoria AFTER INSERT OR UPDATE OR DELETE ON doc_costos_hora
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_costos_hora');
    }
};
