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

const INTERNAL_LAYERS = [
    'Actions',
    'Filament',
    'Http',
    'Listeners',
    'Livewire',
    'Models',
    'Policies',
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
    $foreignInternals = collect(MODULES)
        ->reject(fn (string $other): bool => $other === $module)
        ->crossJoin(INTERNAL_LAYERS)
        ->map(fn (array $pair): string => "App\\Modules\\{$pair[0]}\\{$pair[1]}")
        ->values()
        ->all();

    arch("{$module} only depends on the public API of other modules")
        ->expect("App\\Modules\\{$module}")
        ->not->toUse($foreignInternals);
}
