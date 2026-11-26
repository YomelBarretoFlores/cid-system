<?php

declare(strict_types=1);

// Probes from the independent verification of the API, kept as regression tests.

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-10 10:00', 'America/Lima'));
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
});

function sondaEstudianteCompleto(string $marca, User $emisor): array
{
    $usuario = User::factory()->create(['email' => mb_strtolower($marca).'@ciln.test']);
    $persona = Persona::factory()->create(['user_id' => $usuario->id, 'nombres' => $marca]);
    $grupo = Grupo::factory()->create(['codigo' => "GRP-{$marca}"]);
    $matricula = Matricula::factory()->activa()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id]);
    $pago = Pago::factory()->validado()->for(Concepto::factory()->create(['nombre' => "Concepto {$marca}"]))->create(['persona_id' => $persona->id, 'numero_operacion' => "OP-{$marca}"]);
    Tramite::factory()->create(['persona_id' => $persona->id, 'tipo_nombre' => "Tramite {$marca}"]);
    ActasFinales::historial($persona, "Idioma {$marca}", [[1, '14.00', 'aprobado']]);
    $documento = app(EmitirConstanciaDeNotas::class)($persona->id, "Idioma {$marca}", NivelCurricular::Basico, $emisor);

    return ['usuario' => $usuario, 'persona' => $persona, 'matricula' => $matricula, 'pago' => $pago, 'documento' => $documento];
}

function sondaHuellaDeDatos(): array
{
    $tablas = collect(DB::select("select tablename from pg_tables where schemaname = 'public' order by tablename"))->pluck('tablename');

    return $tablas->mapWithKeys(function (string $tabla): array {
        $columnas = $tabla === 'personal_access_tokens'
            ? 'id, tokenable_type, tokenable_id, name, token, abilities, expires_at, created_at, updated_at'
            : 't.*';
        $fila = DB::selectOne("select md5(coalesce(string_agg(x::text, '|' order by x::text), '')) as huella from (select {$columnas} from \"{$tabla}\" t) x");

        return [$tabla => $fila->huella];
    })->all();
}

it('never shows another student any of their enrollments, payments, trámites, documents or grades', function (): void {
    $ana = sondaEstudianteCompleto('Ana', $this->emisor);
    $beto = sondaEstudianteCompleto('Beto', $this->emisor);
    $token = $ana['usuario']->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;

    foreach (['perfil', 'matriculas', 'pagos', 'tramites', 'documentos', 'notas'] as $ruta) {
        $this->withToken($token)->getJson("/api/v1/estudiante/{$ruta}")
            ->assertOk()
            ->assertDontSee('Beto')
            ->assertDontSee($beto['matricula']->ulid)
            ->assertDontSee($beto['pago']->ulid)
            ->assertDontSee($beto['documento']->codigo);
    }

    $matriculas = $this->withToken($token)->getJson('/api/v1/estudiante/matriculas')->json('data.*.id');

    expect($matriculas)->toContain($ana['matricula']->ulid)
        ->and(Matricula::query()->whereIn('ulid', $matriculas)->distinct()->pluck('persona_id')->all())->toBe([$ana['persona']->id])
        ->and($this->withToken($token)->getJson('/api/v1/estudiante/pagos')->json('data.*.id'))->toBe([$ana['pago']->ulid])
        ->and($this->withToken($token)->getJson('/api/v1/estudiante/documentos')->json('data.*.codigo'))->toBe([$ana['documento']->codigo])
        ->and($this->withToken($token)->getJson('/api/v1/estudiante/notas')->json('data.*.idioma'))->toBe(['Idioma Ana']);

    $this->withToken($token)->getJson("/api/v1/estudiante/documentos/{$beto['documento']->codigo}/pdf")->assertNotFound();
});

it('answers a teacher token on the student routes with the teacher\'s own empty data, as the web student guard does', function (): void {
    [$usuario] = PortalDocente::docente();
    $token = $usuario->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/estudiante/matriculas')->assertOk()->assertExactJson(['data' => []]);
    $this->withToken($token)->getJson('/api/v1/estudiante/pagos')->assertOk()->assertExactJson(['data' => []]);
});

