<?php

declare(strict_types=1);

use App\Modules\Personas\Actions\RegistrarPersona;
use App\Modules\Personas\Data\DatosDePersona;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

function nuevaPersona(array $cambios = []): DatosDePersona
{
    return new DatosDePersona(...[
        'tipoDocumento' => 'dni', 'numeroDocumento' => '45678912', 'apellidoPaterno' => ' Huamán ', 'apellidoMaterno' => '',
        'nombres' => 'Lucía', 'celular' => '987 654 321', ...$cambios,
    ]);
}

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo(PermisoPersona::Gestionar->value);
});

it('registers a person with the default link type, normalized data and no consent unless given', function (): void {
    $persona = app(RegistrarPersona::class)(nuevaPersona(), $this->gestor);

    expect($persona->refresh())
        ->apellido_paterno->toBe('Huamán')
        ->apellido_materno->toBeNull()
        ->celular->toBe('987654321')
        ->tipo_vinculo->toBe('publico_general')
        ->tratamiento_aceptado_en->toBeNull();
});

it('refuses staff who cannot manage people', function (): void {
    app(RegistrarPersona::class)(nuevaPersona(), User::factory()->create());
})->throws(AuthorizationException::class);

it('refuses data the person record does not allow', function (array $cambios, string $mensaje): void {
    Persona::factory()->create(['tipo_documento' => 'dni', 'numero_documento' => '40000001']);

    expect(fn () => app(RegistrarPersona::class)(nuevaPersona($cambios), $this->gestor))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Persona::query()->count())->toBe(1);
})->with([
    'repeated document' => [['numeroDocumento' => '40000001'], RegistrarPersona::DOCUMENTO_REPETIDO],
    'short DNI' => [['numeroDocumento' => '4567891'], 'El DNI debe tener exactamente 8 dígitos.'],
    'no names' => [['nombres' => ' '], 'Escribe el apellido paterno y los nombres.'],
    'bad phone' => [['celular' => '98A'], RegistrarPersona::CELULAR_INVALIDO],
    'bad email' => [['correo' => 'lucia'], 'Escribe un correo personal válido.'],
    'unknown link type' => [['tipoVinculo' => 'inexistente'], 'El tipo de vínculo elegido no está disponible.'],
]);
