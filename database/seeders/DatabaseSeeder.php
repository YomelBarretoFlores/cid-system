<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academico\Database\Seeders\AcademicoDemoSeeder;
use App\Modules\Matricula\Database\Seeders\MatriculaDemoSeeder;
use App\Modules\Personas\Database\Seeders\PersonasDemoSeeder;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Permission;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        throw_if(app()->isProduction(), RuntimeException::class, 'Demo data must never be seeded in production.');

        Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION);

        User::factory()->create([
            'name' => 'Administrador Demo',
            'email' => 'admin@ciln.test',
        ])->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

        $this->call([
            AcademicoDemoSeeder::class,
            PersonasDemoSeeder::class,
            MatriculaDemoSeeder::class,
        ]);
    }
}
