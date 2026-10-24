<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\RegistrarCostoPorHora;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $desde, $costo, $usuarioId] = $argv;
$usuario = User::query()->findOrFail((int) $usuarioId);
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $desde, $costo, $usuario): void {
        $app->make(RegistrarCostoPorHora::class)(CondicionLaboral::Contratado, $costo, CarbonImmutable::parse($desde), null, $usuario);
        usleep(500_000);
    });
    echo "{$desde}: hecho";
} catch (ReglaDeNegocioIncumplida $error) {
    echo "{$desde}: {$error->getMessage()}";
}
