<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $this->grupo = Grupo::factory()->create(['periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    PortalDocente::asignar($this->grupo, $this->docente);
    [$this->ana, $this->luis] = collect(['Quispe', 'Zapata'])->map(fn (string $apellido): Matricula => Matricula::factory()->create([
        'grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => $apellido])->id, 'matriculado_en' => '2027-02-20 15:00:00+00',
    ]))->all();
    $this->travelTo(CarbonImmutable::parse('2027-03-10 18:05', 'America/Lima'));
});

function migracionDeAsistenciaSinTardanza(): Migration
{
    return require base_path('app/Modules/Docencia/Database/Migrations/2026_11_01_090000_keep_present_or_absent_and_add_class_topic.php');
}

// That migration also linked classes to syllabus weeks, so it rolls back only after the syllabus tables are restored.
function migracionSinSilabo(): Migration
{
    return require base_path('app/Modules/Docencia/Database/Migrations/2026_11_17_090000_drop_doc_silabos_tables.php');
}

it('offers only present and absent, keeping absent stored as falta', function (): void {
    expect(array_map(fn (EstadoAsistencia $estado): array => [$estado->value, $estado->etiqueta()], EstadoAsistencia::cases()))
        ->toBe([['presente', 'Presente'], ['falta', 'Ausente']]);
});

it('refuses a late arrival in the roll call and saves nothing', function (): void {
    expect(fn (): ClaseDictada => app(RegistrarAsistencia::class)($this->grupo->id, $this->docente->id, '2027-03-10', '18:00', [$this->ana->id => 'tardanza', $this->luis->id => 'presente'], $this->usuario))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El estado de asistencia no es válido.')
        ->and(ClaseDictada::query()->count())->toBe(0)
        ->and(Asistencia::query()->count())->toBe(0);
});

it('makes the database refuse a late arrival written directly', function (): void {
    $clase = app(RegistrarAsistencia::class)($this->grupo->id, $this->docente->id, '2027-03-10', '18:00', [$this->ana->id => 'presente', $this->luis->id => 'presente'], $this->usuario);

    expect(fn () => DB::table('doc_asistencias')->where('clase_id', $clase->id)->update(['estado' => 'tardanza']))->toThrow(QueryException::class);
});

it('turns the late arrivals already saved into present and keeps the old value in the audit trail', function (): void {
    $clase = app(RegistrarAsistencia::class)($this->grupo->id, $this->docente->id, '2027-03-10', '18:00', [$this->ana->id => 'presente', $this->luis->id => 'falta'], $this->usuario);
    migracionSinSilabo()->down();
    migracionDeAsistenciaSinTardanza()->down();
    DB::table('doc_asistencias')->where('matricula_id', $this->ana->id)->update(['estado' => 'tardanza']);

    migracionDeAsistenciaSinTardanza()->up();
    migracionSinSilabo()->up();

    expect(DB::table('doc_asistencias')->where('clase_id', $clase->id)->orderBy('matricula_id')->pluck('estado', 'matricula_id')->all())
        ->toBe([$this->ana->id => 'presente', $this->luis->id => 'falta'])
        ->and(DB::table('aud_registros')->where('tabla', 'doc_asistencias')->where('operacion', 'UPDATE')->get()
            ->contains(fn (object $fila): bool => json_decode((string) $fila->datos_anteriores, true)['estado'] === 'tardanza' && json_decode((string) $fila->datos_nuevos, true)['estado'] === 'presente'))->toBeTrue();
});
