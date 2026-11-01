<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\GradoAcademico;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tablero\Actions\CalcularRentabilidadPorGrupo;
use App\Modules\Tablero\Data\FilaDeRentabilidad;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Shared\Auth\Models\User;
use Tests\Support\DatosDelTablero;

beforeEach(function (): void {
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->quechua = Idioma::factory()->create(['nombre' => 'Quechua']);
    $this->marzo = DatosDelTablero::periodo('Marzo 2027', '2027-03-01', '2027-03-31', numero: 3);
    $abril = DatosDelTablero::periodo('Abril 2027', '2027-04-01', '2027-04-30', numero: 4);

    $this->basico = DatosDelTablero::grupo($this->marzo, $this->ingles, atributos: ['codigo' => 'ING-1-M']);
    $this->intermedio = DatosDelTablero::grupo($this->marzo, $this->ingles, 5, NivelCurricular::Intermedio, ['codigo' => 'ING-5-M']);
    $this->quechuaBasico = DatosDelTablero::grupo($this->marzo, $this->quechua, atributos: ['codigo' => 'QUE-1-T']);
    DatosDelTablero::grupo($this->marzo, $this->ingles, atributos: ['codigo' => 'ING-1-X', 'estado' => EstadoGrupo::Cancelado]);
    $grupoDeAbril = DatosDelTablero::grupo($abril, $this->ingles, atributos: ['codigo' => 'ING-1-A']);

    $this->contratado = $contratado = Docente::factory()->create(['condicion_laboral_id' => CondicionLaboral::idDe('contratado')]);
    $tercero = Docente::factory()->create(['condicion_laboral_id' => CondicionLaboral::idDe('tercero')]);
    DatosDelTablero::costo('contratado', '40.00', '2027-01-01', '2027-03-15');
    DatosDelTablero::costo('contratado', '50.00', '2027-03-16');

    $pagada = DatosDelTablero::matricula($this->basico, DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-02-20'));
    DatosDelTablero::matricula($this->basico, DatosDelTablero::pago(TipoConcepto::Matricula, '80.50', '2027-02-21'), retirada: true);
    DatosDelTablero::matricula($this->basico);
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-05', personaId: $pagada->persona_id);
    DatosDelTablero::clase($this->basico, $contratado, '2027-03-10', '08:00', '10:00');
    DatosDelTablero::clase($this->basico, $contratado, '2027-03-20', '08:00', '09:30');

    DatosDelTablero::matricula($this->quechuaBasico, DatosDelTablero::pago(TipoConcepto::Matricula, '60.00', '2027-02-22'));
    DatosDelTablero::clase($this->quechuaBasico, $tercero, '2027-03-12', '10:00', '12:00');

    DatosDelTablero::matricula($grupoDeAbril, DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-03-25'));
    DatosDelTablero::clase($grupoDeAbril, $contratado, '2027-04-02', '08:00', '10:00');
});

it('sets the enrollment income of each group of the period against the cost of the hours taught at the rate in force each day', function (): void {
    $rentabilidad = app(CalcularRentabilidadPorGrupo::class)(new FiltrosDelTablero($this->marzo->id));

    expect($rentabilidad->filas)->toEqual([
        new FilaDeRentabilidad($this->basico->id, 'ING-1-M', 'Inglés', 'Básico', 3, 16050, 350, 15500, 0),
        new FilaDeRentabilidad($this->intermedio->id, 'ING-5-M', 'Inglés', 'Intermedio', 0, 0, 0, 0, 0),
        // The teacher's category has no hourly cost in force: the hours are shown apart, never priced at zero.
        new FilaDeRentabilidad($this->quechuaBasico->id, 'QUE-1-T', 'Quechua', 'Básico', 1, 6000, 0, 0, 200),
    ])
        ->and($rentabilidad->filas[0]->margen())->toBe(550)
        ->and($rentabilidad->ingresosCentimos)->toBe(22050)
        ->and($rentabilidad->costoCentimos)->toBe(15500)
        ->and($rentabilidad->margen())->toBe(6550)
        ->and($rentabilidad->horasSinCostoCentesimas)->toBe(200);
});

it('keeps only the groups of the chosen language and level', function (): void {
    $calcular = app(CalcularRentabilidadPorGrupo::class);

    expect(array_map(fn (FilaDeRentabilidad $fila): string => $fila->codigo, $calcular(new FiltrosDelTablero($this->marzo->id, $this->quechua->id))->filas))->toBe(['QUE-1-T'])
        ->and(array_map(fn (FilaDeRentabilidad $fila): string => $fila->codigo, $calcular(new FiltrosDelTablero($this->marzo->id, null, NivelCurricular::Intermedio))->filas))->toBe(['ING-5-M']);
});

it('prices the hours of a teacher with a degree by the degree rate, and before it by the condition rate', function (): void {
    $this->contratado->update(['grado_academico_id' => GradoAcademico::idDe('magister')]);
    CostoPorHora::query()->create(['grado_academico_id' => GradoAcademico::idDe('magister'), 'costo_hora' => '70.00', 'vigente_desde' => '2027-03-15', 'registrado_por' => User::factory()->create()->id]);

    $basico = app(CalcularRentabilidadPorGrupo::class)(new FiltrosDelTablero($this->marzo->id))->filas[0];

    // 10 March: 2 h at the condition rate of 40.00; 20 March: 1.5 h at the degree rate of 70.00 instead of the condition rate of 50.00.
    expect([$basico->codigo, $basico->costoCentimos])->toBe(['ING-1-M', 18500]);
});
