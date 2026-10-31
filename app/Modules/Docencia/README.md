# Docencia

M3: padrón docente, carga académica sin cruces, asistencia en dos llamados con confirmación del delegado del curso (ADR 0009), pre-actas, control de clases para la coordinación y expediente de horas por tarifa (sin planilla ni retenciones), y la asistencia de cada matrícula para los reportes del estudiante.

Teacher screens live in `Filament/Docente/Pages` and belong to the teacher panel at `/docente` (`App\Providers\Filament\DocentePanelProvider`).

Public API for other modules: `Contracts/`, `Data/` (DTOs), `Events/` and `Enums/`. Everything else is internal. See `docs/arquitectura.md`.
