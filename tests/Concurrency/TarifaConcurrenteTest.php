<?php

declare(strict_types=1);

use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Models\Persona;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * @param  array{pago: string, cierre: string}  $esperas
 * @return array{salidas: list<string>, tarifa: Tarifa}
 */
function cerrarTarifaMientrasSeRegistraUnPago(array $esperas, float $retrasoDelCierre, string $modo = 'action'): array
{
    // Payments, people and fees refuse TRUNCATE or are referenced by payments, so these rows stay until the next migrate:fresh.
    $persona = Persona::factory()->create(['tipo_vinculo' => 'publico_general']);
    $concepto = Concepto::factory()->create();
    $tarifa = Tarifa::factory()->for($concepto)->create(['tipo_vinculo' => 'publico_general', 'vigente_desde' => '2026-01-01']);
    $recibo = (string) tempnam(sys_get_temp_dir(), 'recibo');
    file_put_contents($recibo, "%PDF-1.4\n% ".uniqid('', true)."\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
    $conexion = config('database.connections.pgsql');
    $inicio = microtime(true) + 2;

    $resultados = Process::pool(function (Pool $pool) use ($conexion, $inicio, $persona, $concepto, $tarifa, $recibo, $esperas, $retrasoDelCierre, $modo): void {
        $proceso = fn () => $pool->path(base_path())->env([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => $conexion['host'],
            'DB_PORT' => (string) $conexion['port'],
            'DB_DATABASE' => $conexion['database'],
            'DB_USERNAME' => $conexion['username'],
            'DB_PASSWORD' => $conexion['password'],
        ]);
        $proceso()->command([PHP_BINARY, 'tests/Concurrency/Fixtures/registrar-pago.php', (string) ($inicio + 0.3 - $retrasoDelCierre), (string) $persona->id, (string) $concepto->id, '2026-10-20', $recibo, $esperas['pago']]);
        $proceso()->command([PHP_BINARY, 'tests/Concurrency/Fixtures/cerrar-tarifa.php', (string) ($inicio + $retrasoDelCierre), (string) $tarifa->id, '2026-10-10', $esperas['cierre'], $modo]);
    })->start()->wait();

    Pago::query()->where('tarifa_id', $tarifa->id)->each(fn (Pago $pago): bool => Storage::disk('local')->delete($pago->recibo_path));
    unlink($recibo);

    return ['salidas' => collect($resultados)->map(fn ($resultado): string => trim($resultado->output().$resultado->errorOutput()))->values()->all(), 'tarifa' => $tarifa->refresh()];
}

it('refuses to close a fee before a payment still being registered with it', function (): void {
    ['salidas' => $salidas, 'tarifa' => $tarifa] = cerrarTarifaMientrasSeRegistraUnPago(['pago' => '700000', 'cierre' => '0'], retrasoDelCierre: 0.3);

    expect($salidas)->toBe(['registrado', 'La tarifa se aplicó a un pago del 20/10/2026; no puede cerrarse antes de esa fecha.'])
        ->and($tarifa->vigente_hasta)->toBeNull();
});

it('refuses a payment on a fee being closed before its date', function (): void {
    ['salidas' => $salidas, 'tarifa' => $tarifa] = cerrarTarifaMientrasSeRegistraUnPago(['pago' => '0', 'cierre' => '700000'], retrasoDelCierre: 0.0);

    expect($salidas)->toBe(['No hay una tarifa vigente para este concepto en la fecha de pago.', 'cerrada'])
        ->and($tarifa->vigente_hasta?->toDateString())->toBe('2026-10-10')
        ->and(Pago::query()->where('tarifa_id', $tarifa->id)->exists())->toBeFalse();
});

it('makes the database refuse a direct closing before a payment still being registered', function (): void {
    ['salidas' => $salidas, 'tarifa' => $tarifa] = cerrarTarifaMientrasSeRegistraUnPago(['pago' => '700000', 'cierre' => '0'], retrasoDelCierre: 0.3, modo: 'sql');

    expect($salidas)->toBe(['registrado', 'rechazada por la base'])
        ->and($tarifa->vigente_hasta)->toBeNull();
});

it('applies the new fee to a payment registered while the fee is being replaced', function (): void {
    // Payments, people and fees refuse TRUNCATE or are referenced by payments, so these rows stay until the next migrate:fresh.
    $persona = Persona::factory()->create(['tipo_vinculo' => 'publico_general']);
    $concepto = Concepto::factory()->create();
    $anterior = Tarifa::factory()->for($concepto)->create(['tipo_vinculo' => 'publico_general', 'vigente_desde' => '2026-01-01']);
    $recibo = (string) tempnam(sys_get_temp_dir(), 'recibo');
    file_put_contents($recibo, "%PDF-1.4\n% ".uniqid('', true)."\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
    $conexion = config('database.connections.pgsql');
    $inicio = microtime(true) + 2;

    $resultados = Process::pool(function (Pool $pool) use ($conexion, $inicio, $persona, $concepto, $recibo): void {
        $proceso = fn () => $pool->path(base_path())->env([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => $conexion['host'],
            'DB_PORT' => (string) $conexion['port'],
            'DB_DATABASE' => $conexion['database'],
            'DB_USERNAME' => $conexion['username'],
            'DB_PASSWORD' => $conexion['password'],
        ]);
        $proceso()->command([PHP_BINARY, 'tests/Concurrency/Fixtures/registrar-tarifa.php', (string) $inicio, (string) $concepto->id, '2026-10-01', '700000']);
        $proceso()->command([PHP_BINARY, 'tests/Concurrency/Fixtures/registrar-pago.php', (string) ($inicio + 0.3), (string) $persona->id, (string) $concepto->id, '2026-10-20', $recibo]);
    })->start()->wait();

    $pago = Pago::query()->where('concepto_id', $concepto->id)->first();
    if ($pago !== null) {
        Storage::disk('local')->delete($pago->recibo_path);
    }
    unlink($recibo);

    expect(collect($resultados)->map(fn ($resultado): string => trim($resultado->output().$resultado->errorOutput()))->values()->all())
        ->toBe(['tarifa registrada', 'registrado'])
        ->and($pago?->tarifa_id)->not->toBe($anterior->id)
        ->and($pago?->tarifa_id)->toBe(Tarifa::query()->where('concepto_id', $concepto->id)->where('vigente_desde', '2026-10-01')->value('id'));
});
