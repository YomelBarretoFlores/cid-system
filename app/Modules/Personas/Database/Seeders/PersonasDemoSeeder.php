<?php

declare(strict_types=1);

namespace App\Modules\Personas\Database\Seeders;

use App\Modules\Personas\Actions\GuardarTipoDeVinculo;
use App\Modules\Personas\Models\Persona;
use App\Modules\Personas\Models\TipoVinculo;
use Illuminate\Database\Seeder;

final class PersonasDemoSeeder extends Seeder
{
    public function run(): void
    {
        // An extra link type added the way staff would; the payments seeder then gives it a fee for every concept.
        app(GuardarTipoDeVinculo::class)(null, ['nombre' => 'Egresado UNASAM', 'orden' => (int) TipoVinculo::query()->max('orden') + 1, 'activo' => true, 'por_defecto' => false]);

        Persona::factory()->count(15)->create();
        Persona::factory()->count(10)->estudianteUnasam()->create();
        Persona::factory()->count(3)->extranjera()->create();
    }
}
