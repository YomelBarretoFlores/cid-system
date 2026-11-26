<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $ulid
 * @property int $tramite_id
 * @property string $nombre
 * @property string $path
 * @property string $sha256
 * @property int $bytes
 * @property string $mime
 * @property-read Tramite $tramite
 */
#[Table('tra_adjuntos')]
#[Fillable(['tramite_id', 'nombre', 'path', 'sha256', 'bytes', 'mime'])]
final class Adjunto extends Model
{
    use HasUlids;

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return BelongsTo<Tramite, $this>
     */
    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    protected function casts(): array
    {
        return ['bytes' => 'integer'];
    }
}
