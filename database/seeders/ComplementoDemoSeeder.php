<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Certificacion\Database\Seeders\HistorialDemoSeeder;
use App\Modules\Docencia\Database\Seeders\ControlDeClasesDemoSeeder;
use App\Modules\Docencia\Database\Seeders\DelegadosDemoSeeder;
use App\Modules\Docencia\Database\Seeders\NotasCompletasDemoSeeder;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

// Completes an existing demo without deleting anything: every piece checks first, so the demo container can run it on every start (docker/app/entrypoint.sh).
final class ComplementoDemoSeeder extends Seeder
{
    private const array CUENTAS = ['admin@ciln.test', 'caja@ciln.test', 'docente@ciln.test', 'estudiante@ciln.test'];

    public function run(): void
    {
        throw_if(app()->isProduction(), RuntimeException::class, 'Demo data must never be seeded in production.');

        // A database with real data and a leftover DEMO_SEED=true has none of the demo accounts.
        if (User::query()->whereIn('email', self::CUENTAS)->count() < count(self::CUENTAS)) {
            $this->command?->warn('Complemento de demo omitido: la base no tiene las cuentas de demostración.');

            return;
        }

        DB::transaction(fn () => $this->call([
            DelegadosDemoSeeder::class,
            ControlDeClasesDemoSeeder::class,
            NotasCompletasDemoSeeder::class,
            HistorialDemoSeeder::class,
        ], silent: true));
    }
}
