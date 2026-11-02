<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\VentanasDeMatricula;
use App\Modules\Academico\Data\VentanaDeMatricula;
use App\Modules\Docencia\Contracts\ClasesPorConfirmarDelDelegado;
use App\Modules\Matricula\Actions\ResumirAvanceDelEstudiante;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Matricula\Support\PlazosDeLaSemana;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Tramites\Contracts\TramitesEnProceso;
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

        return view('matricula::livewire.resumen-del-estudiante', [
            'ficha' => $ficha,
            'avance' => $avance,
            'plazos' => PlazosDeLaSemana::de(CarbonImmutable::now('America/Lima'), $avance->grupoActual),
            'esDocente' => $this->esDocente,
            'clasificaciones' => $this->clasificacionesPendientes($ficha->personaId),
            'enProceso' => app(TramitesEnProceso::class)($ficha->personaId),
            'clasesPorConfirmar' => app(ClasesPorConfirmarDelDelegado::class)($ficha->personaId) ?? 0,
            'cierreDeMatricula' => collect(app(VentanasDeMatricula::class)())->first(fn (VentanaDeMatricula $ventana): bool => $ventana->abierta),
        ]);
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
