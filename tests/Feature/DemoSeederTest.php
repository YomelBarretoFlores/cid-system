<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Database\Seeders\CertificacionDemoSeeder;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\DelegadoDelGrupo;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tablero\Enums\PermisoTablero;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\Notificacion;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Audit\Enums\PermisoAuditoria;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

it('seeds consistent demo data for local development', function (): void {
    $this->seed(DatabaseSeeder::class);

    $grupo = Grupo::query()->orderBy('id')->firstOrFail();
    $activasPorGrupo = Matricula::query()->where('estado', 'activa')->selectRaw('grupo_id, count(*) as total')->groupBy('grupo_id')->pluck('total', 'grupo_id');

    expect(Ciclo::query()->count())->toBe(60)
        ->and(Grupo::query()->get()->every(fn (Grupo $cadaGrupo): bool => $cadaGrupo->matriculados === (int) ($activasPorGrupo[$cadaGrupo->id] ?? 0)))->toBeTrue()
        ->and(Matricula::query()->whereNull('pago_id')->exists())->toBeFalse()
        ->and(Pago::query()->whereIn('id', Matricula::query()->select('pago_id'))->where('estado', '!=', EstadoPago::Validado)->exists())->toBeFalse()
        ->and(User::query()->where('email', 'admin@ciln.test')->firstOrFail()->can(User::ADMIN_PANEL_PERMISSION))->toBeTrue()
        ->and(app(DocentesDeGrupos::class)([$grupo->id]))->toBe([$grupo->id => 'Docente Demo, Ana'])
        ->and(DB::table('cer_aprobaciones')->where('forma', 'clasificacion')->orderBy('ciclo_numero')->pluck('ciclo_numero')->all())->toBe([2, 3, 4]);
});

it('dates the demo around today in Lima so the closed flows show data', function (): void {
    $this->seed(DatabaseSeeder::class);
    $hoy = CarbonImmutable::today('America/Lima')->toDateString();

    expect(Periodo::query()->where('estado', 'en_curso')->whereDate('fecha_inicio', '<=', $hoy)->whereDate('fecha_fin', '>=', $hoy)->count())->toBe(1)
        ->and(Periodo::query()->where('estado', 'matricula_abierta')->whereDate('matricula_inicio', '<=', $hoy)->whereDate('matricula_fin', '>=', $hoy)->count())->toBe(1)
        ->and(Pago::query()->whereDate('fecha_pago', '>', $hoy)->exists())->toBeFalse()
        ->and(Pago::query()->where('estado', EstadoPago::Pendiente)->exists())->toBeTrue()
        ->and(ClaseDictada::query()->whereDate('fecha', '>', $hoy)->exists())->toBeFalse()
        ->and(SolicitudClasificacion::query()->whereNull('pago_id')->exists())->toBeFalse();
});

it('seeds a demo administrator with every payment permission and a cashier who cannot void', function (): void {
    $this->seed(DatabaseSeeder::class);

    $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
    $caja = User::query()->where('email', 'caja@ciln.test')->firstOrFail();

    expect($administrador->getPermissionNames()->sort()->values()->all())->toBe(collect(['admin-panel.access', ...PermisoPago::valores(), ...PermisoAcademico::valores(), ...PermisoPersona::valores(), ...PermisoMatricula::valores(), ...PermisoClasificacion::valores(), ...PermisoDocencia::valores(), ...PermisoCertificacion::valores(), ...PermisoTramite::valores(), ...PermisoTablero::valores(), ...PermisoUsuario::valores(), ...PermisoAuditoria::valores()])->sort()->values()->all())
        ->and($caja->getPermissionNames()->sort()->values()->all())
        ->toBe(collect(['admin-panel.access', PermisoPago::Registrar->value, PermisoPago::Validar->value, PermisoPago::Ver->value, ...PermisoPersona::valores(), PermisoMatricula::Ver->value])->sort()->values()->all());
});

it('seeds a verified portal student with an active enrollment and an unused validated enrollment payment', function (): void {
    $this->seed(DatabaseSeeder::class);

    $estudiante = User::query()->where('email', 'estudiante@ciln.test')->sole();
    $persona = Persona::query()->where('user_id', $estudiante->id)->sole();
    $usados = Matricula::query()->where('persona_id', $persona->id)->pluck('pago_id')->merge(Tramite::query()->whereNotNull('pago_id')->pluck('pago_id'));

    expect($estudiante->hasVerifiedEmail())->toBeTrue()
        ->and($estudiante->can(User::ADMIN_PANEL_PERMISSION))->toBeFalse()
        // Three current groups plus Portuguese I and II of the record, whose enrollments stay active once the period closes.
        ->and(Matricula::query()->where(['persona_id' => $persona->id, 'estado' => 'activa'])->count())->toBe(5)
        ->and(Pago::query()->where(['persona_id' => $persona->id, 'estado' => EstadoPago::Validado])->whereNotIn('id', $usados)->count())->toBe(1);
});

it('seeds a verified portal teacher who has the first demo group', function (): void {
    $this->seed(DatabaseSeeder::class);

    $docente = User::query()->where('email', 'docente@ciln.test')->sole();
    $grupo = Grupo::query()->orderBy('id')->firstOrFail();

    expect($docente->hasVerifiedEmail())->toBeTrue()
        ->and($docente->can(User::ADMIN_PANEL_PERMISSION))->toBeFalse();

    $this->actingAs($docente)->get(route('filament.docente.pages.mis-grupos'))->assertOk()->assertSee("Grupo {$grupo->codigo}");
});

