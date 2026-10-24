<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../../vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Connects before the start barrier so both processes reach the Action at the same moment.
DB::select('select 1');

function esperarHasta(float $inicio): void
{
    // A slow boot past the barrier must not turn time_sleep_until's warning into a failure.
    if ($inicio > microtime(true)) {
        time_sleep_until($inicio);
    }
}

return $app;
