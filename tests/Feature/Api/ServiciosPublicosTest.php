<?php

declare(strict_types=1);

use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\FuenteDeConcepto;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:30', 'America/Lima'));
});

it('publishes the offer of the period without a token and without private group data', function (): void {
    $periodo = Periodo::factory()->create([
        'nombre' => '2026-II', 'estado' => EstadoPeriodo::MatriculaAbierta,
        'matricula_inicio' => '2026-10-06', 'matricula_fin' => '2026-10-31', 'fecha_inicio' => '2026-11-03', 'fecha_fin' => '2026-12-20',
    ]);
    $ciclo = Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['numero' => 1, 'nivel' => NivelCurricular::Basico]);
    $grupo = Grupo::factory()->for($periodo)->for($ciclo)->create(['codigo' => 'ING-1-M', 'modalidad' => Modalidad::Virtual, 'enlace_virtual' => 'https://meet.test/secreto', 'aula_id' => Aula::factory()->create(['codigo' => 'A-101'])->id, 'cupo_maximo' => 25]);
    DB::table('aca_horarios')->insert(['grupo_id' => $grupo->id, 'dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '09:00', 'created_at' => now(), 'updated_at' => now()]);

    $this->getJson('/api/v1/oferta')
        ->assertOk()
        ->assertExactJson(['data' => [
            'periodo' => '2026-II',
            'matricula_abierta' => true,
            'matricula_inicio' => '2026-10-06',
            'matricula_fin' => '2026-10-31',
            'clases_inicio' => '2026-11-03',
            'clases_fin' => '2026-12-20',
            'grupos' => [[
                'idioma' => 'Inglés', 'ciclo' => 1, 'nivel' => 'Básico', 'modalidad' => 'Virtual', 'acelerado' => false,
                'horario' => [SesionDeClase::compactar([new SesionDeClase(1, '07:00', '09:00')])[0]], 'vacantes' => 25, 'abierto' => true,
            ]],
        ]])
        ->assertDontSee('meet.test')
        ->assertDontSee('A-101');
});

it('answers an empty offer when no period is open or about to open', function (): void {
    $this->getJson('/api/v1/oferta')->assertOk()->assertExactJson(['data' => null]);
});

it('lists the fees in force today in Lima with their amount as a decimal string', function (): void {
    $concepto = Concepto::factory()->create(['codigo' => 'TUSNE-012', 'nombre' => 'Matrícula por ciclo', 'fuente_id' => FuenteDeConcepto::idDe('tusne')]);
    Tarifa::factory()->for($concepto)->create(['tipo_vinculo' => 'publico_general', 'monto' => '180.00', 'vigente_desde' => '2026-03-01']);
    Tarifa::factory()->for($concepto)->create(['tipo_vinculo' => 'estudiante_unasam', 'monto' => '120.00', 'vigente_desde' => '2026-10-25']);

    $this->getJson('/api/v1/tarifas')
        ->assertOk()
        ->assertExactJson(['data' => [[
            'codigo' => 'TUSNE-012', 'concepto' => 'Matrícula por ciclo', 'fuente' => 'tusne', 'fuente_etiqueta' => 'TUSNE',
            'vinculo' => 'publico_general', 'vinculo_etiqueta' => 'Público general', 'monto' => '180.00', 'vigente_desde' => '2026-03-01', 'nivel' => null, 'programa' => null,
        ]]]);
});

it('verifies a document by its code with the same minimal data as the public page', function (): void {
    $emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    $ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', 'numero_documento' => '70000001']);
    ActasFinales::historial($ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '15.50', 'aprobado'], [3, '16.25', 'aprobado'], [4, '13.00', 'aprobado']]);
    $certificado = app(EmitirCertificadoDeNivel::class)($ana->id, 'Inglés', NivelCurricular::Basico, $emisor);

    $this->getJson('/api/v1/documentos/'.mb_strtolower($certificado->codigo))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertJsonPath('data.codigo', $certificado->codigo)
        ->assertJsonPath('data.vigente', true)
        ->assertJsonPath('data.estudiante', 'Alva Prueba, Ana')
        ->assertJsonPath('data.documento', 'DNI ••••0001')
        ->assertJsonPath('data.idioma', 'Inglés')
        ->assertJsonPath('data.nivel', 'Básico')
        ->assertJsonPath('data.horas_lectivas', 240)
        ->assertJsonPath('data.emitido_el', '2026-10-24')
        ->assertJsonPath('data.anulado_el', null)
        ->assertDontSee('70000001');

    app(AnularDocumento::class)($certificado->id, 'Se emitió con un nombre mal escrito.', $emisor);

    $this->getJson("/api/v1/documentos/{$certificado->codigo}")
        ->assertOk()
        ->assertJsonPath('data.vigente', false)
        ->assertJsonPath('data.anulado_el', '2026-10-24')
        ->assertDontSee('nombre mal escrito');
});

it('answers 404 for an unknown document code', function (): void {
    $this->getJson('/api/v1/documentos/NO-EXISTE')
        ->assertNotFound()
        ->assertJsonPath('message', 'No existe un documento con ese código.');
});

it('limits document verification to 30 requests a minute per address', function (): void {
    foreach (range(1, 30) as $pedido) {
        $this->getJson('/api/v1/documentos/NO-EXISTE')->assertNotFound();
    }

    $this->getJson('/api/v1/documentos/NO-EXISTE')->assertTooManyRequests();
});
