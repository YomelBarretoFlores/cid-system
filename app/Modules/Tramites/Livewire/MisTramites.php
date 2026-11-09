<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Livewire;

use App\Modules\Certificacion\Contracts\IdiomasConNotasCerradas;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Tramites\Actions\ListarTramitesDeEstudiante;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\TipoTramite;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Trámites')]
final class MisTramites extends Component
{
    private const array CAMPOS = ['matricula', 'idioma', 'nivel', 'motivo', 'comentario'];

    #[Locked]
    public ?int $tipoElegido = null;

    public string $matricula = '';

    public string $idioma = '';

    public string $nivel = '';

    public string $motivo = '';

    public string $comentario = '';

    private ?int $personaId = null;

    public function elegir(int $tipoId): void
    {
        $this->resetErrorBag();
        $this->reset(self::CAMPOS);
        $this->tipoElegido = TipoTramite::query()->whereKey($tipoId)->where('activo', true)->value('id');
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
        $this->reset([...self::CAMPOS, 'tipoElegido']);
    }

    public function solicitar(SolicitarTramite $solicitarTramite): void
    {
        if ($this->tipoElegido === null) {
            return;
        }

        [$idiomaDelNivel, $nivel] = array_pad(explode('|', $this->nivel, 2), 2, null);

        try {
            $tramite = $solicitarTramite($this->personaId(), $this->tipoElegido, new DatosDeSolicitud(
                matriculaId: is_numeric($this->matricula) ? (int) $this->matricula : null,
                idioma: $this->nivel === '' ? $this->idioma : $idiomaDelNivel,
                nivel: $nivel,
                motivo: $this->motivo,
                comentario: $this->comentario,
            ));
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('solicitud', $error->getMessage());

            return;
        }

        session()->flash('estado', $tramite->estado === EstadoTramite::PendientePago
            ? "Registramos tu trámite {$tramite->codigo}. Paga la tasa y registra tu recibo en Registro de pagos: el trámite continúa cuando el CILN valide el pago."
            : "Registramos tu trámite {$tramite->codigo}. Te avisaremos por correo cada vez que cambie de estado.");

        $this->redirectRoute('estudiante.tramites');
    }

    public function render(): View
    {
        $tipo = $this->tipoElegido === null ? null : TipoTramite::query()->find($this->tipoElegido);

        return view('tramites::livewire.mis-tramites', [
            'tramites' => app(ListarTramitesDeEstudiante::class)($this->personaId()),
            'tipos' => TipoTramite::query()->where('activo', true)->orderBy('nombre')->get(),
            'tipo' => $tipo,
            'opciones' => $tipo instanceof TipoTramite ? $this->opciones($tipo->accion_final) : [],
        ]);
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    private function opciones(AccionFinal $accion): array
    {
        return array_filter([
            'matricula' => $accion->pideMatricula() ? app(MatriculasActivasDePersona::class)($this->personaId()) : null,
            'idioma' => $accion === AccionFinal::ConstanciaNotas ? $this->idiomasConNotas() : null,
            'nivel' => $accion === AccionFinal::CertificadoNivel
                ? collect(app(NivelesPorCertificar::class)($this->personaId()))->mapWithKeys(fn (NivelPorCertificar $nivel): array => [$nivel->clave() => $nivel->etiqueta()])->all()
                : null,
        ], fn (?array $opciones): bool => $opciones !== null);
    }

    /**
     * @return array<string, string>
     */
    private function idiomasConNotas(): array
    {
        $idiomas = app(IdiomasConNotasCerradas::class)($this->personaId());

        return array_combine($idiomas, $idiomas);
    }

    private function personaId(): int
    {
        return $this->personaId ??= app(FichaDelUsuario::class)((int) auth()->id())->personaId ?? abort(403);
    }
}
