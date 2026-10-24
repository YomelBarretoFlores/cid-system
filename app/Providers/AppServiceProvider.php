<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\Support\Database\ConectorPostgres;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('db.connector.pgsql', fn (): ConectorPostgres => new ConectorPostgres(
            sinLimiteDeSentencia: $this->app->runningInConsole() && ConectorPostgres::liberaLimiteDeSentencia($_SERVER['argv'] ?? []),
        ));
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        Model::shouldBeStrict(! $this->app->isProduction());
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
