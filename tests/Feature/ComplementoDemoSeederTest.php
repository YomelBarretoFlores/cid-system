<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Database\Seeders\ControlDeClasesDemoSeeder;
use App\Modules\Docencia\Database\Seeders\CuentasDeDemo;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\DelegadoDelGrupo;
use App\Modules\Docencia\Support\EstadoDeLaClase;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use Carbon\CarbonImmutable;
use Database\Seeders\ComplementoDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// A Wednesday of the demo month: English I (Mon, Wed, Fri 07:00–08:30) already gave the class of the day.
beforeEach(fn () => $this->travelTo(CarbonImmutable::parse('2026-10-21 12:00', 'America/Lima')));

function grupoDelMes(string $codigo): Grupo
{
    return Grupo::query()->where('codigo', $codigo)->whereRelation('periodo', 'estado', 'en_curso')->firstOrFail();
}

/**
 * @return array<string, int>
 */
function filasPorTabla(): array
{
    return collect(DB::select("select tablename from pg_tables where schemaname = 'public' order by tablename"))
        ->mapWithKeys(fn (object $tabla): array => [$tabla->tablename => DB::table($tabla->tablename)->count()])
        ->all();
}

// The demo as it was seeded before these features: every class without calls ("registro anterior"), no delegates or record.
// With $registroAnterior false the classes stay "registro posterior", as the seed of the previous release saved them.
function sembrarDemoAnterior(bool $registroAnterior = true): void
{
    app()->bind(ComplementoDemoSeeder::class, fn (): Seeder => new class extends Seeder
    {
        public function run(): void {}
    });
    test()->seed(DatabaseSeeder::class);
    app()->offsetUnset(ComplementoDemoSeeder::class);

    $grupo = grupoDelMes('ING-1-M');
    $docenteId = (int) CargaAcademica::query()->where('grupo_id', $grupo->id)->value('docente_id');
    $estudiantes = app(EstudiantesDelGrupo::class)($grupo->id);

    foreach (ControlDeClasesDemoSeeder::reservadas(app(HorariosDeGrupos::class)([$grupo->id])[$grupo->id]) as $clase) {
        $presentes = collect(ClasesDelHorario::estudiantesDe($estudiantes, $clase->fecha))
            ->mapWithKeys(fn (EstudianteDelGrupo $estudiante): array => [$estudiante->matriculaId => EstadoAsistencia::Presente->value])->all();
        app(RegistrarAsistencia::class)($grupo->id, $docenteId, $clase->fecha->toDateString(), '07:00', $presentes, CuentasDeDemo::usuarioDe($docenteId), 'Saludos y presentaciones');
    }

    if ($registroAnterior) {
        ClaseDictada::query()->update(['registro_posterior' => false]);
    }
}

/**
 * @return list<string>
 */
function clasesConLlamados(Grupo $grupo): array
{
    return ClaseDictada::query()->where('grupo_id', $grupo->id)->whereNotNull('llamado_inicio_en')->orderBy('fecha')->get()
        ->map(fn (ClaseDictada $clase): string => $clase->fecha->toDateString().' '.EstadoDeLaClase::de($clase)->value.($clase->llamado_cierre_en === null ? ' sin cierre' : ''))
        ->all();
}

/**
 * @return list<string>
 */
function historialDelEstudiante(): array
{
    return NotaFinal::query()->where('persona_id', CuentasDeDemo::personaDelEstudiante())->with('acta')->get()
        ->map(fn (NotaFinal $nota): string => "{$nota->acta->idioma} {$nota->acta->ciclo_numero} {$nota->acta->periodo}: {$nota->acta->estado->value} {$nota->estado_final->value}")
        ->sort()->values()->all();
}

function cicloHabilitadoEnPortugues(): ?string
{
    $habilitacion = Habilitacion::query()->where('persona_id', CuentasDeDemo::personaDelEstudiante())
        ->whereIn('ciclo_id', Ciclo::query()->whereRelation('idioma', 'codigo', 'POR')->select('id'))->first();

    return $habilitacion === null ? null : Ciclo::query()->whereKey($habilitacion->ciclo_id)->value('numero').' '.$habilitacion->origen->value;
}

