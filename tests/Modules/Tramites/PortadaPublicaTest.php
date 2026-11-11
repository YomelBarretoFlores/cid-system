<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Data\VigenciaDeClasificacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\FuenteDeConcepto;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use Carbon\CarbonImmutable;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:30', 'America/Lima'));
    $matricula = Concepto::factory()->create(['codigo' => 'TUSNE-012', 'nombre' => 'Matrícula por ciclo', 'fuente_id' => FuenteDeConcepto::idDe('tusne')]);
    Tarifa::factory()->for($matricula)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '120.00', 'vigente_desde' => '2026-03-01']);
    Tarifa::factory()->for($matricula)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '180.00', 'vigente_desde' => '2026-03-01']);
});

function periodoDePortada(): Periodo
{
    return Periodo::factory()->create([
        'nombre' => '2026-II', 'estado' => EstadoPeriodo::MatriculaAbierta,
        'matricula_inicio' => '2026-10-06', 'matricula_fin' => '2026-10-31', 'fecha_inicio' => '2026-11-03', 'fecha_fin' => '2026-12-20',
    ]);
}

it('publishes the open period with its groups, fees, dates and entries, without private group data', function (): void {
    $ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $grupo = Grupo::factory()->for(periodoDePortada())->for(Ciclo::factory()->for($ingles)->state(['numero' => 1]))->create([
        'estado' => EstadoGrupo::Abierto, 'modalidad' => Modalidad::Virtual, 'cupo_maximo' => 20, 'matriculados' => 18,
        'aula_id' => Aula::factory()->create(['codigo' => 'AULA-SECRETA'])->id, 'enlace_virtual' => 'https://meet.example.test/privado',
    ]);
    Horario::factory()->for($grupo)->create(['dia_semana' => 2, 'hora_inicio' => '19:00', 'hora_fin' => '21:15']);
    [, $docente] = PortalDocente::docente();
    Persona::query()->whereKey($docente->persona_id)->update(['nombres' => 'DocenteReservado']);
    PortalDocente::asignar($grupo, $docente);

    $this->get('/')
        ->assertOk()
        ->assertSeeText('Matrícula 2026-II abierta hasta el 31 de octubre')
        ->assertSeeText('Oferta 2026-II')
        ->assertSeeTextInOrder(['Ciclo', 'Nivel', 'Días', 'Hora', 'Modalidad', 'Inicio', 'Vacantes', 'Ciclo I', 'Mar', '19:00–21:15', 'Virtual', '3 nov', '2 vacantes'])
        ->assertSeeText('Vacantes al 24 oct, 10:30')
        ->assertSeeText('TUSNE-012 · TUSNE')
        ->assertSeeText('S/ 120.00')
        ->assertSeeText('S/ 180.00')
        ->assertSeeText('Tarifas vigentes desde el 1 de marzo de 2026.')
        ->assertSeeTextInOrder(['En curso', '6 – 31 oct', 'Próximo', '3 nov', 'Próximo', '20 dic'])
        ->assertSee(route('ingresar'), false)
        ->assertSee(route('estudiante.registro'), false)
        ->assertSee(route('verificacion.documento'), false)
        ->assertDontSee('AULA-SECRETA')
        ->assertDontSee('meet.example.test')
        ->assertDontSee('DocenteReservado')
        ->assertDontSee('noindex')
        ->assertDontSee('laravel.com');
});

it('marks a full group and a group not yet open for enrollment', function (): void {
    $ciclo = Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Quechua']))->create(['numero' => 2]);
    $periodo = periodoDePortada();
    Grupo::factory()->for($periodo)->for($ciclo)->create(['estado' => EstadoGrupo::Abierto, 'cupo_maximo' => 20, 'matriculados' => 20]);
    Grupo::factory()->for($periodo)->for($ciclo)->create(['estado' => EstadoGrupo::Programado]);

    $this->get('/')->assertOk()->assertSeeText('Sin vacantes')->assertSeeText('Próximamente')->assertSeeText('Ciclo II');
});

