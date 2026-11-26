<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\ProgramaPorDefecto;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Personas\Contracts\FichaDePersona;
use App\Shared\Ui\Formato;

// What the grade record per level freezes besides the grades: the student's identity, program and curriculum, and the cycles per level.
final readonly class FilasDeLaConstancia
{
    public function __construct(
        private FichaDePersona $fichaDePersona,
        private DetalleDeGrupos $detalleDeGrupos,
        private ProgramaPorDefecto $programaPorDefecto,
        private CatalogoDeIdiomas $catalogoDeIdiomas,
        private CiclosDelNivelDelActa $ciclosDelNivel,
    ) {}

    /**
     * @param  list<NotaDelHistorial>  $historial  every result of the language, to find the program of its latest acta
     * @return array{tipo_documento: string, dni: string, programa: string, curriculo: string}
     */
    public function datosDelEstudiante(int $personaId, string $idioma, array $historial): array
    {
        [$tipo, $numero] = array_pad(explode(' ', ($this->fichaDePersona)($personaId)->documento, 2), 2, '');
        $curriculo = collect(($this->catalogoDeIdiomas)())->first(fn (IdiomaDelCatalogo $catalogo): bool => $catalogo->nombre === $idioma)?->curriculo;

        return ['tipo_documento' => $tipo, 'dni' => $numero, 'programa' => $this->programa($historial), 'curriculo' => $curriculo ?? '01'];
    }

    /**
     * @param  list<NotaDelHistorial>  $notas
     * @return list<array{item: int, nivel: string, ciclo: string, anio: ?int, mes: ?string, nota: string, forma: string}>
     */
    public static function filas(array $notas): array
    {
        return array_map(fn (int $indice, NotaDelHistorial $nota): array => [
            'item' => $indice + 1,
            'nivel' => $nota->nivel->etiqueta(),
            'ciclo' => Formato::romano($nota->cicloNumero),
            'anio' => $nota->anio,
            'mes' => $nota->mes === null ? null : Formato::mes($nota->mes),
            'nota' => $nota->promedio ?? '—',
            'forma' => mb_strtoupper($nota->forma->etiqueta()),
        ], array_keys($notas), $notas);
    }

    /**
     * @return array<string, int> cycles the language configures per level
     */
    public function programaCurricular(string $idioma): array
    {
        return collect(NivelCurricular::cases())->mapWithKeys(fn (NivelCurricular $nivel): array => [$nivel->value => count($this->ciclosDelNivel->de($idioma, $nivel))])->all();
    }

    /**
     * @param  list<NotaDelHistorial>  $historial
     */
    private function programa(array $historial): string
    {
        $actas = array_values(array_unique(array_filter(array_map(fn (NotaDelHistorial $nota): ?int => $nota->actaId, $historial))));
        $grupoId = $actas === [] ? null : Acta::query()->whereKey($actas)->orderByDesc('validada_en')->orderByDesc('id')->value('grupo_id');

        if ($grupoId === null) {
            return ($this->programaPorDefecto)();
        }

        $grupo = ($this->detalleDeGrupos)([(int) $grupoId])[(int) $grupoId] ?? null;

        return $grupo->programa ?? ($this->programaPorDefecto)();
    }
}
