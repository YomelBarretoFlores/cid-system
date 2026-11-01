<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Modules\Certificacion\Policies\DenominacionDelAnioPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $anio
 * @property string $texto
 */
#[Table('cer_denominaciones')]
#[Fillable(['anio', 'texto'])]
#[UsePolicy(DenominacionDelAnioPolicy::class)]
final class DenominacionDelAnio extends Model
{
    protected function casts(): array
    {
        return ['anio' => 'integer'];
    }
}
