<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Personas\Models\Persona;

// PostgreSQL accepts at most 65,535 bound parameters per statement; a year of payments can approach that.
const IDS_DE_MAS = 70_000;

function idsConUnoReal(int $real): array
{
    return [...range(10_000_000, 10_000_000 + IDS_DE_MAS - 1), $real];
}

it('answers a lookup of more IDs than PostgreSQL can bind in one statement', function (string $contrato, Closure $real): void {
    [$id, $esperado] = $real();

    expect(array_keys(app($contrato)(idsConUnoReal($id))))->toBe($esperado === null ? [] : [$id]);
})->with([
    'person cards' => [FichasDePersonas::class, fn (): array => [Persona::factory()->create()->id, true]],
    'person labels' => [EtiquetasDePersonas::class, fn (): array => [Persona::factory()->create()->id, true]],
    'group details' => [DetalleDeGrupos::class, fn (): array => [Grupo::factory()->create()->id, true]],
    'group schedules' => [HorariosDeGrupos::class, fn (): array => [Grupo::factory()->create()->id, true]],
    'group teachers' => [DocentesDeGrupos::class, fn (): array => [Grupo::factory()->create()->id, null]],
    'payment context' => [ContextoAcademicoDePagos::class, function (): array {
        $pago = Pago::factory()->validado()->create();
        Matricula::factory()->create(['pago_id' => $pago->id]);

        return [$pago->id, true];
    }],
]);
