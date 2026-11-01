<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Models;

use App\Modules\Pagos\Database\Factories\ConceptoFactory;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Policies\ConceptoPolicy;
use App\Shared\Support\Eloquent\NormalizaCodigo;
use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $fuente_id
 * @property-read FuenteDeConcepto $fuenteDeConcepto
 * @property TipoConcepto $tipo
 */
#[Table('pag_conceptos')]
#[Fillable(['codigo', 'nombre', 'tipo', 'fuente_id', 'activo'])]
#[UseFactory(ConceptoFactory::class)]
#[UsePolicy(ConceptoPolicy::class)]
final class Concepto extends Model
{
    /** @use HasFactory<ConceptoFactory> */
    use HasFactory, NormalizaCodigo, RenuevaContenidoPublico;

    /**
     * @return HasMany<Tarifa, $this>
     */
    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class)->orderBy('vigente_desde');
    }

    /**
     * @return BelongsTo<FuenteDeConcepto, $this>
     */
    public function fuenteDeConcepto(): BelongsTo
    {
        return $this->belongsTo(FuenteDeConcepto::class, 'fuente_id');
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoConcepto::class,
            'activo' => 'boolean',
        ];
    }
}
