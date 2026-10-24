<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('indexes every foreign key column so joins and cascades never scan a whole table', function (): void {
    $sinIndice = collect(DB::select(<<<'SQL'
        SELECT c.conrelid::regclass::text AS tabla, a.attname AS columna
        FROM pg_constraint c
        JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
        WHERE c.contype = 'f'
          AND c.connamespace = 'public'::regnamespace
          AND NOT EXISTS (
              SELECT 1 FROM pg_index i
              WHERE i.indrelid = c.conrelid AND i.indkey[0] = c.conkey[1]
          )
        ORDER BY 1, 2
        SQL))->map(fn (object $fila): string => "{$fila->tabla}.{$fila->columna}")->all();

    expect($sinIndice)->toBe([]);
});
