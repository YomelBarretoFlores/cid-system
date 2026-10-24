---
paths:
  - "app/Modules/*/Models/**"
  - "app/Modules/*/Database/**"
  - "app/Shared/**/Models/**"
  - "app/Shared/Audit/**"
  - "database/**"
---

# Models and database (PostgreSQL 18)

- Tables use the module prefix: `aca_`, `per_`, `mat_`, `pag_`, `doc_`, `cer_`, `tra_`. Module migrations, factories and seeders live in `app/Modules/<Modulo>/Database/{Migrations,Factories,Seeders}`.
- Real foreign keys between modules, but no `belongsTo` to another module's model; reference by ID and read through its contract.
- Put invariants in the database too: `UNIQUE` (for example payment method + operation number), `CHECK` (vacancies >= 0), `NOT NULL`.
- PostgreSQL has no unsigned integers and Laravel silently ignores `unsigned*`: use `smallInteger`/`integer` plus a `CHECK`. Name constraints `<table>_<rule>_check` and add them with `DB::statement` after `Schema::create`.
- Migrations use literal enum values, never PHP enum classes, so old migrations keep their meaning.
- Money: `decimal(12, 2)` or integer cents. Never float. Timestamps: `timestampsTz()` / `timestampTz()`.
- Time-varying prices (TUPA/TUSNE fees, teacher hourly cost) are rows with validity dates; transactions store the amount they applied.
- Index every foreign key and every column used in filters of reports.
- Models stay thin: relations, casts, scopes, explicit `$fillable`. No business logic.
- `encrypted` cast for sensitive fields that are never searched (bank account). Never encrypt DNI.
- Financial and academic records are voided with reason and user, never deleted. The audit table is insert-only (trigger + grants).
- Migrations are reversible. Seeders and factories use synthetic data only.
