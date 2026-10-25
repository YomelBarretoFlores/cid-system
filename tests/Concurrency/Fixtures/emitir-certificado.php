<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $personaId, $usuarioId] = $argv;
$usuario = User::query()->findOrFail((int) $usuarioId);
esperarHasta((float) $inicio);

try {
    $app->make(EmitirCertificadoDeNivel::class)((int) $personaId, 'Inglés', NivelCurricular::Basico, $usuario);
    echo 'emitido';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
