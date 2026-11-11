<?php

declare(strict_types=1);

use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Data\TarifaVigente;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\FuenteDeConcepto;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use Carbon\CarbonImmutable;

it('lists the fees in force on a date for the active concepts, by concept and link', function (): void {
    $matricula = Concepto::factory()->create(['codigo' => 'TUSNE-01', 'nombre' => 'Matrícula', 'fuente_id' => FuenteDeConcepto::idDe('tusne')]);
    $certificado = Concepto::factory()->create(['codigo' => 'TUPA-07', 'nombre' => 'Certificado de nivel', 'tipo' => TipoConcepto::Certificado, 'fuente_id' => FuenteDeConcepto::idDe('tupa')]);
    $inactivo = Concepto::factory()->create(['activo' => false]);
    Tarifa::factory()->for($matricula)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '180.00', 'vigente_desde' => '2026-03-01']);
    Tarifa::factory()->for($matricula)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '120.00', 'vigente_desde' => '2026-03-01']);
    Tarifa::factory()->for($matricula)->create([
        'tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '100.00', 'vigente_desde' => '2025-03-01', 'vigente_hasta' => '2026-02-28',
    ]);
    Tarifa::factory()->for($certificado)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '60.50', 'vigente_desde' => '2026-04-01', 'vigente_hasta' => '2026-11-30']);
    Tarifa::factory()->for($certificado)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '70.00', 'vigente_desde' => '2026-12-01']);
    Tarifa::factory()->for($inactivo)->create(['vigente_desde' => '2026-01-01']);

    $tarifas = app(TarifasVigentes::class)(CarbonImmutable::parse('2026-10-20'));

    expect(array_map(fn (TarifaVigente $tarifa): string => "{$tarifa->codigo} {$tarifa->tipoVinculo->value} {$tarifa->monto}", $tarifas))->toBe([
        'TUPA-07 publico_general 60.50',
        'TUSNE-01 estudiante_unasam 120.00',
        'TUSNE-01 publico_general 180.00',
    ])->and($tarifas[0])
        ->concepto->toBe('Certificado de nivel')
        ->fuente->toBe('tupa')->fuenteEtiqueta->toBe('TUPA')
        ->tipo->toBe(TipoConcepto::Certificado)
        ->and($tarifas[1]->tipo)->toBe(TipoConcepto::Matricula)
        ->and($tarifas[0]->vigenteDesde->toDateString())->toBe('2026-04-01');
});