it('still shows the fees and requirements when no period is open or planned', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('La oferta del próximo periodo se publicará pronto.')
        ->assertSeeText('S/ 120.00')
        ->assertSeeText('Cree su cuenta')
        ->assertDontSeeText('Fechas del periodo');
});

it('dates each step of the period by the day in Lima, including its last day', function (string $momento, array $esperado): void {
    $this->travelTo(CarbonImmutable::parse($momento, 'America/Lima'));
    periodoDePortada()->update(['matricula_inicio' => '2026-10-20', 'matricula_fin' => '2026-11-03', 'fecha_inicio' => '2026-11-03']);

    $this->get('/')->assertOk()->assertSeeTextInOrder($esperado);
})->with([
    'before the period, late evening' => ['2026-10-19 23:30', ['Próximo', '20 oct – 3 nov', 'Próximo', '3 nov']],
    'last enrollment day, which is also the first day of classes, late evening' => ['2026-11-03 23:30', ['En curso', '20 oct – 3 nov', 'Hoy', '3 nov', 'Próximo', '20 dic']],
]);

it('shows only the fees in force on the Lima day, late in the evening', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-31 23:30', 'America/Lima'));
    $examen = Concepto::factory()->create(['codigo' => 'TUSNE-015', 'nombre' => 'Examen de clasificación']);
    Tarifa::factory()->for($examen)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '55.00', 'vigente_desde' => '2026-01-01', 'vigente_hasta' => '2026-10-31']);
    Tarifa::factory()->for($examen)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '65.00', 'vigente_desde' => '2026-11-01']);

    $this->get('/')->assertOk()->assertSeeText('S/ 55.00')->assertDontSeeText('S/ 65.00');
});

it('escapes catalog names, also inside the data of the client filters', function (): void {
    $periodo = periodoDePortada();
    $periodo->update(['nombre' => '<img src=x onerror=alert(3)>']);
    $idioma = Idioma::factory()->create(['nombre' => '<script>alert(1)</script>"\'x']);
    Grupo::factory()->for($periodo)->for(Ciclo::factory()->for($idioma)->state(['numero' => 1]))->create(['estado' => EstadoGrupo::Abierto]);
    Concepto::factory()->create(['nombre' => '<script>alert(2)</script>'])->tarifas()->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '10.00', 'vigente_desde' => '2026-01-01']);

    expect($this->get('/')->assertOk()->getContent())
        ->not->toContain('<script>alert(1)')
        ->not->toContain('<script>alert(2)')
        ->not->toContain('<img src=x');
});

it('gives staff a header link to the panel sign-in and names the centre in the headline', function (): void {
    periodoDePortada();

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['<header', 'Personal CILN', '</header>'], false)
        ->assertSee('href="'.route('filament.admin.auth.login').'"', false)
        ->assertSeeInOrder(['<h1', 'Centro de Idiomas y Lenguas Nativas', '</h1>'], false);
});

it('shows each catalog language with the drawing of its flag', function (): void {
    Idioma::factory()->create(['nombre' => 'Inglés']);
    Idioma::factory()->create(['nombre' => 'Quechua']);

    $html = $this->get('/')->assertOk()->getContent();
    $flagDe = function (string $idioma) use ($html): string {
        preg_match_all('/<li class="border-r[^"]*">.*?<\/li>/s', $html, $tarjetas);
        $tarjeta = collect($tarjetas[0])->first(fn (string $html): bool => str_contains($html, '>'.$idioma.'</span>'));
        expect($tarjeta)->toBeString();

        return $tarjeta;
    };

    expect($flagDe('Inglés'))->toContain('#3C3B6E')->toContain('#B22234')->toContain('#FFFFFF')
        ->and($flagDe('Quechua'))->toContain('#E30613')->toContain('#6F2C91');
});

it('links to the dates section from the header and footer only when the home shows it', function (): void {
    $this->get('/')->assertOk()->assertDontSee(route('portada').'#fechas', false);
    $this->get(route('verificacion.documento'))->assertOk()->assertDontSee(route('portada').'#fechas', false);

    periodoDePortada();

    $this->get('/')->assertOk()->assertSee('href="'.route('portada').'#fechas"', false);
});