it('seeds a fictitious hourly cost for every academic degree and gives every demo teacher a degree', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(CostoPorHora::query()->with('gradoAcademico')->whereNull('vigente_hasta')->get()->mapWithKeys(fn (CostoPorHora $costo): array => [$costo->gradoAcademico?->codigo => $costo->costo_hora])->sortKeys()->all())
        ->toBe(['bachiller' => '45.00', 'certificado' => '40.00', 'doctor' => '60.00', 'magister' => '55.00', 'titulado' => '50.00'])
        ->and(Docente::query()->whereNull('grado_academico_id')->count())->toBe(0);
});

it('closes the final grade sheets of the previous month, one of them reopened for a rectification', function (): void {
    $this->seed(DatabaseSeeder::class);

    $gruposCerrados = Grupo::query()->whereRelation('periodo', 'estado', 'cerrado')->pluck('id');
    $actas = Acta::query()->withCount('notas')->get();

    expect($actas->pluck('grupo_id')->sort()->values()->all())->toBe($gruposCerrados->sort()->values()->all())
        ->and($actas->every(fn (Acta $acta): bool => $acta->notas_count === Matricula::query()->where(['grupo_id' => $acta->grupo_id, 'estado' => 'activa'])->count()))->toBeTrue()
        ->and($actas->where('estado', EstadoActa::Abierta)->pluck('grupo_codigo')->all())->toBe(['POR-1-T'])
        ->and(NotaFinal::query()->distinct()->pluck('estado_final')->map->value->sort()->values()->all())->toBe(['aprobado', 'desaprobado', 'nsp'])
        ->and(Periodo::query()->where('estado', 'en_curso')->sole()->actas_inicio)->not->toBeNull();
});

it('issues level certificates to the English cohort, its grade records with one voided (and one more through a trámite), and the portal student an enrollment record', function (): void {
    $this->seed(DatabaseSeeder::class);

    $documentos = Documento::query()->get();

    expect($documentos->where('tipo', TipoDocumento::CertificadoNivel)->count())->toBeGreaterThan(0)
        ->and($documentos->where('tipo', TipoDocumento::CertificadoNivel)->every(fn (Documento $documento): bool => $documento->horas_lectivas === 240 && $documento->nota_final >= '11.00'))->toBeTrue()
        // A cohort student without a passed Básico cycle (the NSP one, depending on the random demo history) gets no grade record.
        ->and($documentos->where('tipo', TipoDocumento::ConstanciaNotas)->count())->toBeIn([5, 6])
        ->and($documentos->filter(fn (Documento $documento): bool => ! $documento->estaVigente())->count())->toBe(1)
        ->and($documentos->where('tipo', TipoDocumento::ConstanciaMatricula)->pluck('estudiante_nombre')->all())->toBe(['Demo, Estudiante']);
});

it('seeds trámites in every state through the real flow, each change e-mailed and logged', function (): void {
    $this->seed(DatabaseSeeder::class);

    $tramites = Tramite::query()->orderBy('id')->get();
    $estudiante = Persona::query()->whereRelation('user', 'email', 'estudiante@ciln.test')->value('id');

    expect($tramites->map(fn (Tramite $tramite): string => "{$tramite->tipo_nombre}: {$tramite->estado->value} {$tramite->avance()}")->all())->toBe([
        'Constancia de matrícula: en_proceso 66',
        'Traslado de grupo: pendiente_pago 0',
        'Constancia de notas: finalizado 100',
        'Constancia de notas: observado 33',
        'Constancia de notas: observado 0',
    ])
        ->and($tramites->take(2)->every(fn (Tramite $tramite): bool => $tramite->persona_id === $estudiante))->toBeTrue()
        ->and($tramites[2]->documento_id)->not->toBeNull()
        // The three requests that took a payment at once send one e-mail for both steps.
        ->and(Notificacion::query()->count())->toBe(Movimiento::query()->count() - 3)
        ->and(Notificacion::query()->where('estado', '!=', 'enviado')->exists())->toBeFalse();
});

it('refuses to seed demo data in production even when forced', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    app(DatabaseSeeder::class)->run();
})->throws(RuntimeException::class, 'Demo data must never be seeded in production.');

it('leaves no demo data behind when a later seeder fails, so the next container start seeds again', function (): void {
    app()->bind(CertificacionDemoSeeder::class, fn (): Seeder => new class extends Seeder
    {
        public function run(): never
        {
            throw new RuntimeException('Seeder failed halfway.');
        }
    });

    expect(fn () => $this->seed(DatabaseSeeder::class))->toThrow(RuntimeException::class, 'Seeder failed halfway.')
        ->and(User::query()->count())->toBe(0)
        ->and(Grupo::query()->count())->toBe(0);
});

it('seeds a delegate for every current demo group, the demo student in English I', function (): void {
    $this->seed(DatabaseSeeder::class);

    $grupos = Grupo::query()->whereRelation('periodo', 'estado', 'en_curso')->whereIn('id', CargaAcademica::query()->select('grupo_id'))->get();
    $delegados = $grupos->mapWithKeys(fn (Grupo $grupo): array => [$grupo->codigo => app(DelegadoDelGrupo::class)($grupo->id)?->personaId]);
    $estudiante = Persona::query()->whereRelation('user', 'email', 'estudiante@ciln.test')->value('id');

    expect($grupos)->not->toBeEmpty()
        ->and($delegados->filter()->count())->toBe($grupos->count())
        ->and($delegados['ING-1-M'])->toBe($estudiante);
});
