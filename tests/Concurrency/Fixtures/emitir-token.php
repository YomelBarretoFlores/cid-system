<?php

declare(strict_types=1);

use App\Shared\Auth\Actions\EmitirTokenDeApi;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $email, $direccion] = $argv;
esperarHasta((float) $inicio);

DB::transaction(function () use ($app, $email, $direccion): void {
    $app->make(EmitirTokenDeApi::class)($email, 'password', $direccion, 'App móvil');
    // Keeps the new token uncommitted so the other sign-in runs meanwhile.
    usleep(500_000);
});

echo 'emitido';
