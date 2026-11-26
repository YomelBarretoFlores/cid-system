<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\TextosDeDocumentos;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\DatosDeLaAcreditacion;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Certificacion\Support\FilasDeLaConstancia;
use App\Modules\Certificacion\Support\HistorialDeNotas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Postgraduate students accredit a level for their degree in the real CILN format (detalle version 3), with its own yearly number.
final readonly class EmitirConstanciaDeAcreditacion
{
    public function __construct(private EmisionDeDocumento $emision, private FilasDeLaConstancia $filas, private TextosDeDocumentos $textosDeDocumentos) {}

    // $gradoOtorgado: the degree a trámite froze with its request; without it the FUT grades catalog derives it from the mention.
    public function __invoke(int $personaId, string $idioma, NivelCurricular $nivel, string $mencion, User $emisor, ?string $gradoOtorgado = null): Documento
    {
        EmisionDeDocumento::asegurarPermiso($emisor);
        $mencion = trim($mencion);
        throw_if($mencion === '' || mb_strlen($mencion) > 200, ReglaDeNegocioIncumplida::class, 'Indica la mención del grado de maestría o doctorado.');

        $historial = HistorialDeNotas::de($personaId, $idioma);
        $notas = FilasDeLaConstancia::aprobadasDelNivel($historial, $nivel);
        throw_if($notas === [], ReglaDeNegocioIncumplida::class, "La persona no tiene ciclos aprobados de {$idioma} en el nivel {$nivel->etiqueta()}.");

        return $this->emision->emitir(TipoDocumento::ConstanciaAcreditacion, $personaId, [
            'idioma' => $idioma,
            'nivel' => $nivel->value,
            'detalle' => [
                'version' => 3,
                ...$this->filas->datosDelEstudiante($personaId, $idioma, $historial),
                'mencion' => $mencion,
                'notas' => array_map(fn (NotaDelHistorial $nota): array => $nota->comoDetalle(), $notas),
                'filas' => FilasDeLaConstancia::filas($notas),
                'programa_curricular' => $this->filas->programaCurricular($idioma),
                ...DatosDeLaAcreditacion::de($notas, $mencion, ($this->textosDeDocumentos)(), $gradoOtorgado),
            ],
        ], $emisor);
    }
}
