<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Matricula\Contracts\DocumentoDeMatriculaPorId;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class EmitirConstanciaDeMatricula
{
    public function __construct(
        private DocumentoDeMatriculaPorId $documentoDeMatricula,
        private EmisionDeDocumento $emision,
    ) {}

    public function __invoke(int $matriculaId, User $emisor): Documento
    {
        EmisionDeDocumento::asegurarPermiso($emisor);
        $matricula = ($this->documentoDeMatricula)($matriculaId) ?? throw new ReglaDeNegocioIncumplida('La matrícula no existe.');

        if ($matricula->estado !== EstadoMatricula::Activa) {
            throw new ReglaDeNegocioIncumplida("La matrícula en el grupo {$matricula->grupo->codigo} está retirada; la constancia de matrícula solo se emite para una matrícula activa.");
        }

        $grupo = $matricula->grupo;

        return $this->emision->emitir(TipoDocumento::ConstanciaMatricula, $matricula->estudiante->personaId, [
            'matricula_id' => $matriculaId, 'idioma' => $grupo->idioma, 'nivel' => $this->nivel($grupo->nivel),
            'detalle' => [
                'grupo' => $grupo->codigo, 'ciclo_numero' => $grupo->cicloNumero, 'periodo' => $grupo->periodo, 'modalidad' => $grupo->modalidad,
                'horario' => $grupo->horarios, 'matriculado_el' => $matricula->matriculadoEn->toDateString(), 'codigo_matricula' => $matricula->codigo,
            ],
        ], $emisor);
    }

    // The group card carries the level label; the document stores the level value.
    private function nivel(string $etiqueta): ?string
    {
        foreach (NivelCurricular::cases() as $nivel) {
            if ($nivel->etiqueta() === $etiqueta) {
                return $nivel->value;
            }
        }

        return null;
    }
}
