<?php

declare(strict_types=1);

use App\Modules\Personas\Actions\ComprobarDocumento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

it('rejects malformed identity documents with the rule of their catalog type', function (array $attributes, string $mensaje): void {
    expect(fn () => app(ComprobarDocumento::class)(Persona::factory()->make($attributes)))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
})->with([
    'DNI with 7 digits' => [['numero_documento' => '1234567'], 'El DNI debe tener exactamente 8 dígitos.'],
    'DNI with letters' => [['numero_documento' => '1234567A'], 'El DNI debe tener exactamente 8 dígitos.'],
    'CE too short' => [['tipo_documento' => 'ce', 'numero_documento' => '12345'], 'El documento debe tener de 6 a 12 letras o números, sin espacios ni guiones.'],
    'passport with symbols' => [['tipo_documento' => 'pasaporte', 'numero_documento' => 'AB-12345'], 'El documento debe tener de 6 a 12 letras o números, sin espacios ni guiones.'],
]);

it('rejects malformed phone numbers', function (): void {
    expect(fn () => Persona::factory()->create(['celular' => '9871A3456']))->toThrow(QueryException::class, 'per_personas_celular_check');
});

it('accepts valid documents of every type', function (string $tipo, string $numero): void {
    $persona = Persona::factory()->make(['tipo_documento' => $tipo, 'numero_documento' => $numero]);
    app(ComprobarDocumento::class)($persona);
    $persona->save();

    expect($persona->refresh()->tipo_documento)->toBe($tipo);
})->with([
    'DNI' => ['dni', '70123456'],
    'CE' => ['ce', '001234567'],
    'passport' => ['pasaporte', 'X1234567'],
]);

it('registers each document only once per type', function (): void {
    Persona::factory()->create(['numero_documento' => '70123456']);

    expect(fn () => Persona::factory()->create(['numero_documento' => '70123456']))
        ->toThrow(QueryException::class, 'per_personas_tipo_documento_numero_documento_unique');
});

it('allows the same number under a different document type', function (): void {
    Persona::factory()->create(['tipo_documento' => 'ce', 'numero_documento' => '70123456']);

    expect(Persona::factory()->create(['numero_documento' => '70123456'])->exists)->toBeTrue();
});

it('normalizes document numbers to uppercase and emails to lowercase', function (): void {
    $persona = Persona::factory()->create([
        'tipo_documento' => 'pasaporte',
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
        'tipo_documento' => 'dni',
        'numero_documento' => '70123456',
        'apellido_paterno' => 'Rojas',
        'nombres' => 'Luis',
    ]);

    expect($persona->refresh()->tipo_vinculo)->toBe('publico_general')
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
