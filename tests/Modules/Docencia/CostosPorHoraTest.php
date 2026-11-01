<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\EliminarCostoPorHora;
use App\Modules\Docencia\Actions\RegistrarCostoPorHora;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\GradoAcademico;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-05-10 12:00', 'America/Lima'));
    $this->usuario = User::factory()->create();
    $this->vigente = CostoPorHora::query()->create(['grado_academico_id' => GradoAcademico::idDe('titulado'), 'costo_hora' => '40.00', 'vigente_desde' => '2027-01-01', 'registrado_por' => $this->usuario->id]);
});

function registrarCosto(string $desde, string $costo = '45.00', string $categoria = 'titulado', ?string $hasta = null): CostoPorHora
{
    return app(RegistrarCostoPorHora::class)(GradoAcademico::idDe($categoria), $costo, CarbonImmutable::parse($desde), $hasta === null ? null : CarbonImmutable::parse($hasta), test()->usuario);
}

it('registers a cost and closes the open cost of the category the day before, with who registered it', function (): void {
    $nuevo = registrarCosto('2027-06-01');

    expect($nuevo->refresh())
        ->costo_hora->toBe('45.00')
        ->registrado_por->toBe($this->usuario->id)
        ->vigente_hasta->toBeNull()
        ->and($nuevo->vigente_desde->toDateString())->toBe('2027-06-01')
        ->and($this->vigente->refresh()->vigente_hasta->toDateString())->toBe('2027-05-31')
        ->and(CostoPorHora::query()->vigenteEn(CarbonImmutable::parse('2027-05-31'))->where('grado_academico_id', GradoAcademico::idDe('titulado'))->sole()->is($this->vigente))->toBeTrue()
        ->and(CostoPorHora::query()->vigenteEn(CarbonImmutable::parse('2027-06-01'))->where('grado_academico_id', GradoAcademico::idDe('titulado'))->sole()->is($nuevo))->toBeTrue()
        ->and(DB::table('aud_registros')->where('tabla', 'doc_costos_hora')->orderBy('id')->pluck('operacion')->all())->toBe(['INSERT', 'UPDATE', 'INSERT']);
});

it('leaves the costs of other categories untouched', function (): void {
    $tercero = CostoPorHora::query()->create(['grado_academico_id' => GradoAcademico::idDe('bachiller'), 'costo_hora' => '35.00', 'vigente_desde' => '2027-01-01', 'registrado_por' => $this->usuario->id]);

    registrarCosto('2027-06-01');

    expect($tercero->refresh()->vigente_hasta)->toBeNull();
});

it('refuses a cost that breaks a rule and changes nothing', function (Closure $registrar, string $mensaje): void {
    CostoPorHora::query()->create(['grado_academico_id' => GradoAcademico::idDe('bachiller'), 'costo_hora' => '35.00', 'vigente_desde' => '2027-03-01', 'vigente_hasta' => '2027-03-31', 'registrado_por' => $this->usuario->id]);

    expect($registrar)->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(CostoPorHora::query()->count())->toBe(2)
        ->and($this->vigente->refresh()->vigente_hasta)->toBeNull();
})->with([
    'overlapping a closed cost' => [fn (): CostoPorHora => registrarCosto('2027-03-15', categoria: 'bachiller', hasta: '2027-04-30'), 'La vigencia se superpone con otro costo por hora de este grado académico.'],
    'open cost covering a later closed one' => [fn (): CostoPorHora => registrarCosto('2027-02-01', categoria: 'bachiller'), 'La vigencia se superpone con otro costo por hora de este grado académico.'],
    'starting before the open cost' => [fn (): CostoPorHora => registrarCosto('2026-12-01', hasta: '2027-01-15'), 'La vigencia se superpone con otro costo por hora de este grado académico.'],
    'zero' => [fn (): CostoPorHora => registrarCosto('2027-06-01', '0'), 'El costo por hora debe ser un monto mayor que cero, con hasta dos decimales.'],
    'three decimals' => [fn (): CostoPorHora => registrarCosto('2027-06-01', '45.125'), 'El costo por hora debe ser un monto mayor que cero, con hasta dos decimales.'],
    'negative' => [fn (): CostoPorHora => registrarCosto('2027-06-01', '-5'), 'El costo por hora debe ser un monto mayor que cero, con hasta dos decimales.'],
    'replacing the cost in effect from today' => [fn (): CostoPorHora => registrarCosto('2027-05-10'), 'Un costo que reemplaza al vigente debe empezar después de hoy.'],
    'replacing the cost in effect from the past' => [fn (): CostoPorHora => registrarCosto('2027-03-01'), 'Un costo que reemplaza al vigente debe empezar después de hoy.'],
    'replacing the cost in effect with an end date' => [fn (): CostoPorHora => registrarCosto('2027-06-01', hasta: '2027-06-30'), 'Un costo que reemplaza al vigente no lleva fecha de fin; la categoría quedaría sin costo después de esa fecha.'],
    'end before start' => [fn (): CostoPorHora => registrarCosto('2027-06-01', hasta: '2027-05-31'), 'La fecha de fin no puede ser anterior a la de inicio.'],
]);

