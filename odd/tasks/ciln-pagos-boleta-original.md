# CILN payments: validation with the original voucher, anti-fraud checks and administrable payment channels

Objective: a payment is validated only with the original paper voucher in hand, the missing anti-fraud checks are added, and payment channels and fee sources become administrable catalogs. Delivery D2 of the approved plan.
Branch: `feat/pagos-boleta-original` (stacked on `feat/asistencia-tema`) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, L1): "Una salida podría ser que el estudiante siga llevando su voucher de pago al CILN y mediante eso se valide, para que no haya falsificaciones."
  - The student still uploads the image or PDF (TdR l.73).
  - A pending payment tells the student: "Acércate al CILN con tu boleta original para validarla".
  - The cashier validates only after confirming "Tengo la boleta original y coincide con el número, la fecha y el monto registrados". The system records who confirmed it and when.
  - The database refuses a validated payment without that confirmation. Payments validated before this change are marked as not requiring it, so no confirmation is invented for them.
- S2 (plan): amount against fee. When the amount differs from the fee in force applied to the payment, the cashier sees the difference and must write a justification to validate it.
- S3 (plan): a payment whose operation number already exists, not rejected, under another channel shows a warning to the cashier. It is not blocked (the per-channel uniqueness of decision #14 stays).
- S4 (plan): the receipt file is fingerprinted with sha256. Registering a file already used by a payment that is not rejected is refused with "Esta boleta ya fue registrada en otro pago."
- S5 (plan): whoever registered a payment cannot validate it. A student never validates, so this only bites staff registering on someone's behalf. It is controlled by `ciln.pagos.validacion_por_otra_persona` (default true).
- S6 (user, L1, "Como dije, administrable"): payment channels are a catalog with its own screen.
  - Seeded with the current five channels plus "Tesorería UNASAM".
  - Inactive channels are not offered.
  - A channel in use cannot be deleted.
  - Existing payments keep their channel.
  - The API keeps returning the channel code and label.
- S7 (user, L1): fee sources (TUPA, TUSNE) are a catalog with its own screen. Existing concepts keep their source. The public fee list and API keep their fields.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1, S2, S3, S5 | inline | done | see L3 |
| T2 | S4 | inline | done | see L5 |
| T3 | S6 | inline | done | see L6 |
| T4 | S7 | inline | done | see L8 |

## Log

- L1 (2026-10-31): "1. Una salida podría ser que el estudiante siga llevando su voucher de pago al CILN y mediante eso se valide, para que no haya falsificaciones. 3. Cubre los huecos. 6. Como dije, administrable, y cubre los huecos." Context: "Sobre la matrícula, muchos pagan mediante la tesorería de la UNASAM, otro desde pagalo.pe … qué pasa si digamos un alumno falsifica una boleta, o genera una imagen con inteligencia artificial."
- L2 (2026-11-01): plan approved.
  - Route: inline. No writer trigger: the units share `RegistrarPago`, `MisPagos`, the factory and `ciln_testing`.
  - Mapping came from one read-only explorer.
  - Constraints found:
    - `pag_controlar_cambios()` freezes every column once a payment leaves pendiente, so the T3 data migration disables that trigger around its own update.
    - The portal stores the student as `registrado_por`.
    - `FuenteConcepto` sits in the cached `TarifaVigente` and in `config/cache.php` `serializable_classes`.
- L3 (2026-11-01): T1 done.
  - Migration `2026_11_01_100000_require_the_original_voucher_to_validate_a_payment` adds:
    - `exige_boleta_original`: added with default false, so stored rows get it without an UPDATE the lifecycle trigger would refuse; then the default becomes true;
    - `boleta_original_verificada_por` and `_en`;
    - `justificacion_monto`;
    - CHECK `pag_pagos_boleta_original_check` and CHECK `pag_pagos_justificacion_monto_check`.
  - `ValidarPago(pagoId, validador, boletaOriginalVerificada, ?justificacionMonto)` checks, in order:
    - the transition;
    - the segregation rule (`ciln.pagos.validacion_por_otra_persona`);
    - the original voucher;
    - the justification when the amount differs from the fee.
  - `AlertasDePago` produces the amount and other-channel warnings, shown in the validate modal and in the "Revise antes de validar" section of the payment page.
  - The validate modal has the checkbox (accepted) and a justification textarea that appears only when the amounts differ.
  - The portal shows "Acércate al CILN con tu boleta original para validarla." on pending payments.
  - Demo seeders and the factory `validado()` state record the voucher check.
  - Docs: `modulos.md`, decisions #114 to #117.
  - RED: 11 new tests, all failing first.
  - GREEN: Pagos, Tramites, Feature, Matricula, Tablero, Certificacion and Concurrency, 1097 tests. Larastan, Rector and Pint are clean.
- L4 (2026-11-01): `review assess` of 13ba086..f668169 was escalated with item 2 (the validation guard). Native review (lenses risk, reliability, resilience; lineage review-2cedda8898261916) was granted by the user, approved and acknowledged.
  - Applied after RED tests:
    - the factory `validado()` state is lazy again: the voucher checker copies the validator and no extra user is created;
    - `ValidarPago` enforces the 500-character justification limit;
    - new tests prove that a rejected payment is left out of the other-channel warning and that the warning section is hidden without alerts.
  - Left for later: the alert queries run on every render, without memoising.
- L5 (2026-11-01): T2 done.
  - Migration `2026_11_01_100100_add_receipt_fingerprint_to_pag_pagos` adds `recibo_sha256` (CHECK on 64 hex characters) and the partial unique index `pag_pagos_recibo_sha256_unique`, which excludes rejected payments.
  - `RegistrarPago` hashes the upload. A duplicate is told apart from a repeated operation number through `RestriccionUnica`, and the stored file is deleted.
  - The concurrency fixture now writes unique receipt bytes per run, because payments survive between runs.
  - RED: 2 of 3 new tests failed first.
  - Pagos, Feature, Tramites and Concurrency: 651 tests pass.
- L6 (2026-11-01): T3 done.
  - Migration `2026_11_01_100200_turn_payment_channels_into_a_catalog`:
    - creates `pag_medios_pago` (unique code and name, CHECKs, audit trigger), seeded with six channels including Tesorería UNASAM;
    - fills `pag_pagos.medio_pago_id` from the old code, with the lifecycle trigger disabled only around that UPDATE;
    - rebuilds `pag_pagos_operacion_unique` on `(medio_pago_id, numero_operacion)` and drops the enum column;
    - its `down()` maps codes that did not exist before to `otro`.
  - Shared code reused later by D4 and T4:
    - `CatalogoAdministrable`: code from the name, `idDe`, scope `activos`;
    - `CatalogoEnElPanel`: form and table;
    - `CatalogoDePagosPolicy`: `pagos.configurar`, no delete.
  - `MedioDePagoResource` lives under Configuración.
  - Every use of the `MedioPago` enum moved to the relation. The API still returns `medio_pago` (the code) and `medio_pago_etiqueta`.
  - `RegistrarPago` refuses an inactive channel.
  - The receipt test helper gives each receipt name its own bytes.
  - Found while testing: running Concurrency before Modules leaves the last concurrency test's rows behind. That only comes from the order of an ad-hoc run; `composer check` runs Concurrency last.
  - RED: the new panel tests failed first (class missing).
  - Pagos, Feature, Tramites, Matricula, Certificacion, Tablero and Concurrency: 1112 tests pass. Pint, Larastan and Rector are clean.
- L7 (2026-11-01): native review of f668169..cf73d45 (escalated with item 1 because the migration rewrites the channel of stored payments; lenses risk, reliability, resilience; lineage review-9e3538b253aac491) was granted by the user.
  - One CRITICAL finding: the catalog had renamed the transfer channel from "Transferencia bancaria" to "Transferencia", silently changing the API, report and portal labels. Fixed in cf73d45 within the bounded correction; the targeted validation approved it and the review was acknowledged.
  - Then fixed a warning, after a RED test: a generated code now stays within 40 characters and takes a free suffix (`_2`…) when it collides.
  - Left for later: `down()` maps the new channels to `otro`, which could collide with an existing `(otro, número)` when rolling back.
- L8 (2026-11-01): T4 done.
  - Migration `2026_11_01_100300_turn_fee_sources_into_a_catalog` creates `pag_fuentes_concepto` (TUPA, TUSNE) and moves `pag_conceptos.fuente` to `fuente_id`.
  - `FuenteDeConceptoResource` lives under Configuración. The concept form offers the active sources plus the one the concept already has.
  - `TarifaVigente` now carries `fuente` (code) and `fuenteEtiqueta`, so the public API keeps its fields. `FuenteConcepto` left the `serializable_classes` allowlist.
  - The home cache key moved to `portada-publica:v2`, so a payload cached by the previous release is never unserialised.
  - `CreateConcepto` wraps creation in a savepoint, so a code taken at the same time stays a form error inside an outer transaction.
  - RED: 5 new tests failed first.
  - Pagos, Feature, Tramites, Unit, Matricula, Certificacion, Tablero and Concurrency: 1157 tests pass. Pint, Larastan and Rector are clean.
- L9 (2026-11-01): checks.
  - `composer check` rc=0, 1847 tests. The architecture rule then required `CreateConcepto` to use `Transaccion::ejecutar(..., reintentar: false)` (ec3afac).
  - Demo data: seeded `main` into `ciln_preview` and migrated with this branch. Payments by channel and state, with their totals, and the source of every concept are identical before and after. All 112 stored payments read `exige_boleta_original = false`, so no voucher check was invented.
  - Browser:
    - the cashier opens a pending payment;
    - the modal says "Valide solo con la boleta original en la mano." and refuses an unchecked box;
    - checked, it validates and the page shows "Boleta original verificada por";
    - the cashier gets 403 on Medios de pago; admin sees the six channels with "Transferencia bancaria";
    - the student page has no overflow at 390 px and no JS errors.
  - The portal heading now asks the student to bring the original voucher.