it('keeps a token valid until the end of its 30 days and refuses it right after', function (): void {
    $usuario = User::factory()->create(['email' => 'rosa@ciln.test']);
    Persona::factory()->create(['user_id' => $usuario->id]);
    $token = $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'Celular de Rosa'])->assertCreated()->json('token');

    $this->travel(30 * 24 * 60 - 1)->minutes();
    $this->withToken($token)->getJson('/api/v1/sesiones/actual')->assertOk()->assertJsonPath('dispositivo', 'Celular de Rosa');

    app('auth')->forgetGuards();
    $this->travel(2)->minutes();
    $this->withToken($token)->getJson('/api/v1/sesiones/actual')->assertUnauthorized();
});

it('revokes only the device that asks, the other devices keep working', function (): void {
    $usuario = User::factory()->create(['email' => 'rosa@ciln.test']);
    Persona::factory()->create(['user_id' => $usuario->id]);
    $celular = $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'Celular'])->json('token');
    $tableta = $this->postJson('/api/v1/sesiones', ['email' => 'rosa@ciln.test', 'password' => 'password', 'dispositivo' => 'Tableta'])->json('token');

    $this->withToken($celular)->deleteJson('/api/v1/sesiones/actual')->assertNoContent();
    app('auth')->forgetGuards();

    $this->withToken($celular)->getJson('/api/v1/sesiones/actual')->assertUnauthorized();
    app('auth')->forgetGuards();
    $this->withToken($tableta)->getJson('/api/v1/sesiones/actual')->assertOk()->assertJsonPath('dispositivo', 'Tableta');
});

it('issues a token to an unverified account that works on the session but not on the student data', function (): void {
    $usuario = User::factory()->unverified()->create(['email' => 'nuevo@ciln.test']);
    Persona::factory()->create(['user_id' => $usuario->id]);

    $token = $this->postJson('/api/v1/sesiones', ['email' => 'nuevo@ciln.test', 'password' => 'password', 'dispositivo' => 'App'])->assertCreated()->json('token');

    $this->withToken($token)->getJson('/api/v1/sesiones/actual')->assertOk()->assertJsonPath('perfiles.estudiante', true);
    $this->withToken($token)->getJson('/api/v1/estudiante/perfil')->assertForbidden()->assertJsonPath('message', 'Verifica tu correo antes de usar estos datos.');
});

