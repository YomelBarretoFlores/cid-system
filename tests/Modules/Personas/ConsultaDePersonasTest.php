<?php

declare(strict_types=1);

use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Models\Persona;

beforeEach(function (): void {
    $this->ana = Persona::factory()->create([
        'numero_documento' => '40123456', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Ana María',
    ]);
    $this->luis = Persona::factory()->create([
        'tipo_documento' => TipoDocumento::Pasaporte, 'numero_documento' => 'AB1234567',
        'apellido_paterno' => 'Rojas', 'apellido_materno' => 'Quispe', 'nombres' => 'Luis',
    ]);
});

it('labels people with their document and full name', function (): void {
    expect(app(EtiquetasDePersonas::class)([$this->ana->id, $this->luis->id]))->toBe([
        $this->ana->id => 'DNI 40123456 · Quispe Mamani, Ana María',
        $this->luis->id => 'Pasaporte AB1234567 · Rojas Quispe, Luis',
    ]);
});

it('finds people by the start of their document or any part of their name', function (string $termino, array $esperados): void {
    $ids = array_keys(app(BusquedaDePersonas::class)($termino));

    expect($ids)->toBe(array_map(fn (string $persona): int => $this->{$persona}->id, $esperados));
})->with([
    'document prefix' => ['4012', ['ana']],
    'document lowercase' => ['ab12', ['luis']],
    'document middle does not match' => ['123456', []],
    'surname in both people, ordered by surname' => ['quispe', ['ana', 'luis']],
    'first name' => ['maría', ['ana']],
    'wildcards are literal' => ['%', []],
    'blank' => ['   ', []],
]);

it('selects the IDs of every matching person as a subquery, without a limit', function (): void {
    Persona::factory()->count(60)->create(['apellido_paterno' => 'Quispe']);

    $subconsulta = app(SubconsultaDePersonas::class)('quispe');

    expect($subconsulta->count())->toBe(62)
        ->and(app(SubconsultaDePersonas::class)('   ')->count())->toBe(0)
        ->and(app(SubconsultaDePersonas::class)('%')->count())->toBe(0)
        ->and(app(BusquedaDePersonas::class)('quispe'))->toHaveCount(50);
});
