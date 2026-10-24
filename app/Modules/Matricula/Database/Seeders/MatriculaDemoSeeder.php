<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Database\Seeders;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class MatriculaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $grupo = Grupo::query()->orderBy('id')->firstOrFail();
        $personas = Persona::query()->orderBy('id')->limit(10)->get();

        DB::transaction(function () use ($grupo, $personas): void {
            $personas->each(fn (Persona $persona): Matricula => Matricula::factory()->create([
                'persona_id' => $persona->id,
                'grupo_id' => $grupo->id,
            ]));

            DB::table('aca_grupos')->where('id', $grupo->id)->increment('matriculados', $personas->count());
        });
    }
}
