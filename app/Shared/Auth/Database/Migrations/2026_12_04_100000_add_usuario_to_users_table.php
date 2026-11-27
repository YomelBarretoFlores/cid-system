<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Teacher accounts sign in with the identity document CILN gives them and may have no email.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('usuario', 20)->nullable()->unique();
            $table->string('email')->nullable()->change();
        });

        DB::statement('ALTER TABLE users ADD CONSTRAINT users_usuario_o_correo CHECK (usuario IS NOT NULL OR email IS NOT NULL)');
        DB::statement("COMMENT ON COLUMN users.usuario IS 'Usuario de ingreso que el CILN asigna a un docente: su número de documento; nulo en las demás cuentas.'");
        DB::statement("COMMENT ON COLUMN users.email IS 'Correo de ingreso, en minúsculas; nulo en una cuenta de docente que solo tiene usuario.'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_usuario_o_correo');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['usuario']);
            $table->dropColumn('usuario');
            $table->string('email')->nullable(false)->change();
        });
    }
};
