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
        Schema::create('per_personas', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->enum('tipo_documento', ['dni', 'ce', 'pasaporte']);
            $table->string('numero_documento', 12);
            $table->string('apellido_paterno', 60);
            $table->string('apellido_materno', 60)->nullable();
            $table->string('nombres', 80);
            $table->date('fecha_nacimiento')->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('celular', 16)->nullable();
            $table->enum('tipo_vinculo', ['estudiante_unasam', 'docente_administrativo_unasam', 'publico_general'])->default('publico_general');
            $table->timestampsTz();

            $table->unique(['tipo_documento', 'numero_documento']);
            $table->index(['apellido_paterno', 'apellido_materno', 'nombres']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE per_personas ADD CONSTRAINT per_personas_documento_check CHECK (
                (tipo_documento = 'dni' AND numero_documento ~ '^[0-9]{8}$')
                OR (tipo_documento <> 'dni' AND numero_documento ~ '^[A-Z0-9]{6,12}$')
            )
            SQL);
        DB::statement("ALTER TABLE per_personas ADD CONSTRAINT per_personas_celular_check CHECK (celular IS NULL OR celular ~ '^\\+?[0-9]{7,15}$')");
        DB::statement('ALTER TABLE per_personas ADD CONSTRAINT per_personas_fecha_nacimiento_check CHECK (fecha_nacimiento IS NULL OR fecha_nacimiento > DATE \'1900-01-01\')');
    }

    public function down(): void
    {
        Schema::dropIfExists('per_personas');
    }
};
