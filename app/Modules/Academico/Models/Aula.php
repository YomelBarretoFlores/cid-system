<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\AulaFactory;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('aca_aulas')]
#[Fillable(['codigo', 'nombre', 'capacidad', 'ubicacion', 'activo'])]
#[UseFactory(AulaFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Aula extends Model
{
    /** @use HasFactory<AulaFactory> */
    use HasFactory;

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
            'capacidad' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
