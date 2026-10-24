<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academico\Database\Seeders\AcademicoDemoSeeder;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Matricula\Database\Seeders\MatriculaDemoSeeder;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Pagos\Database\Seeders\PagosDemoSeeder;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Database\Seeders\PersonasDemoSeeder;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
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
        ])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoPago::valores(), ...PermisoAcademico::valores(), ...PermisoPersona::valores(), ...PermisoMatricula::valores()]);

        User::factory()->create([
            'name' => 'Caja Demo',
            'email' => 'caja@ciln.test',
        ])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Registrar->value, PermisoPago::Validar->value, ...PermisoPersona::valores(), PermisoMatricula::Ver->value]);

        $this->call([
            AcademicoDemoSeeder::class,
            PersonasDemoSeeder::class,
            MatriculaDemoSeeder::class,
            PagosDemoSeeder::class,
        ]);

        $this->estudianteDelPortal();
    }

    // A verified student with an unused validated enrollment payment, so the portal can be tried end to end.
    private function estudianteDelPortal(): void
    {
        $user = User::factory()->create(['name' => 'Estudiante Demo', 'email' => 'estudiante@ciln.test']);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'numero_documento' => '70000001',
            'apellido_paterno' => 'Demo',
            'apellido_materno' => null,
            'nombres' => 'Estudiante',
            'correo' => 'estudiante@ciln.test',
        ]);

        $tarifa = Tarifa::query()
            ->whereRelation('concepto', 'codigo', 'MAT-01')
            ->where('tipo_vinculo', TipoVinculo::PublicoGeneral)
            ->sole();

        Pago::factory()->validado()->create([
            'persona_id' => $persona->id,
            'concepto_id' => $tarifa->concepto_id,
            'tarifa_id' => $tarifa->id,
            'monto' => $tarifa->monto,
            'validado_por' => User::query()->where('email', 'caja@ciln.test')->value('id'),
        ]);
    }
}
