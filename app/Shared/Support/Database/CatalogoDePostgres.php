<?php

declare(strict_types=1);

namespace App\Shared\Support\Database;

use App\Shared\Support\Database\Catalogo\Columna;
use App\Shared\Support\Database\Catalogo\IndiceUnico;
use App\Shared\Support\Database\Catalogo\Restriccion;
use App\Shared\Support\Database\Catalogo\Tabla;
use App\Shared\Support\Database\Catalogo\Trigger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

// Reads the public schema straight from the PostgreSQL catalog, so the data dictionary never drifts from the real database.
final class CatalogoDePostgres
{
    /**
     * @return Collection<int, Tabla>
     */
    public function tablas(): Collection
    {
        return $this->filas(<<<'SQL'
            SELECT c.relname AS nombre, obj_description(c.oid, 'pg_class') AS descripcion
            FROM pg_class c
            WHERE c.relnamespace = 'public'::regnamespace AND c.relkind IN ('r', 'p')
            ORDER BY c.relname
            SQL)->map(fn (stdClass $fila): Tabla => new Tabla((string) $fila->nombre, $this->opcional($fila->descripcion)));
    }

    /**
     * @return Collection<int, Columna>
     */
    public function columnas(): Collection
    {
        return $this->filas(<<<'SQL'
            SELECT c.relname AS tabla, a.attname AS nombre, format_type(a.atttypid, a.atttypmod) AS tipo,
                NOT a.attnotnull AS nulo, pg_get_expr(d.adbin, d.adrelid) AS defecto, a.attgenerated <> '' AS generada,
                col_description(c.oid, a.attnum) AS descripcion
            FROM pg_attribute a
            JOIN pg_class c ON c.oid = a.attrelid
            LEFT JOIN pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum
            WHERE c.relnamespace = 'public'::regnamespace AND c.relkind IN ('r', 'p') AND a.attnum > 0 AND NOT a.attisdropped
            ORDER BY c.relname, a.attnum
            SQL)->map(fn (stdClass $fila): Columna => new Columna(
            (string) $fila->tabla,
            (string) $fila->nombre,
            (string) $fila->tipo,
            (bool) $fila->nulo,
            $this->opcional($fila->defecto),
            (bool) $fila->generada,
            $this->opcional($fila->descripcion),
        ));
    }

    /**
     * @return Collection<int, Restriccion>
     */
    public function restricciones(): Collection
    {
        return $this->filas(<<<'SQL'
            SELECT conrelid::regclass::text AS tabla, conname AS nombre, contype::text AS tipo,
                pg_get_constraintdef(oid) AS definicion, NULLIF(confrelid, 0)::regclass::text AS referencia
            FROM pg_constraint
            WHERE connamespace = 'public'::regnamespace AND contype IN ('p', 'f', 'u', 'c', 'x')
            ORDER BY conrelid::regclass::text, contype, conname
            SQL)->map(fn (stdClass $fila): Restriccion => new Restriccion(
            (string) $fila->tabla,
            (string) $fila->nombre,
            (string) $fila->tipo,
            (string) $fila->definicion,
            $this->opcional($fila->referencia),
        ));
    }

    /**
     * Unique indexes that are not table constraints, such as the partial ones.
     *
     * @return Collection<int, IndiceUnico>
     */
    public function indicesUnicos(): Collection
    {
        return $this->filas(<<<'SQL'
            SELECT t.relname AS tabla, i.relname AS nombre, pg_get_indexdef(i.oid) AS definicion
            FROM pg_index x
            JOIN pg_class i ON i.oid = x.indexrelid
            JOIN pg_class t ON t.oid = x.indrelid
            WHERE t.relnamespace = 'public'::regnamespace AND x.indisunique
                AND NOT EXISTS (SELECT 1 FROM pg_constraint k WHERE k.conindid = i.oid)
            ORDER BY t.relname, i.relname
            SQL)->map(fn (stdClass $fila): IndiceUnico => new IndiceUnico((string) $fila->tabla, (string) $fila->nombre, (string) $fila->definicion));
    }

    /**
     * @return Collection<int, Trigger>
     */
    public function triggers(): Collection
    {
        return $this->filas(<<<'SQL'
            SELECT c.relname AS tabla, t.tgname AS nombre,
                substring(pg_get_triggerdef(t.oid) FROM ' (BEFORE|AFTER|INSTEAD OF) ') AS momento,
                replace(substring(pg_get_triggerdef(t.oid) FROM ' (?:BEFORE|AFTER|INSTEAD OF) (.+?) ON '), ' OR ', ', ') AS eventos,
                p.proname AS funcion
            FROM pg_trigger t
            JOIN pg_class c ON c.oid = t.tgrelid
            JOIN pg_proc p ON p.oid = t.tgfoid
            WHERE c.relnamespace = 'public'::regnamespace AND NOT t.tgisinternal
            ORDER BY c.relname, t.tgname
            SQL)->map(fn (stdClass $fila): Trigger => new Trigger(
            (string) $fila->tabla,
            (string) $fila->nombre,
            (string) $fila->momento,
            (string) $fila->eventos,
            (string) $fila->funcion,
        ));
    }

    public function version(): string
    {
        return (string) DB::scalar("SELECT current_setting('server_version')");
    }

    public function ultimaMigracion(): string
    {
        return (string) DB::table('migrations')->orderByDesc('id')->value('migration');
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function filas(string $sql): Collection
    {
        return collect(DB::select($sql));
    }

    private function opcional(mixed $valor): ?string
    {
        return $valor === null ? null : (string) $valor;
    }
}
