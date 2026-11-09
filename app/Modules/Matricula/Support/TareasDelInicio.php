<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Data\VentanaDeMatricula;
use App\Modules\Matricula\Data\AvanceDelEstudiante;
use App\Modules\Matricula\Data\TareaDelInicio;
use App\Modules\Matricula\Enums\PasoDelEstudiante;
use App\Modules\Pagos\Data\PagoRechazado;
use App\Shared\Ui\Formato;

// The "Te toca a ti" and "Le toca al CILN" lists of the student home, in display order.
final readonly class TareasDelInicio
{
    /**
     * @param  list<array{string, string}>  $clasificaciones  language and last day of each placement exam still to sit
     */
    public function __construct(
        private AvanceDelEstudiante $avance,
        private bool $soloDocente,
        private int $observados,
        private array $clasificaciones,
        private int $clasesPorConfirmar,
        private int $enProceso,
        private ?VentanaDeMatricula $cierreDeMatricula,
    ) {}

    /**
     * @return list<TareaDelInicio>
     */
    public function delEstudiante(): array
    {
        return array_values(array_filter([
            $this->soloDocente ? new TareaDelInicio('Tus grupos están en Mis grupos.', 'Registra allí la asistencia y las notas parciales.', 'Ir a mis grupos', 'filament.docente.pages.mis-grupos') : null,
            $this->pagoRechazado(),
            $this->pagoPorRegistrar(),
            $this->grupoPorElegir(),
            $this->clasesPorConfirmar > 0 ? new TareaDelInicio($this->clasesPorConfirmar === 1 ? 'Tienes 1 clase por confirmar.' : "Tienes {$this->clasesPorConfirmar} clases por confirmar.", 'Como delegado, confirma que el docente dictó la clase antes de que venza el plazo.', 'Confirmar clases', 'estudiante.clases') : null,
            $this->observados > 0 ? new TareaDelInicio($this->observados === 1 ? 'Subsana tu trámite observado.' : "Subsana tus {$this->observados} trámites observados.", 'El CILN te pidió corregir o completar algo.', 'Ver mis trámites', 'estudiante.tramites') : null,
            ...$this->examenesDeClasificacion(),
        ]));
    }

    /**
     * @return list<TareaDelInicio>
     */
    public function delCiln(): array
    {
        return array_values(array_filter([
            $this->sinPago() && ! $this->avance->pagos->ultimoRechazo instanceof PagoRechazado && $this->avance->pagos->matriculaPorValidar
                ? new TareaDelInicio('Tu pago de matrícula está por validar.', 'Cuando el CILN lo valide podrás elegir tu grupo. Lleva tu boleta original al CILN.', 'Ver registro de pagos', 'estudiante.pagos', 'Por validar') : null,
            $this->enProceso > 0 ? new TareaDelInicio($this->enProceso === 1 ? 'Tu trámite está en revisión.' : "Tus {$this->enProceso} trámites están en revisión.", 'Te avisaremos por correo cuando avancen.', 'Ver mis trámites', 'estudiante.tramites', 'En revisión') : null,
        ]));
    }

    private function sinPago(): bool
    {
        return ! $this->soloDocente && $this->avance->paso === PasoDelEstudiante::SinPago;
    }

    private function pagoRechazado(): ?TareaDelInicio
    {
        $rechazo = $this->avance->pagos->ultimoRechazo;

        return $this->sinPago() && $rechazo instanceof PagoRechazado ? new TareaDelInicio('Tu último pago fue rechazado.', $rechazo->motivo, 'Registrar pago', 'estudiante.pagos') : null;
    }

    private function pagoPorRegistrar(): ?TareaDelInicio
    {
        return $this->sinPago() && ! $this->avance->pagos->ultimoRechazo instanceof PagoRechazado && ! $this->avance->pagos->matriculaPorValidar
            ? new TareaDelInicio('Registra tu pago de matrícula.', 'Sube el recibo en Registro de pagos; cuando el CILN lo valide podrás elegir tu grupo.', 'Registrar pago', 'estudiante.pagos') : null;
    }

    private function grupoPorElegir(): ?TareaDelInicio
    {
        if ($this->avance->paso !== PasoDelEstudiante::PagoValidado) {
            return null;
        }

        $cuando = $this->cierreDeMatricula instanceof VentanaDeMatricula
            ? "Elige tu grupo de {$this->cierreDeMatricula->periodo} hasta el ".Formato::diaDeMes($this->cierreDeMatricula->fin).'.'
            : 'Elige tu grupo mientras la matrícula esté abierta.';

        return new TareaDelInicio('Tu pago de matrícula ya fue validado.', $cuando, 'Elegir mi grupo', 'estudiante.matricula');
    }

    /**
     * @return list<TareaDelInicio>
     */
    private function examenesDeClasificacion(): array
    {
        if ($this->soloDocente) {
            return [];
        }

        return array_map(fn (array $clasificacion): TareaDelInicio => new TareaDelInicio("Rinde tu examen de clasificación de {$clasificacion[0]}.", "Acércate al CILN antes del {$clasificacion[1]}.", 'Ver clasificación', 'estudiante.clasificacion'), $this->clasificaciones);
    }
}
