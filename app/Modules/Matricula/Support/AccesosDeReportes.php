<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Matricula\Models\Matricula;

// The "Todos" page of the student reports: every report of a period in the three SGA columns.
final readonly class AccesosDeReportes
{
    private const string SIN_MATRICULA = 'Sin matrícula en este periodo';

    public function __construct(private ?ReportesDelPeriodo $reportes, private bool $hayRecord) {}

    /**
     * @return array<string, list<array{titulo: string, detalle: string, href: ?string, motivo: ?string, color: string, icono: string, pdf: bool}>>
     */
    public function columnas(): array
    {
        return [
            'Periodo seleccionado' => $this->delPeriodo(),
            'Generales e históricos' => $this->historicos(),
            'Seguimiento académico' => $this->seguimiento(),
        ];
    }

    /**
     * @return list<array{titulo: string, detalle: string, href: ?string, motivo: ?string, color: string, icono: string, pdf: bool}>
     */
    private function delPeriodo(): array
    {
        $reportes = $this->reportes;
        $unica = $reportes?->datos->matriculas->count() === 1 ? $reportes->datos->matriculas->first() : null;
        $varias = $reportes instanceof ReportesDelPeriodo ? "{$reportes->datos->matriculas->count()} cursos: ver en Mis matrículas" : null;

        return [
            $this->documentoDeMatricula('Ficha de matrícula', 'estudiante.matriculas.ficha', $unica, $varias, 'celeste', 'heroicon-o-identification'),
            $this->documentoDeMatricula('Constancia de inscripción', 'estudiante.matriculas.constancia', $unica, $varias, 'verde', 'heroicon-o-document-check'),
            $this->acceso('Horario personalizado', 'Ver cruces de horarios', $reportes instanceof ReportesDelPeriodo ? route('estudiante.reportes', ['ver' => 'horario', 'periodo' => $reportes->periodoId]) : null, $reportes instanceof ReportesDelPeriodo ? null : self::SIN_MATRICULA, 'lila', 'heroicon-o-calendar-days'),
            $this->acceso('Boleta de notas', 'Notas del acta final', $reportes instanceof ReportesDelPeriodo ? route('estudiante.reportes.boleta', $reportes->periodoId) : null, $reportes instanceof ReportesDelPeriodo ? null : self::SIN_MATRICULA, 'rojo', 'heroicon-o-clipboard-document-list', pdf: true),
        ];
    }

    /**
     * @return list<array{titulo: string, detalle: string, href: ?string, motivo: ?string, color: string, icono: string, pdf: bool}>
     */
    private function historicos(): array
    {
        return [
            $this->acceso('Récord de notas', 'Récord de todos tus idiomas', $this->hayRecord ? route('estudiante.reportes.record') : null, $this->hayRecord ? null : 'Aún no apruebas ningún ciclo', 'celeste', 'heroicon-o-academic-cap', pdf: true),
            $this->acceso('Historial de matrículas', 'Todas tus matrículas', route('estudiante.matriculas'), null, 'rojo', 'heroicon-o-rectangle-stack'),
            $this->acceso('Documentos emitidos', 'Constancias y certificados', route('estudiante.tramites'), null, 'verde', 'heroicon-o-document-text'),
        ];
    }

    /**
     * @return list<array{titulo: string, detalle: string, href: ?string, motivo: ?string, color: string, icono: string, pdf: bool}>
     */
    private function seguimiento(): array
    {
        $avance = $this->reportes instanceof ReportesDelPeriodo ? route('estudiante.reportes', ['ver' => 'avance', 'periodo' => $this->reportes->periodoId]) : null;
        $motivo = $this->reportes instanceof ReportesDelPeriodo ? null : self::SIN_MATRICULA;

        return [
            $this->acceso('Avance de notas', 'Notas registradas por el docente', $avance, $motivo, 'lila', 'heroicon-o-chart-bar'),
            $this->acceso('Asistencia', 'Clases y faltas', $avance, $motivo, 'ambar', 'heroicon-o-check-circle'),
            $this->acceso('Avance por idioma', 'Ciclos aprobados de 12', route('estudiante.reportes', ['ver' => 'aprobado']), null, 'celeste', 'heroicon-o-language'),
            $this->acceso('Registro de pagos', 'Recibos registrados', route('estudiante.pagos'), null, 'verde', 'heroicon-o-banknotes'),
        ];
    }

    /**
     * @return array{titulo: string, detalle: string, href: ?string, motivo: ?string, color: string, icono: string, pdf: bool}
     */
    private function documentoDeMatricula(string $titulo, string $ruta, ?Matricula $unica, ?string $varias, string $color, string $icono): array
    {
        if ($unica instanceof Matricula) {
            $grupo = $this->reportes?->datos->grupos[$unica->grupo_id] ?? null;

            return $this->acceso($titulo, $grupo ? "{$grupo->idioma} {$grupo->cicloNumero} · {$grupo->codigo}" : 'Del periodo', route($ruta, $unica), null, $color, $icono, pdf: true);
        }

        return $this->acceso($titulo, $varias ?? 'Del periodo', $varias === null ? null : route('estudiante.matriculas'), $varias === null ? self::SIN_MATRICULA : null, $color, $icono);
    }

    /**
     * @return array{titulo: string, detalle: string, href: ?string, motivo: ?string, color: string, icono: string, pdf: bool}
     */
    private function acceso(string $titulo, string $detalle, ?string $href, ?string $motivo, string $color, string $icono, bool $pdf = false): array
    {
        return ['titulo' => $titulo, 'detalle' => $detalle, 'href' => $href, 'motivo' => $motivo, 'color' => $color, 'icono' => $icono, 'pdf' => $pdf];
    }
}
