<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Actions\GuardarDisponibilidad;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;

// A synthetic teacher with a portal account who teaches the demo face-to-face group, so the roster, the load, attendance and the pre-acta can be tried.
final class DocenciaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::factory()->create(['name' => 'Docente Demo', 'email' => 'docente@ciln.test']);
        $persona = Persona::factory()->create([
            'user_id' => $usuario->id,
            'numero_documento' => '70000002', 'apellido_paterno' => 'Docente', 'apellido_materno' => 'Demo',
            'nombres' => 'Ana', 'correo' => 'docente@ciln.test',
        ]);
        $docente = Docente::factory()->create([
            'persona_id' => $persona->id, 'correo_institucional' => 'docente.demo@ciln.test', 'ruc' => '10700000020',
            'condicion_laboral' => CondicionLaboral::Contratado,
        ]);
        $grupo = Grupo::query()->with('ciclo')->orderBy('id')->firstOrFail();
        $docente->idiomas()->create(['idioma_id' => $grupo->ciclo->idioma_id, 'nivel_maximo' => NivelCurricular::Avanzado]);

        foreach (range(1, 5) as $dia) {
            app(GuardarDisponibilidad::class)($docente->id, $dia, '07:00', '13:00');
        }

        app(AsignarDocente::class)($grupo->id, $docente->id, User::query()->where('email', 'admin@ciln.test')->firstOrFail());
    }
}
