<?php

declare(strict_types=1);

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Tests\Support\ActasFinales;

it('issues one level certificate when two are requested for the same student at the same time', function (): void {
    $persona = Persona::factory()->create();
    ActasFinales::historial($persona, 'Inglés', [[1, '14.00', 'aprobado'], [2, '15.00', 'aprobado'], [3, '16.00', 'aprobado'], [4, '13.00', 'aprobado']]);
    $emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);

    $salidas = enParalelo('emitir-certificado.php', [[$persona->id, $emisor->id], [$persona->id, $emisor->id]]);

    $rechazos = array_values(array_filter($salidas, fn (string $salida): bool => $salida !== 'emitido'));

    expect(array_count_values($salidas)['emitido'] ?? 0)->toBe(1)
        ->and($rechazos)->toHaveCount(1)
        ->and($rechazos[0])->toStartWith('La persona ya tiene un certificado vigente del nivel Básico de Inglés')
        ->and(Documento::query()->where('persona_id', $persona->id)->whereNull('anulado_en')->count())->toBe(1);
});
