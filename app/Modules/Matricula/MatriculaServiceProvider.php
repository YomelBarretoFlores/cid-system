<?php

declare(strict_types=1);

namespace App\Modules\Matricula;

use App\Modules\Certificacion\Events\ActaCerrada;
use App\Modules\Matricula\Actions\BuscarDocumentoDeMatricula;
use App\Modules\Matricula\Actions\DescribirContextoAcademicoDePagos;
use App\Modules\Matricula\Actions\ListarEstudiantesDelGrupo;
use App\Modules\Matricula\Actions\ListarMatriculasActivasDePersona;
use App\Modules\Matricula\Actions\ListarProgramasDeLaPersona;
use App\Modules\Matricula\Actions\MoverMatriculaPorTramite;
use App\Modules\Matricula\Actions\RegistrarHabilitacionPorTramite;
use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Matricula\Contracts\DocumentoDeMatriculaPorId;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Contracts\FinDeLaUltimaMatricula;
use App\Modules\Matricula\Contracts\HabilitacionPorTramite;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Matricula\Contracts\MovimientoPorTramite;
use App\Modules\Matricula\Contracts\ProgramasDeLaPersona;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Personas\MatriculasDeLaPersona;
use App\Modules\Matricula\Filament\Resources\Matriculas\Actions\AccionesDeMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\Schemas\MatriculaEnVentanillaForm;
use App\Modules\Matricula\Listeners\AlAnularPago;
use App\Modules\Matricula\Listeners\AlCerrarActa;
use App\Modules\Matricula\Livewire\ResumenDelEstudiante;
use App\Modules\Matricula\Support\UltimaMatriculaDelIdioma;
use App\Modules\Pagos\Events\PagoAnulado;
use App\Modules\Personas\Contracts\FormularioDePersona;
use App\Shared\Support\Filament\FichaDePersonaEnElPanel;
use App\Shared\Support\ModuleServiceProvider;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

final class MatriculaServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ContextoAcademicoDePagos::class => DescribirContextoAcademicoDePagos::class,
        EstudiantesDelGrupo::class => ListarEstudiantesDelGrupo::class,
        MatriculasActivasDePersona::class => ListarMatriculasActivasDePersona::class,
        DocumentoDeMatriculaPorId::class => BuscarDocumentoDeMatricula::class,
        MovimientoPorTramite::class => MoverMatriculaPorTramite::class,
        ProgramasDeLaPersona::class => ListarProgramasDeLaPersona::class,
        HabilitacionPorTramite::class => RegistrarHabilitacionPorTramite::class,
        FinDeLaUltimaMatricula::class => UltimaMatriculaDelIdioma::class,
    ];

    #[\Override]
    protected string $module = 'Matricula';

    public function boot(): void
    {
        parent::boot();

        $ficha = app(FichaDePersonaEnElPanel::class);
        $ficha->pestana('matriculas', 'Matrículas', MatriculasDeLaPersona::class, PermisoMatricula::Ver->value, 10);
        $ficha->atajo('matricular', fn (int $personaId): Action => AccionesDeMatricula::matricularEnVentanilla()
            ->name('matricular')
            ->label('Matricular')
            ->fillForm(fn (): array => ['persona' => app(FormularioDePersona::class)->estadoDe($personaId), 'persona_id' => $personaId, 'modo_de_pago' => MatriculaEnVentanillaForm::PAGO_VALIDADO]), 10);

        Livewire::component('matricula.resumen-del-estudiante', ResumenDelEstudiante::class);
        Event::listen(ActaCerrada::class, AlCerrarActa::class);
        Event::listen(PagoAnulado::class, AlAnularPago::class);
    }
}
