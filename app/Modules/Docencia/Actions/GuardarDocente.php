<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Docencia\Data\DatosDeDocente;
use App\Modules\Docencia\Data\IdiomaHabilitado;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ReglasDeCargaDocente;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class GuardarDocente
{
    private const array RESTRICCIONES = [
        'doc_docentes_persona_id_unique' => 'Esta persona ya está registrada como docente.',
        'doc_docentes_ruc_unique' => 'Ya existe un docente con el RUC %s.',
        'doc_docentes_correo_institucional_unique' => 'Ya existe un docente con el correo institucional %s.',
    ];

    public function __construct(
        private EtiquetasDePersonas $etiquetasDePersonas,
        private CatalogoDeIdiomas $catalogoDeIdiomas,
        private ReglasDeCargaDocente $reglas,
    ) {}

    public function __invoke(DatosDeDocente $datos, ?int $docenteId = null): Docente
    {
        $correo = mb_strtolower(trim($datos->correoInstitucional));
        $cuenta = (string) preg_replace('/\s+/', '', $datos->cuentaBancaria);
        $this->asegurarPerfil($datos, $correo, $cuenta);
        $this->asegurarIdiomas($datos->idiomas);

        try {
            return Transaccion::ejecutar(fn (): Docente => $this->guardar($datos, $correo, $cuenta, $docenteId));
        } catch (UniqueConstraintViolationException $error) {
            throw $this->traducir($error, $datos, $correo);
        }
    }

    private function guardar(DatosDeDocente $datos, string $correo, string $cuenta, ?int $docenteId): Docente
    {
        $docente = $docenteId === null ? new Docente : Docente::query()->whereKey($docenteId)->lockForUpdate()->firstOrFail();

        if (! $docente->exists) {
            $this->asegurarPersona($datos->personaId);
            $docente->fill(['persona_id' => $datos->personaId]);
        }

        $docente->fill([
            'correo_institucional' => $correo,
            'ruc' => $datos->ruc,
            'banco' => trim($datos->banco),
            'cuenta_bancaria' => $cuenta,
            'condicion_laboral' => $datos->condicion,
        ])->save();

        $docente->idiomas()->whereNotIn('idioma_id', array_map(fn (IdiomaHabilitado $idioma): int => $idioma->idiomaId, $datos->idiomas))->delete();

        foreach ($datos->idiomas as $idioma) {
            $docente->idiomas()->updateOrCreate(['idioma_id' => $idioma->idiomaId], ['nivel_maximo' => $idioma->nivelMaximo]);
        }

        $this->reglas->asegurarCargaVigente($docente);

        // The code is a generated column, so it exists only after reading the row back.
        return $docente->refresh();
    }

    private function asegurarPerfil(DatosDeDocente $datos, string $correo, string $cuenta): void
    {
        $reglas = [
            'Escribe un correo institucional válido.' => filter_var($correo, FILTER_VALIDATE_EMAIL) !== false,
            'El RUC debe tener 11 dígitos.' => preg_match('/^\d{11}$/', $datos->ruc) === 1,
            'Indica el banco de la cuenta.' => trim($datos->banco) !== '' && mb_strlen(trim($datos->banco)) <= 80,
            'El número de cuenta solo admite dígitos y guiones, entre 6 y 30 caracteres.' => preg_match('/^[0-9-]{6,30}$/', $cuenta) === 1,
        ];

        foreach ($reglas as $mensaje => $cumple) {
            if (! $cumple) {
                throw new ReglaDeNegocioIncumplida($mensaje);
            }
        }
    }

    /**
     * @param  list<IdiomaHabilitado>  $idiomas
     */
    private function asegurarIdiomas(array $idiomas): void
    {
        if ($idiomas === []) {
            throw new ReglaDeNegocioIncumplida('Habilita al menos un idioma para dictar.');
        }

        $ids = array_map(fn (IdiomaHabilitado $idioma): int => $idioma->idiomaId, $idiomas);

        if (count(array_unique($ids)) !== count($ids)) {
            throw new ReglaDeNegocioIncumplida('Cada idioma se habilita una sola vez.');
        }

        $existentes = array_map(fn (IdiomaDelCatalogo $idioma): int => $idioma->id, ($this->catalogoDeIdiomas)());

        if (array_diff($ids, $existentes) !== []) {
            throw new ReglaDeNegocioIncumplida('El idioma elegido no existe.');
        }
    }

    private function asegurarPersona(int $personaId): void
    {
        if (! array_key_exists($personaId, ($this->etiquetasDePersonas)([$personaId]))) {
            throw new ReglaDeNegocioIncumplida('La persona elegida no existe.');
        }
    }

    private function traducir(UniqueConstraintViolationException $error, DatosDeDocente $datos, string $correo): \Throwable
    {
        foreach (self::RESTRICCIONES as $indice => $mensaje) {
            if (RestriccionUnica::es($error, $indice)) {
                return new ReglaDeNegocioIncumplida(sprintf($mensaje, str_contains($indice, 'ruc') ? $datos->ruc : $correo));
            }
        }

        return $error;
    }
}
