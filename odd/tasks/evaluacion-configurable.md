# Configurable evaluation system: columns, calculations and one or two cycle grades

Objective: CILN defines, without code changes, the grade columns teachers enter, how they are combined (simple or weighted average, rounding), and which column is the grade of each cycle (two for accelerated groups), scoped by language, level, program and regime. Branch `feat/evaluacion-configurable`. Delivery: one commit per task (`ask-on-risk`). Runner: Pest on PostgreSQL 18 (`php artisan test --compact`, `composer check`).

## Specs

- S1. Accelerated groups can have a different grade per cycle. Evidence: real constancia «BASICO - NIVEL ACELERADO», «III 16 (DIECISEIS) JULIO 2026», «IV 15 (QUINCE) JULIO 2026». User: "eso mismo noté que las notas no son las mismas, entonces en un mes seguramente sacan dos notas para dos ciclos. Sinceramente no sé el cómo manejan esa evaluación."
- S2. Columns and calculations are configured: "Que se definan sus columnas, quizás en un futuro tengan pesos, qué columnas o qué notas se promedian para sacar una nota, etc." Teacher-entered columns and calculated columns (simple or weighted average of chosen columns, rounding); the scheme names the column that is the grade of each cycle (one for regular, two for accelerated). Also for regular cycles: "creo que sería conveniente, incluso para el ciclo regular."
- S3. Teacher and student panels follow the scheme: "Y cuando se edite se cambie también en el panel de docente y estudiante (solo si se requiere)". Agreed rule: a scheme with recorded grades is not modified; a new version is created and closed actas keep their grades.
- S4. Scope by modality: "Incluso puede haber dependiendo de la modalidad, acelerado, regular, jóvenes/adultos, niños, etc." The most specific scheme in force wins (language, level, program, regime).
- S5. The constancia of an accelerated level shows each cycle with its own grade and month, as in the real document.

## Tasks

- [ ] T1 · S2, S3, S4 · Columns (entered/calculated, simple/weighted, rounding, cycle grade), calculation, scope by program and regime, admin form, and every grade view (teacher, acta, student) rendering the scheme columns · inline (one behavior: removing the fixed groups touches every view)
- [ ] T2 · S1, S3 · Acta stores one grade per cycle; accelerated actas carry two; history and progression use them · inline
- [ ] T3 · S5 · Constancia and record show each accelerated cycle with its own grade · inline
- [ ] T4 · all · Manuals, data dictionary, pending decisions, demo seed · inline

## Log

- L1 (2026-11-27) Original request: "Bien, sí, eso mismo noté que las notas no son las mismas, entonces en un mes seguramente sacan dos notas para dos ciclos. Sinceramente no sé el cómo manejan esa evaluación. Pero todo eso se podría resolver con la configuración del sistema de evaluación? Y creo que sería conveniente, incluso para el ciclo regular. Que se definan sus columnas, quizás en un futuro tengan pesos, qué columnas o qué notas se promedian para sacar una nota, etc. Y cuando se edite se cambie también en el panel de docente y estudiante (solo si se requiere) Incluso puede haber dependiendo de la modalidad, acelerado, regular, jóvenes/adultos, niños, etc. O tú qué piensas?, si es un sí, cómo lo harías?, cómo quedaría?, cómo sería con un ejemplo?"
- L2 (2026-11-27) "Sí, arranca con el plan. Y hasta el momento no se cuenta con datos reales aún, estamos en proceso de prueba, el único dato real que quedará el la credencial del admin que ya está en prod." Consequence: migrations may reshape grade tables without preserving test grades; production keeps only the admin account.
- L3 Current state (2026-11-27): schemes per language/level with validity; components have a fixed group (Habilidades/Examenes); the formula (average of skills, average of exams, final = average of both, rounded) is in code (#56); one final grade per acta, copied to both cycles of an accelerated acta.
- L4 T1 (2026-11-27) Design: `aca_componentes_evaluacion` gets tipo (ingresada/calculada), calculo (promedio/ponderado), redondeo (ninguno/entero), fuentes jsonb [{codigo, peso}] of earlier columns, ciclo (1/2; 2 only in a scheme for the accelerated regime); `grupo` and its triggers are dropped; a deferred trigger requires an entered column and a cycle-1 column. Schemes get programa_id and regimen; lookup ranks regime, program, language, level. Existing schemes and closed actas are converted to the same CILN formula (two averages plus nota_final rounded), grades untouched. Calculation `NotasCalculadas` (exact fractions, half-up). Test-first was not observed for T1: the old tests were adapted after the code, then new rules were pinned (PromedioFinalTest, EsquemasDeEvaluacionTest, EsquemaDelGrupoTest, GruposDeComponentesTest, ActasConPesosTest, NotasParcialesTest). Browser: scheme list and form checked on localhost (cycle options follow the regime, source options list the table's columns).
