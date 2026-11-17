<?php

declare(strict_types=1);

namespace App\Shared\Support\Database;

use App\Shared\Support\Database\Catalogo\Restriccion;
use App\Shared\Support\Database\Catalogo\Tabla;
use App\Shared\Support\Database\Catalogo\Trigger;
use Illuminate\Support\Collection;

// Renders the data dictionary as Markdown from the catalog comments; no date inside, so regenerating an unchanged schema yields no diff.
final readonly class DiccionarioDeDatos
{
    private const array MODULOS = [
        'aca_' => 'Académico (oferta académica)',
        'per_' => 'Personas',
        'mat_' => 'Matrícula',
        'pag_' => 'Pagos',
        'doc_' => 'Docencia',
        'cer_' => 'Certificación',
        'tra_' => 'Trámites',
        'aud_' => 'Auditoría',
    ];

    private const string SEGURIDAD = 'Seguridad y control de acceso';

    private const string INFRAESTRUCTURA = 'Infraestructura de la aplicación';

    private const array TABLAS_DE_SEGURIDAD = [
        'users', 'password_reset_tokens', 'sessions', 'personal_access_tokens',
        'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
    ];

    private const int PRIMER_MODULO = 3;

    public function __construct(private CatalogoDePostgres $catalogo, private TablaDelDiccionario $tabla) {}

    public function __invoke(): string
    {
        $grupos = $this->modulos();
        $restricciones = $this->catalogo->restricciones();

        $secciones = $grupos->keys()->values()->map(fn (string $modulo, int $indice): string => $this->modulo(
            self::PRIMER_MODULO + $indice,
            $modulo,
            $grupos->get($modulo, collect()),
            $restricciones,
        ));

        return implode("\n", [
            $this->introduccion(),
            $this->resumen($grupos),
            ...$secciones->all(),
            $this->funciones(self::PRIMER_MODULO + $grupos->count()),
        ]);
    }

    /**
     * @return Collection<string, Collection<int, Tabla>>
     */
    private function modulos(): Collection
    {
        $orden = [...array_values(self::MODULOS), self::SEGURIDAD, self::INFRAESTRUCTURA];
        $tablas = $this->catalogo->tablas();

        return collect($orden)
            ->mapWithKeys(fn (string $modulo): array => [$modulo => $tablas->filter(fn (Tabla $tabla): bool => $this->moduloDe($tabla->nombre) === $modulo)->values()])
            ->filter(fn (Collection $tablasDelModulo): bool => $tablasDelModulo->isNotEmpty());
    }

    /**
     * @param  Collection<int, Tabla>  $tablas
     * @param  Collection<int, Restriccion>  $restricciones
     */
    private function modulo(int $numero, string $nombre, Collection $tablas, Collection $restricciones): string
    {
        $columnas = $this->catalogo->columnas();
        $unicos = $this->catalogo->indicesUnicos();
        $triggers = $this->catalogo->triggers();

        $detalle = $tablas->map(fn (Tabla $tabla, int $posicion): string => ($this->tabla)(
            sprintf('%d.%d.', $numero, $posicion + 1),
            $tabla,
            $columnas->where('tabla', $tabla->nombre)->values(),
            $restricciones->where('tabla', $tabla->nombre)->values(),
            $unicos->where('tabla', $tabla->nombre)->values(),
            $triggers->where('tabla', $tabla->nombre)->values(),
        ));

        return implode("\n", ["## {$numero}. {$nombre}", '', $this->diagrama($tablas->pluck('nombre'), $restricciones), ...$detalle->all()]);
    }

    /**
     * Relations of the module, leaving out the audit columns that point to users so the diagram stays legible.
     *
     * @param  Collection<int, string>  $tablas
     * @param  Collection<int, Restriccion>  $restricciones
     */
    private function diagrama(Collection $tablas, Collection $restricciones): string
    {
        $relaciones = $restricciones
            ->filter(fn (Restriccion $llave): bool => $llave->tipo === 'f' && $tablas->contains($llave->tabla) && ($llave->referencia !== 'users' || $tablas->contains('users')))
            ->map(fn (Restriccion $llave): string => sprintf('    %s ||--o{ %s : "%s"', $llave->referencia, $llave->tabla, TablaDelDiccionario::columnasDe($llave->definicion)))
            ->unique()
            ->values();

        if ($relaciones->isEmpty()) {
            return '';
        }

        return implode("\n", ['```mermaid', 'erDiagram', ...$relaciones->all(), '```', '']);
    }

    private function introduccion(): string
    {
        return <<<MD
            # Diccionario de datos

            ## 1. Introducción

            Este diccionario describe la base de datos del Sistema de Gestión Académica y Administrativa del Centro de Idiomas y Lenguas Nativas (CILN) de la UNASAM. Se genera directamente del catálogo de PostgreSQL con el comando `php artisan docs:diccionario-de-datos`, por lo que coincide con la base de datos instalada. Las descripciones de tablas y columnas están guardadas en la propia base como comentarios (`COMMENT ON`) y pueden consultarse con cualquier cliente de PostgreSQL.

            | Dato | Valor |
            |---|---|
            | Gestor de base de datos | PostgreSQL {$this->catalogo->version()} |
            | Esquema | `public` |
            | Última migración aplicada | `{$this->catalogo->ultimaMigracion()}` |

            **Convenciones:**

            - Cada módulo es dueño de sus tablas, identificadas por un prefijo: `aca_` Académico, `per_` Personas, `mat_` Matrícula, `pag_` Pagos, `doc_` Docencia, `cer_` Certificación, `tra_` Trámites y `aud_` Auditoría.
            - Las relaciones entre módulos son llaves foráneas reales de PostgreSQL. El diagrama de cada módulo omite las columnas que solo registran qué cuenta hizo una operación (por ejemplo, `validado_por`), que se detallan en cada tabla.
            - Los importes se guardan como `numeric` en soles; las fechas y horas, con zona horaria (`timestamptz`) y se muestran en hora de Lima.
            - Las reglas de negocio críticas se garantizan en la propia base: restricciones `CHECK`, `UNIQUE`, `EXCLUDE` y triggers.
            - Los registros financieros y académicos no se borran: se anulan con motivo y usuario. Los cambios de las tablas críticas quedan en la pista de auditoría inmutable `aud_registros`.
            - En la columna **Nulo**, "Sí" indica que el dato es opcional.

            MD;
    }

    /**
     * @param  Collection<string, Collection<int, Tabla>>  $grupos
     */
    private function resumen(Collection $grupos): string
    {
        $columnas = $this->catalogo->columnas()->countBy('tabla');

        $filas = $grupos->map(fn (Collection $tablas, string $modulo): string => sprintf(
            '| %s | %d | %d |',
            $modulo,
            $tablas->count(),
            $tablas->sum(fn (Tabla $tabla): int => $columnas->get($tabla->nombre, 0)),
        ));

        return implode("\n", [
            '## 2. Resumen por módulo',
            '',
            '| Módulo | Tablas | Columnas |',
            '|---|---|---|',
            ...$filas->values()->all(),
            sprintf('| **Total** | **%d** | **%d** |', $grupos->sum(fn (Collection $tablas): int => $tablas->count()), $columnas->sum()),
            '',
        ]);
    }

    private function funciones(int $numero): string
    {
        $usos = $this->catalogo->triggers()
            ->groupBy(fn (Trigger $trigger): string => $trigger->funcion)
            ->sortKeys()
            ->map(fn (Collection $triggers, string $funcion): string => sprintf(
                '| `%s()` | %s |',
                $funcion,
                $triggers->map(fn (Trigger $trigger): string => "`{$trigger->tabla}`")->unique()->implode(', '),
            ));

        return implode("\n", [
            "## {$numero}. Funciones de los triggers",
            '',
            'Funciones de PostgreSQL que ejecutan los triggers descritos en cada tabla. `aud_registrar()` escribe la pista de auditoría; las demás protegen reglas de negocio (bloqueo de actas cerradas, ciclo de vida de los pagos, cruces de horario de aulas y docentes, habilidades y exámenes de cada sistema de evaluación).',
            '',
            '| Función | Tablas que la usan |',
            '|---|---|',
            ...$usos->values()->all(),
            '',
        ]);
    }

    private function moduloDe(string $tabla): string
    {
        foreach (self::MODULOS as $prefijo => $modulo) {
            if (str_starts_with($tabla, $prefijo)) {
                return $modulo;
            }
        }

        return in_array($tabla, self::TABLAS_DE_SEGURIDAD, true) ? self::SEGURIDAD : self::INFRAESTRUCTURA;
    }
}
