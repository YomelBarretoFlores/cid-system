<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Contracts\IdiomasConNotasCerradas;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Tests\Support\ActasFinales;

it('offers the languages with closed actas and the completed levels without a valid certificate', function (): void {
    $persona = Persona::factory()->create();
    $basico = [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado']];
    ActasFinales::historial($persona, 'Inglés', $basico);
    ActasFinales::historial($persona, 'Portugués', $basico);
    ActasFinales::historial($persona, 'Quechua', [[1, '12.00', 'aprobado']]);
    ActasFinales::historial($persona, 'Francés', [[1, '15.00', 'aprobado']], 'abierta');
    $emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    app(EmitirCertificadoDeNivel::class)($persona->id, 'Portugués', NivelCurricular::Basico, $emisor);

    expect(app(IdiomasConNotasCerradas::class)($persona->id))->toBe(['Inglés', 'Portugués', 'Quechua'])
        ->and(array_map(fn (NivelPorCertificar $nivel): string => $nivel->etiqueta(), app(NivelesPorCertificar::class)($persona->id)))->toBe(['Inglés · Básico']);
});
