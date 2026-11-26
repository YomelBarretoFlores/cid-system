<?php

declare(strict_types=1);

namespace App\Shared\Parametros\Models;

use App\Shared\Parametros\Enums\Parametro;
use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 */
#[Table('sis_parametros')]
#[Fillable([
    'dias_vigencia_clasificacion', 'puntaje_maximo_clasificacion', 'asistencia_inicio_abre_minutos_antes', 'asistencia_cierre_abre_minutos_antes_del_fin',
    'asistencia_cierre_cierra_minutos_despues_del_fin', 'asistencia_confirmacion_minutos_despues_del_fin', 'maximo_adjuntos_tramite', 'adjunto_maximo_mb',
    'recibo_maximo_mb', 'foto_maximo_mb', 'foto_lado_minimo_px', 'foto_lado_maximo_px', 'umbral_pocas_vacantes',
])]
#[WithoutIncrementing]
final class Parametros extends Model
{
    // The public page caches the few-vacancies threshold and the placement validity.
    use RenuevaContenidoPublico;

    public const int FILA = 1;

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return array_fill_keys(array_column(Parametro::cases(), 'value'), 'integer');
    }
}
