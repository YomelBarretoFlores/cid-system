<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\IdiomaFactory;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('aca_idiomas')]
#[Fillable(['codigo', 'nombre', 'es_lengua_nativa', 'activo'])]
#[UseFactory(IdiomaFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Idioma extends Model
{
    /** @use HasFactory<IdiomaFactory> */
    use HasFactory;

    /**
     * @return HasMany<Ciclo, $this>
     */
    public function ciclos(): HasMany
    {
        return $this->hasMany(Ciclo::class)->orderBy('numero');
    }

    public static function normalizarCodigo(string $codigo): string
    {
        return mb_strtoupper(trim($codigo));
    }

    /**
     * @return Attribute<string, string>
     */
    protected function codigo(): Attribute
    {
        return Attribute::set(fn (string $value): string => self::normalizarCodigo($value));
    }

    protected function casts(): array
    {
        return [
            'es_lengua_nativa' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}
