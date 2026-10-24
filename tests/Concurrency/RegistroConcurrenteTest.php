<?php

declare(strict_types=1);

use App\Modules\Personas\Actions\RegistrarCuentaDeEstudiante;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

const DOCUMENTOS_DE_PRUEBA = ['49990001', '49990002', '49990003'];
const CORREOS_DE_PRUEBA = ['rosa.a@concurrencia.test', 'rosa.b@concurrencia.test'];

afterEach(function (): void {
    Persona::query()->whereIn('numero_documento', DOCUMENTOS_DE_PRUEBA)->delete();
    User::query()->whereIn('email', CORREOS_DE_PRUEBA)->delete();
});

it('creates one account when two people register the same new document at once', function (): void {
    expect(enParalelo('registrar-cuenta.php', [[DOCUMENTOS_DE_PRUEBA[0], CORREOS_DE_PRUEBA[0]], [DOCUMENTOS_DE_PRUEBA[0], CORREOS_DE_PRUEBA[1]]]))
        ->toBe([RegistrarCuentaDeEstudiante::DOCUMENTO_NO_DISPONIBLE, 'registrada'])
        ->and(Persona::query()->where('numero_documento', DOCUMENTOS_DE_PRUEBA[0])->count())->toBe(1)
        ->and(User::query()->whereIn('email', CORREOS_DE_PRUEBA)->count())->toBe(1);
});

it('creates one account when two documents register the same email at once', function (): void {
    expect(enParalelo('registrar-cuenta.php', [[DOCUMENTOS_DE_PRUEBA[1], CORREOS_DE_PRUEBA[0]], [DOCUMENTOS_DE_PRUEBA[2], CORREOS_DE_PRUEBA[0]]]))
        ->toBe([RegistrarCuentaDeEstudiante::CORREO_EN_USO, 'registrada'])
        ->and(User::query()->where('email', CORREOS_DE_PRUEBA[0])->count())->toBe(1)
        ->and(Persona::query()->whereIn('numero_documento', [DOCUMENTOS_DE_PRUEBA[1], DOCUMENTOS_DE_PRUEBA[2]])->count())->toBe(1);
});
