<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Data\DatosDeLaInstitucion;
use App\Modules\Certificacion\Data\TextosDeLosDocumentos;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Policies\DocumentoPolicy;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TipoDocumento $tipo
 * @property ?NivelCurricular $nivel
 * @property ?string $nota_final
 * @property array<string, mixed> $detalle
 * @property CarbonImmutable $emitido_en
 * @property ?CarbonImmutable $anulado_en
 * @property ?CarbonImmutable $revisar_desde
 * @property ?string $motivo_revision
 */
#[Table('cer_documentos')]
#[Fillable([
    'codigo', 'tipo', 'persona_id', 'matricula_id', 'estudiante_nombre', 'estudiante_documento', 'idioma', 'nivel', 'horas_lectivas',
    'nota_final', 'detalle', 'emitido_por', 'emitido_en', 'anulado_por', 'anulado_en', 'motivo_anulacion', 'anio_numero', 'numero',
])]
#[UsePolicy(DocumentoPolicy::class)]
final class Documento extends Model
{
    public const string FORMATO_ORIGINAL = 'N.º {numero:06}-{anio}';

    // The yearly number printed on the document; documents issued before numbering have none.
    public function numeroOficial(): ?string
    {
        $numero = $this->getAttribute('numero');
        $anio = $this->getAttribute('anio_numero');

        if (! is_numeric($numero) || ! is_numeric($anio)) {
            return null;
        }

        // The pattern is frozen at issue time; documents issued before it was configurable keep the original one.
        $formato = $this->detalle['formato_numero'] ?? self::FORMATO_ORIGINAL;

        return DatosDeLaInstitucion::numerar((string) $formato, (int) $numero, (int) $anio);
    }

    // The text frozen at issue; documents issued before the texts were editable print the original wording.
    public function texto(string $clave): string
    {
        $texto = $this->detalle['textos'][$clave] ?? TextosDeLosDocumentos::ORIGINALES[$clave] ?? '';

        return is_string($texto) ? $texto : '';
    }

    // Behind a "N° Registro:" label the pattern's own "N.º"/"N°" prefix would repeat.
    public function numeroDeRegistro(): ?string
    {
        $numero = $this->numeroOficial();

        return $numero === null ? null : (string) preg_replace('/^N[.ºo°]*\s*/u', '', $numero);
    }

    // Grade records issued per level (detalle version 2) print the real CILN format; older ones keep the first template.
    public function esConstanciaPorNivel(): bool
    {
        return $this->tipo === TipoDocumento::ConstanciaNotas && ($this->detalle['version'] ?? 1) === 2;
    }

    public function estaVigente(): bool
    {
        return $this->anulado_en === null;
    }

    // A voided document no longer needs review, whatever its flag says.
    public function estaPorRevisar(): bool
    {
        return $this->estaVigente() && $this->revisar_desde !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function emisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anulador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoDocumento::class,
            'nivel' => NivelCurricular::class,
            'horas_lectivas' => 'integer',
            'nota_final' => 'decimal:2',
            'detalle' => 'array',
            'emitido_en' => 'immutable_datetime',
            'anulado_en' => 'immutable_datetime',
            'revisar_desde' => 'immutable_datetime',
        ];
    }
}
