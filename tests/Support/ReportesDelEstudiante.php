<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Horario;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

// One student of ING-1-M (Inglés, ciclo 1, Marzo 2027, Monday and Wednesday 18:00 to 20:00, ten classes in the period) with
// pre-acta grades 15 and 12 (partial average 10.80) and three classes held: present, absent at the second call, and late.
final class ReportesDelEstudiante
{
    /**
     * @return array{usuario: User, persona: Persona, matricula: Matricula, curso: array<string, mixed>}
     */
    public static function escenario(): array
    {
        $curso = ActasFinales::curso();
        $grupo = $curso['grupo'];
        $grupo->update(['aula_id' => Aula::factory()->create(['codigo' => 'A-101', 'nombre' => 'Laboratorio 1'])->id, 'enlace_virtual' => 'https://meet.example.test/ing-1-m']);
        Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
        Horario::factory()->for($grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);

        $usuario = User::factory()->create();
        $persona = Persona::factory()->create(['user_id' => $usuario->id, 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa', 'numero_documento' => '41234567']);
        $matricula = Matricula::factory()->activa()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id, 'matriculado_en' => '2027-02-20 15:00:00+00']);
        ActasFinales::preActa($matricula, [$curso['parcial']->id => '15.00', $curso['final']->id => '12.00']);

        foreach ([['2027-03-01', 'presente', 'presente'], ['2027-03-03', 'tardanza', 'falta'], ['2027-03-08', 'tardanza', 'presente']] as [$fecha, $inicio, $cierre]) {
            PortalDocente::dictarClase($grupo, $curso['docente'], $curso['usuario'], [$matricula->id => $inicio], [$matricula->id => $cierre], $fecha);
        }

        Carbon::setTestNow(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));

        return ['usuario' => $usuario, 'persona' => $persona, 'matricula' => $matricula, 'curso' => $curso];
    }

    /**
     * @param  array<string, mixed>  $curso
     */
    public static function cerrarActa(array $curso): void
    {
        app(CerrarActa::class)($curso['grupo']->id, $curso['docente']->id, $curso['usuario']);
    }
}
