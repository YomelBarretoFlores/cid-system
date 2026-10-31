# CILN enrollment: next cycle after approval and visible requirements (E1)

Objective: passing a cycle in a closed acta enables the next one (TdR M2, anexo :53a), and the enroll page lists every requirement with its reason before the offer.
Branch: `feat/requisitos-matricula` (from `main` 98c39a2) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`). Plan: `~/.claude/plans/agile-orbiting-wreath.md` (E1).

## Specs

- S1 (anexo-tecnico-ciln.md:53): the system "impedirá la matrícula si el estudiante (a) no aprobó el ciclo anterior o no tiene el nivel requerido; (b) no cuenta con la validación de pago…; o (c) … sin vacantes". Today approving a cycle never enables the next one: only `RegistrarResultadoDeClasificacion` writes `mat_habilitaciones`.
- S2 (plan E1, habilitación): closing an acta (first close and after a rectification) enables the next cycle of the language for every student marked Aprobado, with origin `Aprobacion`. It only moves forward: a higher habilitation (for example from placement) is never lowered. The last cycle enables nothing. Idempotent.
- S3 (plan E1): command `matricula:recalcular-habilitaciones` applies S2 to every acta already closed. Idempotent.
- S4 (plan E1, assumption): a rectification from Aprobado to Desaprobado does not revert the habilitation (recorded in decisiones-pendientes).
- S5 (plan E1, requisitos): "Requisitos de matrícula checklist (TdR M2 'validación de prerrequisitos'): periodo abierto, ciclo que corresponde, pago validado disponible, grupos con vacantes, sin otra matrícula vigente en ese idioma; each item with its reason." The enrollment rule today is per cycle and period, so the checklist mirrors that rule and does not add a per-language rule. It also shows the expected condition (Regular or Repitente).
- S6 (plan E1): the checklist and `MatricularEstudiante` never contradict each other: every unmet item is a case the enrollment rejects.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1–S4 | inline | done | see L3 |
| T2 | S5, S6 | inline | done | see L5 |
| T3 | all | inline | pending | — |

T1 event `ActaCerrada` from `CerrarActa`, Matricula listener and Action `HabilitarCiclosSiguientes`, Academico contract for the groups' cycles, Certificacion contract for approved enrollments in closed actas, the command, demo, docs (Risk item 1). T2 Academico contract for enrollment windows, `EvaluarRequisitosDeMatricula`, checklist in `Matricularse`, docs. T3 browser check (desktop and phone). Route: inline (no writer trigger: T2 depends on T1's habilitation origin).

## Log

- L1 (2026-10-31): "Bien, arma ahora el plan. Una cosa más, revisa sobre el tema de asistencia también, te comento que en el sga el docente llama lista dos veces, al inicio de la clase y al final. Y al final el el estudiante delegado del curso ingresa sus credenciales o algo así para que se valide esa asistencia. Esto se hace para que se vea que el docente en verdad también está dictando clases. Y además, te comento que para el pago del docente se le resta si es que falta a clases, pero eso lo veremos más adelante /plan"
- L2 (2026-10-31): plan approved with five deliveries (E1–E5); the delegate confirms from their own phone. E1 forecast about 600 authored lines.
- L3 (2026-10-31): T1 done. RED: 3 of 5 tests failed (no habilitation after close or rectification; command missing). `CerrarActa::validar` is the only place an acta becomes Cerrada (first close and after a rectification), so it dispatches `ActaCerrada`; the next cycle comes from `CatalogoDeIdiomas` order plus a new Academico contract `CiclosDeGrupos`, and the backfill from a new Certificacion contract `MatriculasAprobadas`. Decision #97 records S4. `composer check` rc=0, 1563 tests.
- L4 (2026-10-31): independent verifier on T1 (b70b304): S1–S4 met, no blockers; 9 probes plus the Certificacion and Matricula suites passed at the baseline and the candidate. Its probes are committed as `HabilitacionPorAprobacionCasosTest` (ac082d0). Advisories: an inactive next cycle is still enabled (`CatalogoDeIdiomas` lists inactive cycles; product question); demo habilitations appear once the queue worker runs, since `CertificacionDemoSeeder` closes actas through `CerrarActa`.
- L5 (2026-10-31): T2 done. RED: 7 tests failed (Action and checklist missing). New Academico contract `VentanasDeMatricula`; the offer and the unused payments moved from `Matricularse` into `Matricula/Support/OpcionesDeMatricula`, shared by the page and `EvaluarRequisitosDeMatricula`. Deviation from the plan: instead of extracting the rules out of `MatricularEstudiante`, every unmet item is pinned by a test where `MatricularEstudiante` also refuses (S6), because the checklist reads the same offer and payments and only adds the cycle-and-period comparison. `composer check` rc=0, 1578 tests.
