<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Data;

use Illuminate\Http\UploadedFile;

final readonly class DatosDeSolicitud
{
    /**
     * @param  array<int, UploadedFile>  $adjuntos  by the position of the attachment in the type's list
     */
    public function __construct(
        public ?int $matriculaId = null,
        public ?string $idioma = null,
        public ?string $nivel = null,
        public ?string $motivo = null,
        public ?string $comentario = null,
        public ?int $programaId = null,
        public ?string $mencionGrado = null,
        public ?string $mencionTexto = null,
        public ?string $exposicion = null,
        public ?string $celular = null,
        public array $adjuntos = [],
    ) {}
}
