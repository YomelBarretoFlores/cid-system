<?php

declare(strict_types=1);

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Support\Facades\Log;

it('keeps serving the request when the daily log file cannot be written', function (): void {
    $archivo = tempnam(sys_get_temp_dir(), 'ciln-log');
    // A path below a regular file can never be opened, like a log file owned by another account.
    foreach (['daily', 'diario'] as $canal) {
        config(["logging.channels.{$canal}.path" => "{$archivo}/laravel.log", "logging.channels.{$canal}.level" => 'debug']);
        Log::forgetChannel($canal);
    }
    config(['logging.default' => 'daily']);
    $fallo = null;

    try {
        RegistroDeRechazos::registrar(new ReglaDeNegocioIncumplida('La nota 25 no es válida.'));
    } catch (Throwable $error) {
        $fallo = $error->getMessage();
    } finally {
        unlink($archivo);
    }

    expect($fallo)->toBeNull();
});