it('deletes a cost not yet in effect and reopens the cost it had closed', function (): void {
    $futuro = registrarCosto('2027-06-01');

    app(EliminarCostoPorHora::class)($futuro->id);

    expect(CostoPorHora::query()->whereKey($futuro->id)->exists())->toBeFalse()
        ->and($this->vigente->refresh()->vigente_hasta)->toBeNull()
        ->and(DB::table('aud_registros')->where('tabla', 'doc_costos_hora')->where('operacion', 'DELETE')->count())->toBe(1);
});

it('refuses to delete a cost already in effect', function (): void {
    expect(fn () => app(EliminarCostoPorHora::class)($this->vigente->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Solo se puede eliminar un costo que aún no entra en vigencia; para cambiarlo, registra uno nuevo.')
        ->and(CostoPorHora::query()->whereKey($this->vigente->id)->exists())->toBeTrue();
});

it('records which cost a new one closed', function (): void {
    expect(registrarCosto('2027-06-01')->cerro_costo_id)->toBe($this->vigente->id)
        ->and(registrarCosto('2027-02-01', categoria: 'bachiller')->cerro_costo_id)->toBeNull();
});

it('reopens only the cost the deleted one closed, never one closed on purpose', function (): void {
    $cerradoAMano = CostoPorHora::query()->create(['grado_academico_id' => GradoAcademico::idDe('bachiller'), 'costo_hora' => '30.00', 'vigente_desde' => '2027-01-01', 'vigente_hasta' => '2027-06-30', 'registrado_por' => $this->usuario->id]);
    $siguiente = registrarCosto('2027-07-01', categoria: 'bachiller');

    app(EliminarCostoPorHora::class)($siguiente->id);

    expect($cerradoAMano->refresh()->vigente_hasta->toDateString())->toBe('2027-06-30');
});

it('refuses to delete a cost a later one already replaced', function (): void {
    $junio = registrarCosto('2027-06-01');
    registrarCosto('2027-07-01');

    expect(fn () => app(EliminarCostoPorHora::class)($junio->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Otro costo más reciente ya reemplazó a este; elimina primero el más reciente.')
        ->and(CostoPorHora::query()->count())->toBe(3);
});

it('tells the user when the cost to delete is already gone', function (): void {
    $futuro = registrarCosto('2027-06-01');
    app(EliminarCostoPorHora::class)($futuro->id);

    expect(fn () => app(EliminarCostoPorHora::class)($futuro->id))->toThrow(ReglaDeNegocioIncumplida::class, 'Ese costo por hora ya no existe; actualiza la página.')
        ->and($this->vigente->refresh()->vigente_hasta)->toBeNull();
});
