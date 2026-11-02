<?php

declare(strict_types=1);

use App\Shared\Audit\Support\CambiosDelRegistro;

it('lists only the fields whose value changed in an update', function (): void {
    $cambios = CambiosDelRegistro::calcular(
        ['id' => 4, 'nombre' => 'Inglés', 'activo' => true, 'orden' => 1, 'extras' => ['a' => 1], 'nota' => null],
        ['id' => 4, 'nombre' => 'Inglés', 'activo' => false, 'orden' => 1, 'extras' => ['a' => 2], 'nota' => 'nueva'],
    );

    expect($cambios)->toBe([
        ['campo' => 'activo', 'antes' => 'Sí', 'despues' => 'No'],
        ['campo' => 'extras', 'antes' => '{"a":1}', 'despues' => '{"a":2}'],
        ['campo' => 'nota', 'antes' => '(vacío)', 'despues' => 'nueva'],
    ]);
});

it('lists every field of an insert with only its new value and of a delete with only its old value', function (): void {
    expect(CambiosDelRegistro::calcular(null, ['id' => 9, 'nombre' => 'Aula 1']))->toBe([
        ['campo' => 'id', 'antes' => null, 'despues' => '9'],
        ['campo' => 'nombre', 'antes' => null, 'despues' => 'Aula 1'],
    ])->and(CambiosDelRegistro::calcular(['id' => 9, 'nombre' => 'Aula 1'], null))->toBe([
        ['campo' => 'id', 'antes' => '9', 'despues' => null],
        ['campo' => 'nombre', 'antes' => 'Aula 1', 'despues' => null],
    ]);
});

it('never shows the value of a sensitive field, even when it changed', function (string $campo): void {
    expect(CambiosDelRegistro::calcular([$campo => 'valor-anterior'], [$campo => 'valor-nuevo']))
        ->toBe([['campo' => $campo, 'antes' => '(oculto)', 'despues' => '(oculto)']])
        ->and(CambiosDelRegistro::calcular(null, [$campo => 'valor-nuevo']))
        ->toBe([['campo' => $campo, 'antes' => null, 'despues' => '(oculto)']]);
})->with(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes', 'cuenta_bancaria', 'api_token', 'client_secret']);
