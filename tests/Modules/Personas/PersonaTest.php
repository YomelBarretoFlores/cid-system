<?php

declare(strict_types=1);

use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

it('rejects malformed identity documents and phone numbers', function (array $attributes, string $constraint): void {
    expect(fn () => Persona::factory()->create($attributes))->toThrow(QueryException::class, $constraint);
})->with([
    'DNI with 7 digits' => [['numero_documento' => '1234567'], 'per_personas_documento_check'],
    'DNI with letters' => [['numero_documento' => '1234567A'], 'per_personas_documento_check'],
    'CE too short' => [['tipo_documento' => TipoDocumento::Ce, 'numero_documento' => '12345'], 'per_personas_documento_check'],
    'passport with symbols' => [['tipo_documento' => TipoDocumento::Pasaporte, 'numero_documento' => 'AB-12345'], 'per_personas_documento_check'],
    'phone with letters' => [['celular' => '9871A3456'], 'per_personas_celular_check'],
]);

it('accepts valid documents of every type', function (TipoDocumento $tipo, string $numero): void {
    $persona = Persona::factory()->create(['tipo_documento' => $tipo, 'numero_documento' => $numero]);

    expect($persona->refresh()->tipo_documento)->toBe($tipo);
})->with([
    'DNI' => [TipoDocumento::Dni, '70123456'],
    'CE' => [TipoDocumento::Ce, '001234567'],
    'passport' => [TipoDocumento::Pasaporte, 'X1234567'],
]);

it('registers each document only once per type', function (): void {
    Persona::factory()->create(['numero_documento' => '70123456']);

    expect(fn () => Persona::factory()->create(['numero_documento' => '70123456']))
        ->toThrow(QueryException::class, 'per_personas_tipo_documento_numero_documento_unique');
});

it('allows the same number under a different document type', function (): void {
    Persona::factory()->create(['tipo_documento' => TipoDocumento::Ce, 'numero_documento' => '70123456']);

    expect(Persona::factory()->create(['numero_documento' => '70123456'])->exists)->toBeTrue();
});

it('normalizes document numbers to uppercase and emails to lowercase', function (): void {
    $persona = Persona::factory()->create([
        'tipo_documento' => TipoDocumento::Pasaporte,
        'numero_documento' => ' x1234567 ',
        'correo' => ' Ana.Quispe@Example.COM ',
    ]);

    expect($persona->refresh()->numero_documento)->toBe('X1234567')
        ->and($persona->correo)->toBe('ana.quispe@example.com');
});

it('builds the full name as surnames, first names', function (): void {
    $completa = Persona::factory()->make(['apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa Elena']);
    $sinMaterno = Persona::factory()->make(['apellido_paterno' => 'Smith', 'apellido_materno' => null, 'nombres' => 'John']);

    expect($completa->nombre_completo)->toBe('Quispe Mamani, Rosa Elena')
        ->and($sinMaterno->nombre_completo)->toBe('Smith, John');
});

it('defaults the link with the university to general public', function (): void {
    $persona = Persona::query()->create([
        'tipo_documento' => TipoDocumento::Dni,
        'numero_documento' => '70123456',
        'apellido_paterno' => 'Rojas',
        'nombres' => 'Luis',
    ]);

    expect($persona->refresh()->tipo_vinculo)->toBe(TipoVinculo::PublicoGeneral)
        ->and(Str::isUlid($persona->ulid))->toBeTrue()
        ->and($persona->getRouteKey())->toBe($persona->ulid);
});

it('links a person to at most one user account', function (): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    expect(fn () => Persona::factory()->create(['user_id' => $user->id]))
        ->toThrow(QueryException::class, 'per_personas_user_id_unique');
});

it('keeps the person when the user account is deleted', function (): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);

    $user->delete();

    expect($persona->refresh()->user_id)->toBeNull();
});

it('finds people by name ignoring accents, eñe and case, or by document prefix', function (string $termino): void {
    $buscada = Persona::factory()->create([
        'numero_documento' => '70123456', 'apellido_paterno' => 'Maguiña', 'apellido_materno' => 'Agüero', 'nombres' => 'José Ramón',
    ]);
    Persona::factory()->create(['numero_documento' => '41234567', 'apellido_paterno' => 'Castillo', 'apellido_materno' => 'Tamara', 'nombres' => 'Noemí']);

    expect(Persona::query()->coincideCon($termino)->pluck('id')->all())->toBe([$buscada->id]);
})->with(['maguina', 'MAGUIÑA', 'aguero jose', 'ramon', '7012']);

it('searches names without the nondeterministic collation that PostgreSQL 17 cannot use with LIKE', function (): void {
    expect(Persona::query()->coincideCon('maguiña')->toSql())->not->toContain('collate');
});
