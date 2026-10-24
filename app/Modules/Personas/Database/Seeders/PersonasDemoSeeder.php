<?php

declare(strict_types=1);

namespace App\Modules\Personas\Database\Seeders;

use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Seeder;

final class PersonasDemoSeeder extends Seeder
{
    public function run(): void
    {
        Persona::factory()->count(15)->create();
        Persona::factory()->count(10)->estudianteUnasam()->create();
        Persona::factory()->count(3)->extranjera()->create();
    }
}
