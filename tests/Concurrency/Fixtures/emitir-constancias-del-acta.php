<?php

declare(strict_types=1);

use App\Modules\Certificacion\Actions\EmitirConstanciasDeNotasDelActa;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $actaId, $usuarioId] = $argv;
$usuario = User::query()->findOrFail((int) $usuarioId);
esperarHasta((float) $inicio);

try {
    $resumen = $app->make(EmitirConstanciasDeNotasDelActa::class)((int) $actaId, $usuario);
    echo 'emitidas '.count($resumen->emitidos).', omitidas '.$resumen->omitidos;
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
