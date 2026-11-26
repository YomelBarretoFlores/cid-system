<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\DocumentoParaTramite;
use App\Modules\Certificacion\Data\DocumentoEmitido;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class EmitirDocumentoParaTramite implements DocumentoParaTramite
{
    public function __construct(
        private EmitirConstanciaDeMatricula $constanciaDeMatricula,
        private EmitirConstanciaDeNotas $constanciaDeNotas,
        private EmitirCertificadoDeNivel $certificadoDeNivel,
    ) {}

    public function __invoke(TipoDocumento $tipo, int $personaId, array $datos, User $emisor): DocumentoEmitido
    {
        try {
            EmisionDeDocumento::asegurarPermiso($emisor);
        } catch (AuthorizationException) {
            throw new ReglaDeNegocioIncumplida('No tienes permiso para emitir constancias y certificados.');
        }

        $documento = match ($tipo) {
            TipoDocumento::ConstanciaMatricula => ($this->constanciaDeMatricula)($datos['matricula_id'] ?? 0, $emisor),
            TipoDocumento::ConstanciaNotas => ($this->constanciaDeNotas)($personaId, $datos['idioma'] ?? '', $this->nivel($datos), $emisor),
            TipoDocumento::CertificadoNivel => ($this->certificadoDeNivel)($personaId, $datos['idioma'] ?? '', $this->nivel($datos), $emisor),
        };

        throw_if($documento->persona_id !== $personaId, ReglaDeNegocioIncumplida::class, 'El documento no corresponde al estudiante del trámite.');

        return new DocumentoEmitido($documento->id, $documento->codigo);
    }

    /**
     * @param  array{nivel?: string}  $datos
     */
    private function nivel(array $datos): NivelCurricular
    {
        return NivelCurricular::tryFrom($datos['nivel'] ?? '') ?? throw new ReglaDeNegocioIncumplida('El trámite no indica un nivel válido.');
    }
}
