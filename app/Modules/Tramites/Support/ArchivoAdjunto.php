<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use Illuminate\Http\UploadedFile;

final readonly class ArchivoAdjunto
{
    public function __construct(
        public string $nombre,
        public UploadedFile $archivo,
        public string $mime,
        public string $extension,
    ) {}
}
