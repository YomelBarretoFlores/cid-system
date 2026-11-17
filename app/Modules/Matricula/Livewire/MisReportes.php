<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Matricula\Actions\BuscarMatriculasDelPeriodo;
use App\Modules\Matricula\Actions\ListarPeriodosDelEstudiante;
use App\Modules\Matricula\Actions\ResumirReportesDelPeriodo;
use App\Modules\Matricula\Support\AccesosDeReportes;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Reportes')]
final class MisReportes extends Component
{
    public const array PESTANAS = ['todos' => 'Todos', 'avance' => 'Avance de notas', 'aprobado' => 'Avance por idioma', 'horario' => 'Horario'];

    #[Url]
    public ?int $periodo = null;

    #[Url(as: 'ver', except: 'todos')]
    public string $pestana = 'todos';

    public function render(): View
    {
        $userId = (int) auth()->id();
        $ficha = app(FichaDelUsuario::class)($userId) ?? abort(403);
        $periodos = app(ListarPeriodosDelEstudiante::class)($ficha->personaId);

        // Only the student's own periods are reachable; anything else falls back to the newest one.
        if (! array_key_exists((int) $this->periodo, $periodos)) {
            $this->periodo = array_key_first($periodos);
        }

        if (! array_key_exists($this->pestana, self::PESTANAS)) {
            $this->pestana = 'todos';
        }

        $reportes = $this->periodo === null ? null : app(ResumirReportesDelPeriodo::class)(app(BuscarMatriculasDelPeriodo::class)($this->periodo, $userId));
        $records = app(RecordDeNotas::class)($ficha->personaId);

        return view('matricula::livewire.mis-reportes', [
            'ficha' => $ficha,
            'esDocente' => app(DocenteDelUsuario::class)($userId) !== null,
            'periodos' => $periodos,
            'reportes' => $reportes,
            'records' => $records,
            'columnas' => new AccesosDeReportes($reportes, $records !== [])->columnas(),
        ]);
    }
}
