<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Contracts\DenominacionDelAnio;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Contracts\FichaDePersona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class EmisionDeDocumento
{
    private const int INTENTOS_DE_CODIGO = 3;

    public function __construct(private FichaDePersona $fichaDePersona, private DenominacionDelAnio $denominacionDelAnio) {}

    public static function asegurarPermiso(User $usuario): void
    {
        // Checked in the Actions too, so every entry point (panel, API, console) needs the permission.
        throw_unless($usuario->can(PermisoCertificacion::Documentos->value), AuthorizationException::class);
    }

    /**
     * @param  array{idioma: string, nivel?: ?string, horas_lectivas?: ?int, nota_final?: ?string, matricula_id?: ?int, detalle: array<string, mixed>}  $datos
     */
    public function emitir(TipoDocumento $tipo, int $personaId, array $datos, User $emisor, string $duplicado = ''): Documento
    {
        $ficha = ($this->fichaDePersona)($personaId);

        for ($intento = 1; ; $intento++) {
            try {
                return Transaccion::ejecutar(function () use ($datos, $tipo, $personaId, $ficha, $emisor): Documento {
                    $anio = (int) now('America/Lima')->format('Y');

                    // The signers and the year's denomination in force are frozen in the document, like the rest of it, so editing a signer never rewrites issued documents.
                    $datos['detalle'] = [
                        ...$datos['detalle'],
                        'firmas' => FirmasDelDocumento::de($tipo->grupoDeFirma(), now('America/Lima')),
                        'denominacion' => ($this->denominacionDelAnio)($anio),
                    ];

                    return Documento::query()->create([
                        ...$datos, 'codigo' => CodigoDeDocumento::nuevo(), 'tipo' => $tipo, 'persona_id' => $personaId,
                        'estudiante_nombre' => $ficha->nombreCompleto, 'estudiante_documento' => $ficha->documento,
                        'emitido_por' => $emisor->id, 'emitido_en' => now(),
                        'anio_numero' => $anio, 'numero' => NumeracionDeDocumentos::siguiente($tipo, $anio),
                    ]);
                });
            } catch (UniqueConstraintViolationException $error) {
                $this->traducir($error, $intento, $duplicado);
            }
        }
    }

    private function traducir(UniqueConstraintViolationException $error, int $intento, string $duplicado): void
    {
        // A repeated random code is retried; a second valid certificate for the same level is a business rule.
        if (str_contains($error->getMessage(), 'cer_documentos_certificado_vigente_unique')) {
            throw new ReglaDeNegocioIncumplida($duplicado);
        }

        if ($intento >= self::INTENTOS_DE_CODIGO) {
            throw $error;
        }
    }
}
