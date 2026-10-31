<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Docencia\Support\MarcasDeAsistencia;
use App\Modules\Docencia\Support\VentanasDeLlamado;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final readonly class RegistrarLlamadoDeCierre
{
    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
    ) {}

    /**
     * @param  array<int, mixed>  $estados  state per enrollment id, only for the students present or late in the first call
     */
    public function __invoke(int $grupoId, int $docenteId, string $fecha, string $horaInicio, array $estados, User $usuario): ClaseDictada
    {
        $dia = ClasesDelHorario::fechaDeClase($fecha, $horaInicio);

        return Transaccion::ejecutar(function () use ($grupoId, $docenteId, $dia, $horaInicio, $estados, $usuario): ClaseDictada {
            $grupo = ($this->bloqueoDeGrupo)($grupoId);
            GrupoACargo::asegurar($grupo, $docenteId, 'la asistencia solo se registra');
            $sesion = ClasesDelHorario::sesionDelPeriodo($grupo, $dia, $horaInicio);
            $ahora = ClasesDelHorario::ahora();
            $this->asegurarVentana(VentanasDeLlamado::de($dia, $sesion), $dia, $ahora);
            $clase = $this->claseConLlamadoDeInicio($grupoId, $dia, $sesion);
            $presentes = $this->presentesAlInicio($clase, $dia);
            $marcas = MarcasDeAsistencia::validar($estados, array_map(intval(...), $presentes->keys()->all()), [EstadoAsistencia::Presente, EstadoAsistencia::Falta]);

            foreach ($presentes as $matriculaId => $asistencia) {
                $asistencia->update(['estado_cierre' => $marcas[$matriculaId]]);
            }

            $clase->fill(['llamado_cierre_en' => $clase->llamado_cierre_en ?? now(), 'confirmacion' => ConfirmacionDeClase::Pendiente, 'registrado_por' => $usuario->id])->save();

            return $clase;
        });
    }

    private function asegurarVentana(VentanasDeLlamado $ventanas, CarbonImmutable $dia, CarbonImmutable $ahora): void
    {
        if ($ahora->lt($ventanas->abreCierre())) {
            throw new ReglaDeNegocioIncumplida("El llamado de cierre de la clase del {$dia->format('d/m/Y')} se abre a las {$ventanas->abreCierre()->format('H:i')}.");
        }

        if ($ahora->gt($ventanas->cierraCierre())) {
            throw new ReglaDeNegocioIncumplida("El llamado de cierre de la clase del {$dia->format('d/m/Y')} cerró a las {$ventanas->cierraCierre()->format('H:i')}.");
        }
    }

    private function claseConLlamadoDeInicio(int $grupoId, CarbonImmutable $dia, SesionDeClase $sesion): ClaseDictada
    {
        $clase = ClaseDictada::query()->where(['grupo_id' => $grupoId, 'fecha' => $dia->toDateString(), 'hora_inicio' => $sesion->horaInicio])->lockForUpdate()->first();

        if ($clase?->registro_posterior === true) {
            throw new ReglaDeNegocioIncumplida('Esta clase se registró como registro posterior; no tiene llamado de cierre.');
        }

        if ($clase?->llamado_inicio_en === null) {
            throw new ReglaDeNegocioIncumplida('Primero toma el llamado de inicio de esta clase.');
        }

        if (! in_array($clase->confirmacion, [null, ConfirmacionDeClase::Pendiente], true)) {
            throw new ReglaDeNegocioIncumplida('El delegado ya revisó esta clase; el llamado de cierre ya no se puede cambiar.');
        }

        return $clase;
    }

    /**
     * Students present or late in the first call who are still enrolled, keyed by enrollment id.
     *
     * @return Collection<int, Asistencia>
     */
    private function presentesAlInicio(ClaseDictada $clase, CarbonImmutable $dia): Collection
    {
        $matriculas = array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->matriculaId, ClasesDelHorario::estudiantesDe(($this->estudiantesDelGrupo)($clase->grupo_id), $dia));

        return Asistencia::query()->where('clase_id', $clase->id)->where('estado', '!=', EstadoAsistencia::Falta)->whereIn('matricula_id', $matriculas)->get()->keyBy('matricula_id');
    }
}
