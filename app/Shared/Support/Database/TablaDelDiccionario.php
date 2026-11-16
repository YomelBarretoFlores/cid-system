<?php

declare(strict_types=1);

namespace App\Shared\Support\Database;

use App\Shared\Support\Database\Catalogo\Columna;
use App\Shared\Support\Database\Catalogo\IndiceUnico;
use App\Shared\Support\Database\Catalogo\Restriccion;
use App\Shared\Support\Database\Catalogo\Tabla;
use App\Shared\Support\Database\Catalogo\Trigger;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class TablaDelDiccionario
{
    private const array TIPOS = [
        'character varying' => 'varchar',
        'timestamp(0) with time zone' => 'timestamptz',
        'timestamp with time zone' => 'timestamptz',
        'timestamp(0) without time zone' => 'timestamp',
        'time(0) without time zone' => 'time',
        'time without time zone' => 'time',
        'character' => 'char',
    ];

    /**
     * @param  Collection<int, Columna>  $columnas
     * @param  Collection<int, Restriccion>  $restricciones
     * @param  Collection<int, IndiceUnico>  $unicos
     * @param  Collection<int, Trigger>  $triggers
     */
    public function __invoke(string $numero, Tabla $tabla, Collection $columnas, Collection $restricciones, Collection $unicos, Collection $triggers): string
    {
        $valores = $this->valoresPermitidos($restricciones);
        $referencias = $this->referencias($restricciones);

        $filas = $columnas->map(fn (Columna $columna, int $indice): string => sprintf(
            '| %d | `%s` | %s | %s | %s | %s |',
            $indice + 1,
            $columna->nombre,
            strtr($columna->tipo, self::TIPOS),
            $columna->nulo ? 'Sí' : 'No',
            $this->defecto($columna),
            $this->celda($this->descripcion($columna, $referencias[$columna->nombre] ?? null, $valores[$columna->nombre] ?? [])),
        ));

        return implode("\n", [
            "### {$numero} `{$tabla->nombre}`",
            '',
            $this->celda($tabla->descripcion ?? ''),
            '',
            '| N° | Columna | Tipo | Nulo | Por defecto | Descripción |',
            '|---|---|---|---|---|---|',
            ...$filas->all(),
            '',
            ...$this->lista('Llave primaria y restricciones únicas', $this->restriccionesDe($restricciones, ['p', 'u'])),
            ...$this->lista('Índices únicos parciales', $unicos->map(fn (IndiceUnico $indice): string => "`{$indice->nombre}`: ".$this->codigo(Str::after($indice->definicion, ' USING btree ')))),
            ...$this->lista('Llaves foráneas', $this->restriccionesDe($restricciones, ['f'])),
            ...$this->lista('Reglas de validación', $this->restriccionesDe($restricciones, ['c', 'x'])),
            ...$this->lista('Triggers', $triggers->map(fn (Trigger $trigger): string => "`{$trigger->nombre}`: {$trigger->momento} {$trigger->eventos}, ejecuta `{$trigger->funcion}()`")),
        ]);
    }

    public static function columnasDe(string $definicion): string
    {
        return Str::betweenFirst($definicion, '(', ')');
    }

    private function celda(string $texto): string
    {
        return str_replace(['|', "\n"], ['\|', ' '], $texto);
    }

    /**
     * @param  Collection<int, Restriccion>  $restricciones
     * @param  list<string>  $tipos
     * @return list<string>
     */
    private function restriccionesDe(Collection $restricciones, array $tipos): array
    {
        return array_values($restricciones
            ->filter(fn (Restriccion $restriccion): bool => in_array($restriccion->tipo, $tipos, true))
            ->map(fn (Restriccion $restriccion): string => "`{$restriccion->nombre}`: ".$this->codigo($restriccion->definicion))
            ->all());
    }

    /**
     * @param  iterable<int, string>  $elementos
     * @return list<string>
     */
    private function lista(string $titulo, iterable $elementos): array
    {
        $vinetas = collect($elementos)->map(fn (string $elemento): string => "- {$elemento}")->values()->all();

        return $vinetas === [] ? [] : ["**{$titulo}:**", '', ...$vinetas, ''];
    }

    /**
     * @param  list<string>  $valores
     */
    private function descripcion(Columna $columna, ?string $referencia, array $valores): string
    {
        $partes = [$columna->descripcion ?? ''];

        if ($valores !== []) {
            $partes[] = 'Valores: '.implode(', ', array_map(fn (string $valor): string => "`{$valor}`", $valores)).'.';
        }

        if ($referencia !== null) {
            $partes[] = "Referencia a `{$referencia}`.";
        }

        return trim(implode(' ', $partes));
    }

    /**
     * @param  Collection<int, Restriccion>  $restricciones
     * @return array<string, string> single-column foreign keys by column
     */
    private function referencias(Collection $restricciones): array
    {
        return $restricciones
            ->filter(fn (Restriccion $llave): bool => $llave->tipo === 'f' && $llave->referencia !== null && preg_match('/^FOREIGN KEY \(\w+\)/', $llave->definicion) === 1)
            ->mapWithKeys(fn (Restriccion $llave): array => [self::columnasDe($llave->definicion) => (string) $llave->referencia])
            ->all();
    }

    /**
     * @param  Collection<int, Restriccion>  $restricciones
     * @return array<string, list<string>> the literal values a CHECK allows, by column
     */
    private function valoresPermitidos(Collection $restricciones): array
    {
        $valores = [];

        foreach ($restricciones->where('tipo', 'c') as $regla) {
            if (preg_match('/^CHECK \(+\(?(\w+)\)?(?:::[\w ]+)? = ANY \(+ARRAY\[([^\]]+)\]/', $regla->definicion, $partes) === 1) {
                preg_match_all("/'([^']*)'/", $partes[2], $literales);
                $valores[$partes[1]] = $literales[1];
            }
        }

        return $valores;
    }

    private function defecto(Columna $columna): string
    {
        if ($columna->generada) {
            return 'Calculada';
        }

        if ($columna->defecto === null) {
            return '';
        }

        return str_starts_with($columna->defecto, 'nextval(') ? 'Autoincremental' : $this->celda('`'.preg_replace('/::[\w ]+/', '', $columna->defecto).'`');
    }

    private function codigo(string $sql): string
    {
        $limpio = preg_replace('/::(?:text|character varying|numeric|smallint|bpchar|integer|date)(?:\[\])?/', '', $sql) ?? $sql;

        return '`'.str_replace('`', "'", $limpio).'`';
    }
}