it('completes a fresh demo seed with delegates, both calls, complete grades and a passed record', function (): void {
    $this->seed(DatabaseSeeder::class);

    $ingles = grupoDelMes('ING-1-M');
    $estudiante = CuentasDeDemo::personaDelEstudiante();
    $actuales = Grupo::query()->whereRelation('periodo', 'estado', 'en_curso')->whereIn('id', CargaAcademica::query()->select('grupo_id'))->get();
    $matriculas = Matricula::query()->where(['persona_id' => $estudiante, 'estado' => 'activa'])->whereIn('grupo_id', $actuales->modelKeys())->get();
    $observada = ClaseDictada::query()->where(['grupo_id' => $ingles->id, 'fecha' => '2026-10-19'])->sole();

    expect($actuales->filter(fn (Grupo $grupo): bool => app(DelegadoDelGrupo::class)($grupo->id) instanceof EstudianteDelGrupo))->toHaveCount($actuales->count())
        ->and(app(DelegadoDelGrupo::class)($ingles->id)?->personaId)->toBe($estudiante)
        ->and(clasesConLlamados($ingles))->toBe(['2026-10-14 confirmada', '2026-10-16 confirmada', '2026-10-19 observada', '2026-10-21 sin_confirmar'])
        ->and($observada->observacion)->toBe('La clase terminó veinte minutos antes de la hora programada.')
        ->and($matriculas)->toHaveCount(3)
        ->and($matriculas->every(fn (Matricula $matricula): bool => NotaParcial::query()->where('matricula_id', $matricula->id)->whereNotNull('nota')->count() === count(app(EsquemaDelGrupo::class)($matricula->grupo_id)->componentes ?? [])))->toBeTrue()
        ->and(historialDelEstudiante())->toBe(['Portugués 1 Julio 2026: cerrada aprobado', 'Portugués 2 Agosto 2026: cerrada aprobado'])
        ->and(cicloHabilitadoEnPortugues())->toBe('3 aprobacion');
});

it('changes nothing when the complement runs again', function (): void {
    $this->seed(DatabaseSeeder::class);
    $antes = filasPorTabla();

    $this->seed(ComplementoDemoSeeder::class);

    expect(filasPorTabla())->toBe($antes)
        ->and($antes['aud_registros'])->toBeGreaterThan(0);
});

it('completes a demo seeded before these features without deleting what it had', function (): void {
    sembrarDemoAnterior();
    $ingles = grupoDelMes('ING-1-M');
    $clases = ClaseDictada::query()->where('grupo_id', $ingles->id)->pluck('id')->sort()->values()->all();

    expect(clasesConLlamados($ingles))->toBe([])
        ->and(historialDelEstudiante())->toBe([]);

    $this->artisan('db:seed', ['--class' => ComplementoDemoSeeder::class, '--force' => true])
        ->expectsOutputToContain('Delegados de demo: 6 designados')
        ->expectsOutputToContain('Control de clases de demo: 4 clases tomadas')
        ->expectsOutputToContain('Notas de demo: 8 notas registradas')
        ->expectsOutputToContain('Historial de demo: 2 actas cerradas')
        ->assertSuccessful();

    expect(ClaseDictada::query()->where('grupo_id', $ingles->id)->pluck('id')->sort()->values()->all())->toBe($clases)
        ->and(clasesConLlamados($ingles))->toBe(['2026-10-14 confirmada', '2026-10-16 confirmada', '2026-10-19 observada', '2026-10-21 sin_confirmar'])
        ->and(historialDelEstudiante())->toBe(['Portugués 1 Julio 2026: cerrada aprobado', 'Portugués 2 Agosto 2026: cerrada aprobado'])
        ->and(cicloHabilitadoEnPortugues())->toBe('3 aprobacion');
});

it('takes no class and says why when the past classes were already saved as registro posterior', function (): void {
    sembrarDemoAnterior(registroAnterior: false);

    $this->artisan('db:seed', ['--class' => ComplementoDemoSeeder::class, '--force' => true])
        ->expectsOutputToContain('Control de clases de demo: 0 clases tomadas; no quedan clases pasadas sin asistencia registrada.')
        ->expectsOutputToContain('Historial de demo: 2 actas cerradas')
        ->assertSuccessful();

    expect(clasesConLlamados(grupoDelMes('ING-1-M')))->toBe([]);
});

it('leaves a database without the demo accounts untouched', function (): void {
    $antes = filasPorTabla();

    $this->artisan('db:seed', ['--class' => ComplementoDemoSeeder::class, '--force' => true])
        ->expectsOutputToContain('Complemento de demo omitido: la base no tiene las cuentas de demostración.')
        ->assertSuccessful();

    expect(filasPorTabla())->toBe($antes);
});

it('refuses to complete demo data in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    app(ComplementoDemoSeeder::class)->run();
})->throws(RuntimeException::class, 'Demo data must never be seeded in production.');
