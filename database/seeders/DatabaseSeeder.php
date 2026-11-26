<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academico\Database\Seeders\AcademicoDemoSeeder;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Database\Seeders\CertificacionDemoSeeder;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Docencia\Database\Seeders\DocenciaDemoSeeder;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Matricula\Database\Seeders\MatriculaDemoSeeder;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Database\Seeders\PagosDemoSeeder;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Actions\ObtenerVinculoPorDefecto;
use App\Modules\Personas\Database\Seeders\PersonasDemoSeeder;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tablero\Enums\PermisoTablero;
use App\Modules\Tramites\Database\Seeders\TramitesDemoSeeder;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Shared\Audit\Enums\PermisoAuditoria;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        throw_if(app()->isProduction(), RuntimeException::class, 'Demo data must never be seeded in production.');

        // All or nothing: a seed that fails halfway leaves no users, so the next container start seeds again (docker/app/entrypoint.sh).
        DB::transaction($this->sembrar(...));
    }

    private function sembrar(): void
    {
        User::factory()->create([
            'name' => 'Administrador Demo',
            'email' => 'admin@ciln.test',
        ])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoPago::valores(), ...PermisoAcademico::valores(), ...PermisoPersona::valores(), ...PermisoMatricula::valores(), ...PermisoClasificacion::valores(), ...PermisoDocencia::valores(), ...PermisoCertificacion::valores(), ...PermisoTramite::valores(), ...PermisoTablero::valores(), ...PermisoUsuario::valores(), ...PermisoAuditoria::valores()]);

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
        $this->estudianteSinHabilitar();
        $this->call(DocenciaDemoSeeder::class);
        $this->call(CertificacionDemoSeeder::class);
        $this->call(TramitesDemoSeeder::class);
        $this->call(ComplementoDemoSeeder::class);
    }

    // An enabled student enrolled in English I, French I and Italian I, withdrawn from Quechua I, and with an unused validated enrollment payment to try next period's enrollment.
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
            ->where('tipo_vinculo', 'publico_general')
            ->sole();

        $cajero = User::query()->where('email', 'caja@ciln.test')->value('id');
        $pagar = fn (string $fecha): Pago => Pago::factory()->validado()->create([
            'persona_id' => $persona->id,
            'concepto_id' => $tarifa->concepto_id,
            'tarifa_id' => $tarifa->id,
            'monto' => $tarifa->monto,
            'fecha_pago' => $fecha,
            'validado_por' => $cajero,
            'boleta_original_verificada_por' => $cajero,
        ]);

        foreach ([['ING-1-M', EstadoMatricula::Activa], ['FRA-1-N', EstadoMatricula::Activa], ['ITA-1-M', EstadoMatricula::Activa], ['QUE-1-S', EstadoMatricula::Retirada]] as [$codigo, $estado]) {
            $grupo = Grupo::query()->with('periodo')->where('codigo', $codigo)->whereRelation('periodo', 'estado', 'en_curso')->firstOrFail();
            $matriculadoEn = CarbonImmutable::parse($grupo->periodo->matricula_inicio->toDateString(), 'America/Lima')->setTime(9, 30);
            Matricula::factory()->create([
                'persona_id' => $persona->id, 'grupo_id' => $grupo->id, 'pago_id' => $pagar($matriculadoEn->toDateString())->id,
                'matriculado_en' => $matriculadoEn, 'estado' => $estado,
                'retirado_en' => $estado === EstadoMatricula::Retirada ? $matriculadoEn->addDays(12) : null,
            ]);

            if ($estado === EstadoMatricula::Activa) {
                $grupo->increment('matriculados');
            }
        }

        $pagar(CarbonImmutable::today('America/Lima')->subDays(2)->toDateString());
    }

    // A self-registered account still waiting for its first validated payment, so staff can validate it and watch the account become enabled.
    private function estudianteSinHabilitar(): void
    {
        $user = User::factory()->create(['name' => 'Postulante Demo', 'email' => 'postulante@ciln.test']);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'numero_documento' => '70000009',
            'apellido_paterno' => 'Demo',
            'apellido_materno' => null,
            'nombres' => 'Postulante',
            'correo' => 'postulante@ciln.test',
            'tipo_vinculo' => app(ObtenerVinculoPorDefecto::class)(),
            'tratamiento_aceptado_en' => now(),
            'habilitada_en' => null,
        ]);

        $tarifa = Tarifa::query()
            ->whereRelation('concepto', 'codigo', 'MAT-01')
            ->where('tipo_vinculo', $persona->tipo_vinculo)
            ->sole();

        Pago::factory()->create([
            'persona_id' => $persona->id,
            'concepto_id' => $tarifa->concepto_id,
            'tarifa_id' => $tarifa->id,
            'monto' => $tarifa->monto,
            'fecha_pago' => CarbonImmutable::today('America/Lima')->toDateString(),
            'estado' => EstadoPago::Pendiente,
            'registrado_por' => $user->id,
        ]);
    }
}
