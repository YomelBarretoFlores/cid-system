<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

it('finds no record, instead of failing in the database, for a malformed key in the URL of any panel resource', function (): void {
    $fallidos = [];

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        foreach (['create', 'abc', '99999999999999999999999'] as $clave) {
            try {
                expect(DB::transaction(fn (): mixed => $resource::resolveRecordRouteBinding($clave)))->toBeNull();
            } catch (ModelNotFoundException) {
                // Answers 404 as well.
            } catch (Throwable $error) {
                $fallidos[] = class_basename($resource)." «{$clave}»: ".substr(strtok($error->getMessage(), "\n"), 0, 60);
            }
        }
    }

    expect($fallidos)->toBe([]);
});
