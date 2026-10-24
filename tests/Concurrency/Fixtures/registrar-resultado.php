<?php

declare(strict_types=1);

use App\Modules\Matricula\Actions\RegistrarResultadoDeClasificacion;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $solicitudId, $cicloId, $userId] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $solicitudId, $cicloId, $userId): void {
        $app->make(RegistrarResultadoDeClasificacion::class)((int) $solicitudId, new ResultadoDeClasificacion(CarbonImmutable::today('America/Lima'), '80', (int) $cicloId, null), User::query()->findOrFail((int) $userId));
        usleep(500_000);
    });
    echo 'registrado';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
