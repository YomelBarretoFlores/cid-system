<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

// PostgreSQL accepts at most 65,535 bound parameters per statement; a year of payments can approach that.
const IDS_DE_MAS = 70_000;

function idsConUnoReal(int $real): array
{
    return [...range(10_000_000, 10_000_000 + IDS_DE_MAS - 1), $real];
}

it('answers a lookup of more IDs than PostgreSQL can bind in one statement', function (string $contrato, Closure $real): void {
    [$id] = $real();

    expect(array_keys(app($contrato)(idsConUnoReal($id))))->toBe([$id]);
})->with([
    'person cards' => [FichasDePersonas::class, fn (): array => [Persona::factory()->create()->id, true]],
    'person labels' => [EtiquetasDePersonas::class, fn (): array => [Persona::factory()->create()->id, true]],
    'group details' => [DetalleDeGrupos::class, fn (): array => [Grupo::factory()->create()->id, true]],
    'group schedules' => [HorariosDeGrupos::class, fn (): array => [Grupo::factory()->create()->id, true]],
    'group teachers' => [DocentesDeGrupos::class, function (): array {
        $carga = CargaAcademica::query()->create(['grupo_id' => Grupo::factory()->create()->id, 'docente_id' => Docente::factory()->create()->id, 'asignado_por' => User::factory()->create()->id]);

        return [$carga->grupo_id, true];
    }],
    'payment context of an enrollment' => [ContextoAcademicoDePagos::class, function (): array {
        $pago = Pago::factory()->validado()->create();
        Matricula::factory()->create(['pago_id' => $pago->id]);

        return [$pago->id, true];
    }],
    'payment context of a placement request' => [ContextoAcademicoDePagos::class, function (): array {
        $pago = Pago::factory()->validado()->create();
        SolicitudClasificacion::factory()->create(['pago_id' => $pago->id]);

        return [$pago->id, true];
    }],
]);