it('answers sign-in validation errors as JSON even without an Accept header, and refuses a non-string e-mail', function (): void {
    $this->post('/api/v1/sesiones', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password', 'dispositivo']);
    $this->postJson('/api/v1/sesiones', ['email' => ['a@b.c'], 'password' => 'x', 'dispositivo' => 'App'])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('keeps the web sign-in behavior: wrong credentials clear the password and fire Failed, the right ones log in and remember', function (): void {
    $usuario = User::factory()->create(['email' => 'rosa@ciln.test']);
    Persona::factory()->create(['user_id' => $usuario->id]);
    Event::fake([Failed::class]);

    Livewire::test(IniciarSesion::class)
        ->set('email', 'rosa@ciln.test')->set('password', 'mala-clave')
        ->call('ingresar')
        ->assertHasErrors(['email' => IniciarSesion::CREDENCIALES_INCORRECTAS])
        ->assertSet('password', '');
    Event::assertDispatched(Failed::class);

    Livewire::test(IniciarSesion::class)
        ->set('email', ' Rosa@CILN.test ')->set('password', 'password')->set('recordar', true)
        ->call('ingresar')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Auth::id())->toBe($usuario->id)
        ->and($usuario->fresh()?->getRememberToken())->not->toBeNull();
});

it('shares the per-address limit between the API and the web sign-in, and the web keeps the password when blocked', function (): void {
    $usuario = User::factory()->create(['email' => 'rosa@ciln.test']);
    Persona::factory()->create(['user_id' => $usuario->id]);

    foreach (range(1, 20) as $intento) {
        $this->postJson('/api/v1/sesiones', ['email' => "nadie{$intento}@ciln.test", 'password' => 'x', 'dispositivo' => 'App'])->assertUnprocessable();
    }

    Livewire::test(IniciarSesion::class)
        ->set('email', 'rosa@ciln.test')->set('password', 'password')
        ->call('ingresar')
        ->assertHasErrors('email')
        ->assertSet('password', 'password');

    expect(Auth::check())->toBeFalse();
});

it('limits public endpoints to 60 requests a minute per address', function (): void {
    foreach (range(1, 60) as $pedido) {
        $this->getJson('/api/v1/tarifas')->assertOk();
    }

    $this->getJson('/api/v1/tarifas')->assertTooManyRequests();
    $this->getJson('/api/v1/oferta')->assertTooManyRequests();
});

it('never publishes the teacher of a group in the public offer', function (): void {
    $periodo = Periodo::factory()->create(['estado' => EstadoPeriodo::MatriculaAbierta, 'matricula_inicio' => '2027-04-01', 'matricula_fin' => '2027-04-30', 'fecha_inicio' => '2027-05-03', 'fecha_fin' => '2027-06-30']);
    $ciclo = Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['numero' => 1, 'nivel' => NivelCurricular::Basico]);
    $grupo = Grupo::factory()->for($periodo)->for($ciclo)->create(['modalidad' => Modalidad::Virtual, 'enlace_virtual' => 'https://meet.test/x']);
    [, $docente] = PortalDocente::docente();
    Persona::query()->whereKey($docente->persona_id)->update(['apellido_paterno' => 'Zevallos']);
    PortalDocente::asignar($grupo, $docente);

    $this->getJson('/api/v1/oferta')->assertOk()->assertJsonCount(1, 'data.grupos')->assertDontSee('Zevallos')->assertDontSee('meet.test')->assertJsonMissingPath('data.grupos.0.docente')->assertJsonMissingPath('data.grupos.0.aula');
});

it('documents every endpoint with a bearer security scheme for staff only', function (): void {
    $personal = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->getJson('/docs/api.json')->assertForbidden();
    $documento = $this->actingAs($personal)->getJson('/docs/api.json')->assertOk()->json();

    expect(array_keys($documento['paths']))->toContain(
        '/sesiones', '/sesiones/actual', '/oferta', '/tarifas', '/documentos/{codigo}',
        '/estudiante/perfil', '/estudiante/matriculas', '/estudiante/pagos', '/estudiante/notas',
        '/estudiante/tramites', '/estudiante/documentos', '/estudiante/documentos/{codigo}/pdf', '/docente/grupos',
    )
        ->and(collect($documento['components']['securitySchemes'] ?? [])->contains(fn (array $esquema): bool => ($esquema['type'] ?? null) === 'http' && ($esquema['scheme'] ?? null) === 'bearer'))->toBeTrue();
});

it('changes no stored data on a full sweep of the read endpoints', function (): void {
    $ana = sondaEstudianteCompleto('Ana', $this->emisor);
    [$docenteUsuario] = PortalDocente::docente();
    $tokenAna = $ana['usuario']->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;
    $tokenDocente = $docenteUsuario->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;
    $antes = sondaHuellaDeDatos();

    foreach (['/api/v1/oferta', '/api/v1/tarifas', "/api/v1/documentos/{$ana['documento']->codigo}", '/api/v1/documentos/NO-EXISTE'] as $ruta) {
        $this->getJson($ruta);
    }
    foreach (['perfil', 'matriculas', 'pagos', 'tramites', 'documentos', 'notas'] as $ruta) {
        $this->withToken($tokenAna)->getJson("/api/v1/estudiante/{$ruta}")->assertOk();
    }
    $this->withToken($tokenAna)->getJson('/api/v1/sesiones/actual')->assertOk();
    app('auth')->forgetGuards();
    $this->withToken($tokenDocente)->getJson('/api/v1/docente/grupos')->assertOk();

    expect(array_diff_assoc(sondaHuellaDeDatos(), $antes))->toBe([]);
});
