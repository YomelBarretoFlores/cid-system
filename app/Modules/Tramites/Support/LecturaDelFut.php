<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\NivelesConNotas;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CampoFut;
use App\Modules\Tramites\Models\TipoTramite;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Reads and validates the FUT fields a type asks, keeping the keys stored before (matricula_id, idioma, nivel, motivo).
final readonly class LecturaDelFut
{
    public const array GRADOS = ['maestria' => 'Maestría', 'doctorado' => 'Doctorado'];

    public function __construct(
        private MatriculasActivasDePersona $matriculasActivas,
        private NivelesConNotas $nivelesConNotas,
        private NivelesPorCertificar $nivelesPorCertificar,
        private CatalogoDeIdiomas $idiomas,
        private AccesoAlTipo $acceso,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function leer(TipoTramite $tipo, int $personaId, DatosDeSolicitud $datos): array
    {
        $guardados = [];

        foreach ($tipo->campos as $configurado) {
            $campo = CampoFut::tryFrom($configurado['clave']);

            if ($campo instanceof CampoFut) {
                $guardados = [...$guardados, ...$this->campo($campo, $configurado['obligatorio'], $tipo, $personaId, $datos)];
            }
        }

        return $guardados;
    }

    /**
     * @return array<string, int|string>
     */
    private function campo(CampoFut $campo, bool $obligatorio, TipoTramite $tipo, int $personaId, DatosDeSolicitud $datos): array
    {
        $validaNivel = $tipo->accion_final->validaIdiomaYNivel();

        return match ($campo) {
            CampoFut::Matricula => $this->matricula($personaId, $datos->matriculaId, $obligatorio),
            CampoFut::Idioma => $validaNivel ? $this->nivelValidado($tipo->accion_final, $personaId, $datos) : $this->idioma($datos->idioma, $obligatorio),
            CampoFut::Nivel => $validaNivel ? [] : $this->nivel($datos->nivel, $obligatorio),
            CampoFut::Programa => $this->programa($tipo, $personaId, $datos->programaId, $obligatorio),
            CampoFut::Mencion => $this->mencion($datos->mencionGrado, $datos->mencionTexto, $obligatorio),
            CampoFut::Modalidad => $this->texto('modalidad', $datos->modalidad, [3, 100], $obligatorio, 'Indica la modalidad, entre 3 y 100 caracteres.'),
            CampoFut::Exposicion => $this->texto('exposicion', $datos->exposicion, [10, 1000], $obligatorio, 'Escribe lo que expones, entre 10 y 1000 caracteres.'),
            CampoFut::Celular => $this->celular($datos->celular, $obligatorio),
            CampoFut::Motivo => $this->texto('motivo', $datos->motivo, [5, 500], $obligatorio, 'Indica el motivo, entre 5 y 500 caracteres.'),
        };
    }

    /**
     * @return array<string, int|string>
     */
    private function matricula(int $personaId, ?int $matriculaId, bool $obligatorio): array
    {
        if ($matriculaId === null && ! $obligatorio) {
            return [];
        }

        $activas = ($this->matriculasActivas)($personaId);

        throw_unless($matriculaId !== null && isset($activas[$matriculaId]), ReglaDeNegocioIncumplida::class, 'Elige una de tus matrículas activas.');

        return ['matricula_id' => $matriculaId, 'matricula' => $activas[$matriculaId]];
    }

    /**
     * @return array{idioma: string, nivel: string}
     */
    private function nivelValidado(AccionFinal $accion, int $personaId, DatosDeSolicitud $datos): array
    {
        [$disponibles, $mensaje] = $accion->pideNivelesConNotas()
            ? [($this->nivelesConNotas)($personaId), 'Aún no apruebas ningún ciclo de ese idioma y nivel.']
            : [($this->nivelesPorCertificar)($personaId), 'Aún no completas ese nivel o ya tienes su certificado vigente.'];
        $elegido = collect($disponibles)->first(fn (NivelPorCertificar $candidato): bool => $candidato->idioma === $datos->idioma && $candidato->nivel->value === $datos->nivel);

        throw_unless($elegido instanceof NivelPorCertificar, ReglaDeNegocioIncumplida::class, $mensaje);

        return ['idioma' => $elegido->idioma, 'nivel' => $elegido->nivel->value];
    }

    /**
     * @return array<string, string>
     */
    private function idioma(?string $idioma, bool $obligatorio): array
    {
        $elegido = trim((string) $idioma);

        if ($elegido === '' && ! $obligatorio) {
            return [];
        }

        $existe = collect(($this->idiomas)())->contains(fn (IdiomaDelCatalogo $candidato): bool => $candidato->activo && $candidato->nombre === $elegido);

        throw_unless($existe, ReglaDeNegocioIncumplida::class, 'Elige uno de los idiomas del CILN.');

        return ['idioma' => $elegido];
    }

    /**
     * @return array<string, string>
     */
    private function nivel(?string $nivel, bool $obligatorio): array
    {
        if (($nivel === null || $nivel === '') && ! $obligatorio) {
            return [];
        }

        $elegido = NivelCurricular::tryFrom((string) $nivel) ?? throw new ReglaDeNegocioIncumplida('Elige el nivel.');

        return ['nivel' => $elegido->value];
    }

    /**
     * @return array<string, int|string>
     */
    private function programa(TipoTramite $tipo, int $personaId, ?int $programaId, bool $obligatorio): array
    {
        if ($programaId === null && ! $obligatorio) {
            return [];
        }

        $disponibles = $this->acceso->programasDisponibles($tipo, $personaId);

        throw_unless($programaId !== null && isset($disponibles[$programaId]), ReglaDeNegocioIncumplida::class, 'Elige uno de tus programas.');

        return ['programa_id' => $programaId, 'programa' => $disponibles[$programaId]];
    }

    /**
     * @return array<string, string>
     */
    private function mencion(?string $grado, ?string $texto, bool $obligatorio): array
    {
        $especialidad = trim((string) $texto);

        if (($grado === null || $grado === '') && $especialidad === '' && ! $obligatorio) {
            return [];
        }

        $valida = array_key_exists((string) $grado, self::GRADOS) && mb_strlen($especialidad) <= 150;

        throw_unless($valida, ReglaDeNegocioIncumplida::class, 'Indica la mención: maestría o doctorado, y su especialidad en hasta 150 caracteres.');

        return $especialidad === '' ? ['mencion_grado' => (string) $grado] : ['mencion_grado' => (string) $grado, 'mencion' => $especialidad];
    }

    /**
     * @return array<string, string>
     */
    private function celular(?string $celular, bool $obligatorio): array
    {
        $numero = preg_replace('/\s+/', '', (string) $celular) ?? '';

        if ($numero === '' && ! $obligatorio) {
            return [];
        }

        throw_unless(preg_match('/^9\d{8}$/', $numero) === 1, ReglaDeNegocioIncumplida::class, 'Indica un celular de 9 dígitos que empiece con 9.');

        return ['celular' => $numero];
    }

    /**
     * @param  array{int, int}  $largo
     * @return array<string, string>
     */
    private function texto(string $clave, ?string $texto, array $largo, bool $obligatorio, string $mensaje): array
    {
        $limpio = trim((string) $texto);

        if ($limpio === '' && ! $obligatorio) {
            return [];
        }

        throw_unless(mb_strlen($limpio) >= $largo[0] && mb_strlen($limpio) <= $largo[1], ReglaDeNegocioIncumplida::class, $mensaje);

        return [$clave => $limpio];
    }
}
