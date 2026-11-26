<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Database\Factories\TipoTramiteFactory;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Policies\TipoTramitePolicy;
use App\Shared\Support\Eloquent\NormalizaCodigo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property ?TipoConcepto $tipo_concepto
 * @property ?int $concepto_id
 * @property AccionFinal $accion_final
 * @property list<array{clave: string, obligatorio: bool}> $campos
 * @property list<array{nombre: string, obligatorio: bool}> $adjuntos
 * @property ?int $requiere_tipo_previo_id
 * @property ?int $meses_sin_estudiar
 * @property-read ?TipoTramite $tipoPrevio
 * @property-read Collection<int, ProgramaDeTipoTramite> $programas
 * @property-read Collection<int, Etapa> $etapas
 */
#[Table('tra_tipos_tramite')]
#[Fillable(['codigo', 'nombre', 'requisitos', 'tipo_concepto', 'concepto_id', 'accion_final', 'activo', 'campos', 'adjuntos', 'requiere_tipo_previo_id', 'meses_sin_estudiar'])]
#[UseFactory(TipoTramiteFactory::class)]
#[UsePolicy(TipoTramitePolicy::class)]
final class TipoTramite extends Model
{
    /** @use HasFactory<TipoTramiteFactory> */
    use HasFactory, NormalizaCodigo;

    /**
     * @return HasMany<Etapa, $this>
     */
    public function etapas(): HasMany
    {
        return $this->hasMany(Etapa::class)->orderBy('orden');
    }

    /**
     * @return HasMany<ProgramaDeTipoTramite, $this>
     */
    public function programas(): HasMany
    {
        return $this->hasMany(ProgramaDeTipoTramite::class);
    }

    /**
     * @return BelongsTo<TipoTramite, $this>
     */
    public function tipoPrevio(): BelongsTo
    {
        return $this->belongsTo(self::class, 'requiere_tipo_previo_id');
    }

    public function cobraTasa(): bool
    {
        return $this->tipo_concepto !== null;
    }

    protected function casts(): array
    {
        return [
            'tipo_concepto' => TipoConcepto::class,
            'accion_final' => AccionFinal::class,
            'activo' => 'boolean',
            'campos' => 'array',
            'adjuntos' => 'array',
            'meses_sin_estudiar' => 'integer',
        ];
    }
}
