<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\VentanasDeMatricula;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Data\VentanaDeMatricula;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Docencia\Contracts\ClasesPorConfirmarDelDelegado;
use App\Modules\Matricula\Actions\ResumirAvanceDelEstudiante;
use App\Modules\Matricula\Data\AvanceDelEstudiante;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\PasoDelEstudiante;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Matricula\Support\PlazosDeLaSemana;
use App\Modules\Matricula\Support\TareasDelInicio;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Tramites\Contracts\TramitesEnProceso;
use App\Modules\Tramites\Contracts\TramitesObservados;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

// Embedded in the portal home: Personas shows it without depending on this module's classes.
final class ResumenDelEstudiante extends Component
{
    public bool $esDocente = false;

    public function render(): View
    {
        $ficha = app(FichaDelUsuario::class)((int) auth()->id()) ?? abort(403);

        $avance = app(ResumirAvanceDelEstudiante::class)($ficha->personaId);

        $soloDocente = $this->esDocente && $avance->idiomas === [] && $avance->paso === PasoDelEstudiante::SinPago;
        $tareas = new TareasDelInicio(
            avance: $avance,
            soloDocente: $soloDocente,
            observados: app(TramitesObservados::class)($ficha->personaId),
            clasificaciones: $this->clasificacionesPendientes($ficha->personaId),
            clasesPorConfirmar: app(ClasesPorConfirmarDelDelegado::class)($ficha->personaId) ?? 0,
            enProceso: app(TramitesEnProceso::class)($ficha->personaId),
            cierreDeMatricula: collect(app(VentanasDeMatricula::class)())->first(fn (VentanaDeMatricula $ventana): bool => $ventana->abierta),
        );

        return view('matricula::livewire.resumen-del-estudiante', [
            'ficha' => $ficha,
            'avance' => $avance,
            'soloDocente' => $soloDocente,
            'tareas' => $tareas->delEstudiante(),
            'esperas' => $tareas->delCiln(),
            'plazos' => PlazosDeLaSemana::de(CarbonImmutable::now('America/Lima'), $avance->grupoActual),
            'documentos' => $this->documentos($avance, app(RecordDeNotas::class)($ficha->personaId) !== []),
        ]);
    }

    /**
     * @return list<array{titulo: string, detalle: string, href: string, color: string, icono: string}> quick accesses to the documents the student already has
     */
    private function documentos(AvanceDelEstudiante $avance, bool $tieneRecord): array
    {
        $grupo = $avance->grupoActual;
        $matricula = $avance->matriculaActual;
        $documentos = [];

        if ($matricula !== null && $grupo instanceof FichaDeGrupo) {
            $documentos[] = ['titulo' => 'Ficha de matrícula', 'detalle' => "Periodo {$grupo->periodo}", 'href' => route('estudiante.matriculas.ficha', $matricula), 'color' => 'celeste', 'icono' => 'heroicon-o-identification'];
            $documentos[] = ['titulo' => 'Constancia de inscripción', 'detalle' => 'De tu matrícula vigente', 'href' => route('estudiante.matriculas.constancia', $matricula), 'color' => 'verde', 'icono' => 'heroicon-o-document-check'];
            $documentos[] = ['titulo' => 'Horario personalizado', 'detalle' => 'Tus clases de la semana', 'href' => route('estudiante.reportes.horario', $grupo->periodoId), 'color' => 'lila', 'icono' => 'heroicon-o-calendar-days'];
            $documentos[] = ['titulo' => 'Boleta de notas', 'detalle' => "Periodo {$grupo->periodo}", 'href' => route('estudiante.reportes.boleta', $grupo->periodoId), 'color' => 'rojo', 'icono' => 'heroicon-o-clipboard-document-list'];
            $documentos[] = ['titulo' => 'Asistencia', 'detalle' => 'Sesión por sesión', 'href' => route('estudiante.reportes.asistencia', $matricula), 'color' => 'ambar', 'icono' => 'heroicon-o-check-circle'];
        }

        if ($tieneRecord) {
            $documentos[] = ['titulo' => 'Récord de notas', 'detalle' => 'Todos tus idiomas', 'href' => route('estudiante.reportes.record'), 'color' => 'celeste', 'icono' => 'heroicon-o-academic-cap'];
        }

        return $documentos;
    }

    /**
     * @return list<array{string, string}> language and last day of each placement exam still to sit
     */
    private function clasificacionesPendientes(int $personaId): array
    {
        $idiomas = collect(app(CatalogoDeIdiomas::class)())->pluck('nombre', 'id');

        $pendientes = [];
        $solicitudes = SolicitudClasificacion::query()
            ->where(['persona_id' => $personaId, 'estado' => EstadoSolicitudClasificacion::PendienteEvaluacion])
            ->where('vence_en', '>=', CarbonImmutable::today('America/Lima')->toDateString())
            ->orderBy('vence_en')
            ->get();

        foreach ($solicitudes as $solicitud) {
            $pendientes[] = [(string) ($idiomas[$solicitud->idioma_id] ?? 'un idioma'), $solicitud->vence_en->format('d/m/Y')];
        }

        return $pendientes;
    }
}
