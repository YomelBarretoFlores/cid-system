<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Livewire;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\NivelesConNotas;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Tramites\Actions\ListarTramitesDeEstudiante;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CampoFut;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\GradoFut;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Support\AccesoAlTipo;
use App\Modules\Tramites\Support\AdjuntosDeSolicitud;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.portal')]
#[Title('Trámites')]
final class MisTramites extends Component
{
    use WithFileUploads;

    private const array CAMPOS = ['matricula', 'idioma', 'nivel', 'programa', 'mencionGrado', 'mencionTexto', 'modalidad', 'exposicion', 'celular', 'motivo', 'comentario', 'adjuntos'];

    #[Locked]
    public ?int $tipoElegido = null;

    public string $matricula = '';

    public string $idioma = '';

    // "idioma|nivel" when the final action validates the pair, otherwise just the level.
    public string $nivel = '';

    public string $programa = '';

    public string $mencionGrado = '';

    public string $mencionTexto = '';

    public string $modalidad = '';

    public string $exposicion = '';

    public string $celular = '';

    public string $motivo = '';

    public string $comentario = '';

    /** @var array<int, mixed> */
    public array $adjuntos = [];

    private ?int $personaId = null;

    public function elegir(int $tipoId): void
    {
        $this->resetErrorBag();
        $this->reset(self::CAMPOS);
        $tipo = TipoTramite::query()->with('programas')->whereKey($tipoId)->where('activo', true)->first();
        $this->tipoElegido = $tipo instanceof TipoTramite && app(AccesoAlTipo::class)->puedeSolicitar($tipo, $this->personaId()) ? $tipo->id : null;
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
        $this->reset([...self::CAMPOS, 'tipoElegido']);
    }

    public function solicitar(SolicitarTramite $solicitarTramite): void
    {
        $tipo = $this->tipoElegido === null ? null : TipoTramite::query()->find($this->tipoElegido);

        if (! $tipo instanceof TipoTramite) {
            return;
        }

        $this->validate(['adjuntos.*' => ['nullable', 'file', 'max:'.AdjuntosDeSolicitud::MAXIMO_KB]], ['adjuntos.*.max' => 'Cada adjunto debe pesar como máximo 5 MB.', 'adjuntos.*.file' => 'Adjunta un archivo válido.']);

        try {
            $tramite = $solicitarTramite($this->personaId(), $tipo->id, $this->datos($tipo->accion_final));
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
        $tipo = $this->tipoElegido === null ? null : TipoTramite::query()->with('programas')->find($this->tipoElegido);

        return view('tramites::livewire.mis-tramites', [
            'tramites' => app(ListarTramitesDeEstudiante::class)($this->personaId()),
            'tipos' => $this->tiposDisponibles(),
            'tipo' => $tipo,
            'campos' => $tipo instanceof TipoTramite ? $this->campos($tipo) : [],
            'grados' => GradoFut::query()->activos()->pluck('nombre', 'codigo')->all(),
        ]);
    }

    private function datos(AccionFinal $accion): DatosDeSolicitud
    {
        [$idioma, $nivel] = $accion->validaIdiomaYNivel() ? array_pad(explode('|', $this->nivel, 2), 2, null) : [$this->idioma, $this->nivel];

        return new DatosDeSolicitud(
            matriculaId: is_numeric($this->matricula) ? (int) $this->matricula : null,
            idioma: $idioma === '' ? null : $idioma,
            nivel: $nivel === '' ? null : $nivel,
            motivo: $this->motivo,
            comentario: $this->comentario,
            programaId: is_numeric($this->programa) ? (int) $this->programa : null,
            mencionGrado: $this->mencionGrado,
            mencionTexto: $this->mencionTexto,
            modalidad: $this->modalidad,
            exposicion: $this->exposicion,
            celular: $this->celular,
            adjuntos: array_filter($this->adjuntos, fn (mixed $archivo): bool => $archivo instanceof UploadedFile),
        );
    }

    /**
     * @return Collection<int, TipoTramite>
     */
    private function tiposDisponibles(): Collection
    {
        $acceso = app(AccesoAlTipo::class);

        return TipoTramite::query()->with('programas')->where('activo', true)->orderBy('nombre')->get()
            ->filter(fn (TipoTramite $tipo): bool => $acceso->puedeSolicitar($tipo, $this->personaId()))
            ->values();
    }

    /**
     * @return list<array{campo: CampoFut, obligatorio: bool, opciones: ?array<int|string, string>}>
     */
    private function campos(TipoTramite $tipo): array
    {
        $campos = [];

        foreach ($tipo->campos as $configurado) {
            $campo = CampoFut::tryFrom($configurado['clave']);

            if ($campo instanceof CampoFut && ($campo !== CampoFut::Nivel || ! $tipo->accion_final->validaIdiomaYNivel())) {
                $campos[] = ['campo' => $campo, 'obligatorio' => $configurado['obligatorio'], 'opciones' => $this->opciones($campo, $tipo)];
            }
        }

        return $campos;
    }

    /**
     * @return ?array<int|string, string>
     */
    private function opciones(CampoFut $campo, TipoTramite $tipo): ?array
    {
        return match ($campo) {
            CampoFut::Matricula => app(MatriculasActivasDePersona::class)($this->personaId()),
            CampoFut::Idioma => $tipo->accion_final->validaIdiomaYNivel() ? $this->nivelesValidados($tipo->accion_final) : $this->idiomas(),
            CampoFut::Nivel => array_column(array_map(fn (NivelCurricular $nivel): array => [$nivel->value, $nivel->etiqueta()], NivelCurricular::cases()), 1, 0),
            CampoFut::Programa => app(AccesoAlTipo::class)->programasDisponibles($tipo, $this->personaId()),
            default => null,
        };
    }

    /**
     * @return array<string, string> "idioma|nivel" => "Idioma · Nivel"
     */
    private function nivelesValidados(AccionFinal $accion): array
    {
        $niveles = $accion->pideNivelesConNotas() ? app(NivelesConNotas::class)($this->personaId()) : app(NivelesPorCertificar::class)($this->personaId());

        return collect($niveles)->mapWithKeys(fn (NivelPorCertificar $nivel): array => [$nivel->clave() => $nivel->etiqueta()])->all();
    }

    /**
     * @return array<string, string>
     */
    private function idiomas(): array
    {
        return collect(app(CatalogoDeIdiomas::class)())
            ->filter(fn (IdiomaDelCatalogo $idioma): bool => $idioma->activo)
            ->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->nombre => $idioma->nombre])
            ->all();
    }

    private function personaId(): int
    {
        return $this->personaId ??= app(FichaDelUsuario::class)((int) auth()->id())->personaId ?? abort(403);
    }
}
