<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

it('describes every table and every column in the catalog, so a new migration cannot leave the data dictionary behind', function (): void {
    $sinDescripcion = collect(DB::select(<<<'SQL'
        SELECT c.relname AS tabla, NULL AS columna
        FROM pg_class c
        WHERE c.relnamespace = 'public'::regnamespace AND c.relkind IN ('r', 'p') AND obj_description(c.oid, 'pg_class') IS NULL
        UNION ALL
        SELECT c.relname, a.attname
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        WHERE c.relnamespace = 'public'::regnamespace AND c.relkind IN ('r', 'p') AND a.attnum > 0 AND NOT a.attisdropped
            AND col_description(c.oid, a.attnum) IS NULL
        SQL))->map(fn (object $falta): string => trim("{$falta->tabla}.{$falta->columna}", '.'));

    expect($sinDescripcion->all())->toBe([]);
});

it('writes the data dictionary with every module, the column descriptions, the allowed values, the references and the triggers', function (): void {
    $ruta = sys_get_temp_dir().'/diccionario-de-datos-'.uniqid().'.md';

    $this->artisan('docs:diccionario-de-datos', ['--ruta' => $ruta])->assertSuccessful();

    $diccionario = File::get($ruta);
    File::delete($ruta);

    expect($diccionario)
        ->toContain('# Diccionario de datos', '## 3. Académico (oferta académica)', '## 10. Auditoría', 'Seguridad y control de acceso')
        ->toContain('### 6.4. `pag_pagos`', 'Pagos con su comprobante y su validación.')
        ->toContain('| `estado` | varchar(255) | No | `\'pendiente\'` | Estado de validación del pago. Valores: `pendiente`, `validado`, `rechazado`, `anulado`. |')
        ->toContain('Persona que paga. Referencia a `per_personas`.')
        ->toContain('`pag_pagos_sin_borrado`: BEFORE DELETE, ejecuta `pag_impedir_borrado()`')
        ->toContain('| `aud_registrar()` |')
        ->toContain('| 1 | `id` | bigint | No | Autoincremental | Identificador interno (llave primaria). |');
});
