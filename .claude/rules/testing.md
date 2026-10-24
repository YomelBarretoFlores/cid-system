---
paths:
  - "tests/**"
  - "phpunit.xml"
---

# Tests (Pest 5)

- Every test runs on PostgreSQL (`ciln_testing`), never SQLite. Feature and module tests use `RefreshDatabase` via `tests/Pest.php`.
- Module tests live in `tests/Modules/<Modulo>/`; cross-cutting flows in `tests/Feature/`; boundary rules in `tests/Architecture/`.
- One test per business rule, asserting every observable effect: response, persisted data, dispatched events or notifications.
- Test through the public interface (HTTP, Livewire, Filament, or the Action), not private methods.
- Concurrency (last vacancy, double validation) needs two real connections or processes with `DatabaseTruncation`; a single `RefreshDatabase` transaction cannot prove locking.
- Audit tests prove that `UPDATE`/`DELETE` on the audit table fails and that query-builder updates are recorded.
- Factories and seeders produce synthetic data only. Never real personal data (Ley 29733).
- Keep scratch files outside the repository (`sys_get_temp_dir()`) and remove them in the test.
- When a change touches shared code, add one test proving the previous behavior still holds.
