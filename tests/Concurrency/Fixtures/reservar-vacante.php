<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\ReservaDeVacante;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $grupoId, $inicio] = $argv;
DB::select('select 1');
time_sleep_until((float) $inicio);

try {
    DB::transaction(function () use ($app, $grupoId): void {
        $app->make(ReservaDeVacante::class)((int) $grupoId);
        // Holds the row until commit so the other process must run while this one is inside its transaction.
        usleep(500_000);
    });
    echo 'reservada';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
