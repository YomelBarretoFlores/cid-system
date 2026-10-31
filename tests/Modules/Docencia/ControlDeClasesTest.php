<?php

declare(strict_types=1);

use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\EstadoDeLaClase;
use Carbon\CarbonImmutable;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    [$this->grupo, $estudiantes] = PortalDocente::grupoConEstudiantes($this->docente, 'Quispe', 'Álvarez');
    $this->clases = PortalDocente::clasesEnCadaEstado($this->grupo, $this->docente, $this->usuario, $estudiantes);
});

it('filters each state in SQL exactly as the state of each class reads at the same time', function (string $hora): void {
    $ahora = CarbonImmutable::parse($hora, 'America/Lima');
    $clases = ClaseDictada::query()->get();

    foreach (EstadoDeClase::cases() as $estado) {
        $esperadas = $clases->filter(fn (ClaseDictada $clase): bool => EstadoDeLaClase::de($clase, $ahora) === $estado)->values()->modelKeys();
        $filtradas = EstadoDeLaClase::filtrar(ClaseDictada::query(), $estado, $ahora)->pluck('id')->all();

        expect($filtradas)->toEqualCanonicalizing($esperadas, "{$estado->value} at {$hora}");
    }
})->with([
    'while the delegate can still answer' => '2027-03-08 21:59:59',
    'at the confirmation deadline' => '2027-03-08 22:00:00',
    'just after the confirmation deadline' => '2027-03-08 22:00:00.000001',
    'at the end of the closing call' => '2027-03-10 20:30:00',
    'just after the closing call' => '2027-03-10 20:30:01',
    'weeks later' => '2027-04-20 12:00',
]);

it('reads each built class in the state the coordination filters by', function (): void {
    $ahora = CarbonImmutable::parse('2027-03-20 12:00', 'America/Lima');

    expect(array_map(fn (ClaseDictada $clase): EstadoDeClase => EstadoDeLaClase::de($clase, $ahora), $this->clases))->toBe([
        'confirmada' => EstadoDeClase::Confirmada,
        'observada' => EstadoDeClase::Observada,
        'pendiente' => EstadoDeClase::SinConfirmar,
        'inicio' => EstadoDeClase::SinLlamadoDeCierre,
        'posterior' => EstadoDeClase::RegistroPosterior,
        'anterior' => EstadoDeClase::RegistroAnterior,
    ]);
});
