<?php

declare(strict_types=1);

const MODULES = [
    'Academico',
    'Personas',
    'Matricula',
    'Pagos',
    'Docencia',
    'Certificacion',
    'Tablero',
    'Tramites',
];

const PUBLIC_API = [
    'Contracts',
    'Data',
    'Enums',
    'Events',
];

arch('php preset')->preset()->php();

arch('security preset')->preset()->security();

arch('modules and shared code use strict types')
    ->expect(['App\Modules', 'App\Shared'])
    ->toUseStrictTypes();

arch('actions are final and invokable')
    ->expect(array_map(fn (string $module): string => "App\\Modules\\{$module}\\Actions", MODULES))
    ->toBeFinal()
    ->toBeInvokable();

foreach (MODULES as $module) {
    $otherModules = array_values(array_filter(MODULES, fn (string $other): bool => $other !== $module));

    $publicApi = collect($otherModules)
        ->crossJoin(PUBLIC_API)
        ->map(fn (array $pair): string => "App\\Modules\\{$pair[0]}\\{$pair[1]}")
        ->all();

    // Factories and seeders are test scaffolding and may build records of other modules.
    arch("{$module} only depends on the public API of other modules")
        ->expect("App\\Modules\\{$module}")
        ->not->toUse(array_map(fn (string $other): string => "App\\Modules\\{$other}", $otherModules))
        ->ignoring([...$publicApi, "App\\Modules\\{$module}\\Database"]);
}
