<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Actas keep the columns they were graded with; this rewrites the stored description in the new shape without touching any grade.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE cer_actas DISABLE TRIGGER cer_actas_bloqueo;

            UPDATE cer_actas a SET componentes = s.nuevo
            FROM (
                SELECT a.id,
                    (
                        SELECT jsonb_agg((e - 'grupo') || '{"tipo": "ingresada", "calculo": null, "redondeo": "ninguno", "fuentes": [], "ciclo": null}'::jsonb ORDER BY o)
                        FROM jsonb_array_elements(a.componentes) WITH ORDINALITY x(e, o)
                    ) || jsonb_build_array(
                        jsonb_build_object('id', coalesce(ph.id, 0), 'codigo', 'promedio_habilidades', 'nombre', 'Promedio de habilidades', 'tipo', 'calculada',
                            'calculo', 'promedio', 'redondeo', 'ninguno', 'fuentes', coalesce(g.habilidades, '[]'::jsonb), 'ciclo', null),
                        jsonb_build_object('id', coalesce(pe.id, 0), 'codigo', 'promedio_examenes', 'nombre', 'Promedio de exámenes', 'tipo', 'calculada',
                            'calculo', 'promedio', 'redondeo', 'ninguno', 'fuentes', coalesce(g.examenes, '[]'::jsonb), 'ciclo', null),
                        jsonb_build_object('id', coalesce(nf.id, 0), 'codigo', 'nota_final', 'nombre', 'Nota final', 'tipo', 'calculada',
                            'calculo', 'promedio', 'redondeo', 'entero',
                            'fuentes', '[{"codigo": "promedio_habilidades", "peso": 1}, {"codigo": "promedio_examenes", "peso": 1}]'::jsonb, 'ciclo', 1)
                    ) AS nuevo
                FROM cer_actas a
                CROSS JOIN LATERAL (
                    SELECT
                        jsonb_agg(jsonb_build_object('codigo', e->>'codigo', 'peso', 1) ORDER BY o) FILTER (WHERE grupo = 'habilidades') AS habilidades,
                        jsonb_agg(jsonb_build_object('codigo', e->>'codigo', 'peso', 1) ORDER BY o) FILTER (WHERE grupo = 'examenes') AS examenes
                    FROM (
                        SELECT e, o, coalesce(e->>'grupo', CASE WHEN e->>'codigo' IN ('parcial', 'final') THEN 'examenes' ELSE 'habilidades' END) AS grupo
                        FROM jsonb_array_elements(a.componentes) WITH ORDINALITY x(e, o)
                    ) clasificados
                ) g
                LEFT JOIN aca_componentes_evaluacion ph ON ph.esquema_id = a.esquema_id AND ph.codigo = 'promedio_habilidades'
                LEFT JOIN aca_componentes_evaluacion pe ON pe.esquema_id = a.esquema_id AND pe.codigo = 'promedio_examenes'
                LEFT JOIN aca_componentes_evaluacion nf ON nf.esquema_id = a.esquema_id AND nf.codigo = 'nota_final'
                WHERE jsonb_array_length(a.componentes) > 0 AND NOT (a.componentes->0 ? 'tipo')
            ) s
            WHERE a.id = s.id;

            ALTER TABLE cer_actas ENABLE TRIGGER cer_actas_bloqueo;

            COMMENT ON COLUMN cer_actas.componentes IS 'Columnas del sistema de evaluación con que se calificó: [{id, codigo, nombre, tipo, calculo, redondeo, fuentes, ciclo}]; las notas ingresadas se guardan por id de columna.';
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE cer_actas DISABLE TRIGGER cer_actas_bloqueo;

            UPDATE cer_actas a SET componentes = (
                SELECT coalesce(jsonb_agg(jsonb_build_object(
                    'id', e->'id', 'codigo', e->'codigo', 'nombre', e->'nombre',
                    'grupo', CASE WHEN EXISTS (
                        SELECT 1 FROM jsonb_array_elements(a.componentes) p
                        WHERE p->>'codigo' = 'promedio_examenes' AND p->'fuentes' @> jsonb_build_array(jsonb_build_object('codigo', e->>'codigo'))
                    ) THEN 'examenes' ELSE 'habilidades' END
                ) ORDER BY o), '[]'::jsonb)
                FROM jsonb_array_elements(a.componentes) WITH ORDINALITY x(e, o)
                WHERE e->>'tipo' = 'ingresada'
            )
            WHERE jsonb_array_length(a.componentes) > 0 AND a.componentes->0 ? 'tipo';

            ALTER TABLE cer_actas ENABLE TRIGGER cer_actas_bloqueo;

            COMMENT ON COLUMN cer_actas.componentes IS 'Componentes aplicados con su grupo, habilidades o examenes (JSON).';
            SQL);
    }
};
