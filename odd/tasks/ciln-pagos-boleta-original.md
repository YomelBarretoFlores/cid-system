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
| T2 | S4 | inline | pending | |
| T3 | S6 | inline | pending | |
| T4 | S7 | inline | pending | |

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

