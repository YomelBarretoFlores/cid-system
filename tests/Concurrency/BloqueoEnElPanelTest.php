<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Periodos\Pages\EditPeriodo;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

it('shows the busy message instead of a server error when a panel save waits past the lock limit', function (): void {
    $periodo = Periodo::factory()->create(['nombre' => 'Original']);
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);
    $conexion = config('database.connections.pgsql');

    $pagina = Livewire::actingAs($gestor)->test(EditPeriodo::class, ['record' => $periodo->getRouteKey()])->fillForm(['nombre' => 'Cambiado']);

    $retencion = Process::path(base_path())->env([
        'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => $conexion['host'], 'DB_PORT' => (string) $conexion['port'],
        'DB_DATABASE' => $conexion['database'], 'DB_USERNAME' => $conexion['username'], 'DB_PASSWORD' => $conexion['password'],
    ])->start([PHP_BINARY, 'tests/Concurrency/Fixtures/retener-fila.php', '0', 'aca_periodos', (string) $periodo->id, '3']);

    $esperando = fn (): bool => DB::table('pg_stat_activity')->where('query', 'like', '%pg_sleep%')->where('pid', '!=', DB::raw('pg_backend_pid()'))->exists();
    $hasta = microtime(true) + 10;
    while (! $esperando() && microtime(true) < $hasta) {
        usleep(100_000);
    }

    config(['database.connections.pgsql.lock_timeout' => '500ms']);
    DB::purge();

    $pagina->call('save')->assertNotified('Otra operación está modificando estos datos en este momento. Inténtalo de nuevo en unos segundos.');
    $retencion->wait();

    expect($periodo->refresh()->nombre)->toBe('Original');
});
