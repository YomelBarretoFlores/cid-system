<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Certificacion\Database\Seeders\HistorialDemoSeeder;
use App\Modules\Docencia\Database\Seeders\ControlDeClasesDemoSeeder;
use App\Modules\Docencia\Database\Seeders\DelegadosDemoSeeder;
use App\Modules\Docencia\Database\Seeders\NotasCompletasDemoSeeder;
use App\Modules\Docencia\Database\Seeders\SilabosDemoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

// Completes an existing demo without deleting anything: every piece checks first, so the demo container can run it on every start (docker/app/entrypoint.sh).
final class ComplementoDemoSeeder extends Seeder
{
    public function run(): void
    {
        throw_if(app()->isProduction(), RuntimeException::class, 'Demo data must never be seeded in production.');

        DB::transaction(fn () => $this->call([
            SilabosDemoSeeder::class,
            DelegadosDemoSeeder::class,
            ControlDeClasesDemoSeeder::class,
            NotasCompletasDemoSeeder::class,
            HistorialDemoSeeder::class,
        ], silent: true));
    }
}
