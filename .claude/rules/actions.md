---
paths:
  - "app/Modules/*/Actions/**"
  - "app/Modules/*/Listeners/**"
  - "app/Modules/*/Contracts/**"
  - "app/Modules/*/Data/**"
---

# Actions and module boundaries

- One business rule per Action: `final`, `__invoke`, constructor-injected dependencies. Name it as a verb in Spanish (`ValidarPago`, `MatricularEstudiante`).
- Wrap multi-step writes in `DB::transaction()`. Lock with `Model::whereKey($id)->lockForUpdate()->firstOrFail()` inside it; `$model->lockForUpdate()->refresh()` does not lock.
- Idempotent: check the current state before acting, so a retry or double click is harmless.
- Enforce state changes through the enum (`EstadoPago::Validado->puedeTransitarA(...)`), never by assigning raw values.
- Dispatch domain events with `ShouldDispatchAfterCommit`. Events carry IDs or DTOs, never Eloquent models.
- Cross-module reads go through the other module's `Contracts/` and return `Data/` DTOs (`final readonly`). Never import another module's Models, Actions or Http.
- Anything external (mail, PDF batches, future integrations) runs in a queued job with `timeout`, `backoff`, and `failed()`.
- Correlative numbers: counter row read with `FOR UPDATE` in the same transaction plus a `UNIQUE` constraint. Never `Cache::lock`.
- Each Action has a feature test in `tests/Modules/<Modulo>/` covering every rule it enforces.
