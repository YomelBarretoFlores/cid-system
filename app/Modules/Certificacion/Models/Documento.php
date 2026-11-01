<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Modules\Academico\Enums\NivelCurricular;
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
 */
#[Table('cer_documentos')]
#[Fillable([
    'codigo', 'tipo', 'persona_id', 'matricula_id', 'estudiante_nombre', 'estudiante_documento', 'idioma', 'nivel', 'horas_lectivas',
    'nota_final', 'detalle', 'emitido_por', 'emitido_en', 'anulado_por', 'anulado_en', 'motivo_anulacion', 'anio_numero', 'numero',
])]
#[UsePolicy(DocumentoPolicy::class)]
final class Documento extends Model
{
    // The yearly number printed on the document; documents issued before numbering have none.
    public function numeroOficial(): ?string
    {
        $numero = $this->getAttribute('numero');
        $anio = $this->getAttribute('anio_numero');

        return is_numeric($numero) && is_numeric($anio) ? sprintf('N.º %06d-%d', (int) $numero, (int) $anio) : null;
    }

    public function estaVigente(): bool
    {
        return $this->anulado_en === null;
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
        ];
    }
}
