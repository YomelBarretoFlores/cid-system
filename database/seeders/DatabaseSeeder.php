<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academico\Database\Seeders\AcademicoDemoSeeder;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Matricula\Database\Seeders\MatriculaDemoSeeder;
use App\Modules\Pagos\Database\Seeders\PagosDemoSeeder;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Personas\Database\Seeders\PersonasDemoSeeder;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        throw_if(app()->isProduction(), RuntimeException::class, 'Demo data must never be seeded in production.');

        User::factory()->create([
            'name' => 'Administrador Demo',
            'email' => 'admin@ciln.test',
        ])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoPago::valores(), ...PermisoAcademico::valores(), ...PermisoPersona::valores()]);

        User::factory()->create([
            'name' => 'Caja Demo',
            'email' => 'caja@ciln.test',
        ])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Registrar->value, PermisoPago::Validar->value, ...PermisoPersona::valores()]);

        $this->call([
            AcademicoDemoSeeder::class,
            PersonasDemoSeeder::class,
            MatriculaDemoSeeder::class,
            PagosDemoSeeder::class,
        ]);
    }
}
