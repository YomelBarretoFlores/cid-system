<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Certificacion\Support\HistorialDeNotas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Trámites requested before records were issued per level carry no level; they still get the whole-language record they paid for.
final readonly class EmitirConstanciaDeNotasDelIdioma
{
    public function __construct(private EmisionDeDocumento $emision) {}

    public function __invoke(int $personaId, string $idioma, User $emisor): Documento
    {
        EmisionDeDocumento::asegurarPermiso($emisor);
        $historial = HistorialDeNotas::de($personaId, $idioma);

        if ($historial === []) {
            throw new ReglaDeNegocioIncumplida("La persona no tiene notas finales de {$idioma} en actas cerradas.");
        }

        return $this->emision->emitir(TipoDocumento::ConstanciaNotas, $personaId, [
            'idioma' => $idioma,
            'detalle' => ['notas' => array_map(fn (NotaDelHistorial $nota): array => $nota->comoDetalle(), $historial)],
        ], $emisor);
    }
}