it('states the placement exam fee of each link and how long the request stays valid', function (): void {
    $examen = Concepto::factory()->create(['codigo' => 'TUSNE-015', 'nombre' => 'Examen de clasificación', 'tipo' => TipoConcepto::ExamenClasificacion]);
    Tarifa::factory()->for($examen)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '40.00', 'vigente_desde' => '2026-03-01']);
    Tarifa::factory()->for($examen)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '60.00', 'vigente_desde' => '2026-03-01']);

    $this->get('/')
        ->assertOk()
        ->assertSeeTextInOrder([
            '¿Ya conoce el idioma?',
            'Estudiante UNASAM', 'S/ 40.00', 'Público general', 'S/ 60.00',
            'vigente por '.VigenciaDeClasificacion::DIAS.' días',
            'Costos según su vínculo',
        ]);
});

it('keeps the placement exam steps but omits the fee when no placement exam fee is in force', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('¿Ya conoce el idioma?')
        ->assertSeeText('vigente por '.VigenciaDeClasificacion::DIAS.' días')
        ->assertDontSeeText('Costo del examen');
});

it('sums up the period in a band of figures under the headline', function (): void {
    $periodo = periodoDePortada();
    $ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $quechua = Idioma::factory()->create(['nombre' => 'Quechua']);
    $cicloI = Ciclo::factory()->for($ingles)->create(['numero' => 1]);
    Ciclo::factory()->for($ingles)->create(['numero' => 2]);
    Ciclo::factory()->for($ingles)->create(['numero' => 3]);
    Ciclo::factory()->for($quechua)->create(['numero' => 1]);
    Grupo::factory()->for($periodo)->for($cicloI)->create(['estado' => EstadoGrupo::Abierto, 'cupo_maximo' => 20, 'matriculados' => 5]);
    Grupo::factory()->for($periodo)->for($cicloI)->create(['estado' => EstadoGrupo::Abierto, 'cupo_maximo' => 20, 'matriculados' => 20]);

    preg_match('/<dl[^>]*aria-label="Cifras del periodo"[^>]*>.*?<\/dl>/s', $this->get('/')->assertOk()->getContent(), $banda);

    expect(preg_replace('/\s+/', ' ', trim(strip_tags($banda[0] ?? ''))))
        ->toBe('Idiomas 2 Grupos con vacantes 1 Ciclos por idioma 3 Inicio de clases 3 nov');
});

it('describes each language with its cycles, teaching hours, modalities with groups and the lowest enrollment fee', function (): void {
    $ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $cicloI = Ciclo::factory()->for($ingles)->create(['numero' => 1, 'horas_lectivas' => 60]);
    Ciclo::factory()->for($ingles)->create(['numero' => 2, 'horas_lectivas' => 64]);
    Grupo::factory()->for(periodoDePortada())->for($cicloI)->create(['estado' => EstadoGrupo::Abierto, 'modalidad' => Modalidad::Virtual]);
    Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Quechua']))->create(['numero' => 1, 'horas_lectivas' => 48]);

    $html = $this->get('/')->assertOk()->getContent();
    $ficha = fn (string $idioma): string => preg_replace('/\s+/', ' ', strip_tags((string) collect(preg_match_all('/<li class="border-r[^"]*">.*?<\/li>/s', $html, $fichas) ? $fichas[0] : [])
        ->first(fn (string $li): bool => str_contains($li, '>'.$idioma.'</span>'))));

    expect($ficha('Inglés'))->toContain('Ciclos 2 (I a II)')->toContain('Horas lectivas por ciclo 60 a 64')
        ->toContain('Modalidades Virtual')->toContain('Matrícula desde S/ 120.00')
        ->and($ficha('Quechua'))->toContain('Ciclos 1 (I)')->toContain('Horas lectivas por ciclo 48')
        ->toContain('Modalidades Sin grupos en este periodo')->toContain('Matrícula desde S/ 120.00');
});
