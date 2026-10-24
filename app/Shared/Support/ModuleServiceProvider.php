<?php

declare(strict_types=1);

namespace App\Shared\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $module;

    public function boot(): void
    {
        $path = app_path("Modules/{$this->module}");

        $this->loadMigrationsFrom("{$path}/Database/Migrations");
        $this->loadViewsFrom("{$path}/resources/views", Str::kebab($this->module));
        $this->loadModuleRoutes($path);
    }

    private function loadModuleRoutes(string $path): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        if (is_file("{$path}/routes/web.php")) {
            Route::middleware('web')->group("{$path}/routes/web.php");
        }

        if (is_file("{$path}/routes/api.php")) {
            Route::middleware('api')->prefix('api/v1')->group("{$path}/routes/api.php");
        }
    }
}
