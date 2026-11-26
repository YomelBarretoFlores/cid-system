<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Data;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;

final readonly class DatosDeTipoTramite
{
    /**
     * @param  list<array{nombre: string, avance: int, condicion: CondicionDeEtapa}>  $etapas
     * @param  ?list<array{clave: string, obligatorio: bool}>  $campos  null keeps the fields the final action asks
     * @param  list<int>  $programaIds  empty lets every program request it
     * @param  list<array{nombre: string, obligatorio: bool}>  $adjuntos
     */
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $requisitos,
        public ?TipoConcepto $tipoConcepto,
        public AccionFinal $accionFinal,
        public bool $activo,
        public array $etapas,
        public ?int $conceptoId = null,
        public ?array $campos = null,
        public ?int $tipoPrevioId = null,
        public array $programaIds = [],
        public array $adjuntos = [],
    ) {}

    public function conConcepto(int $conceptoId, TipoConcepto $tipo): self
    {
        return new self(
            $this->codigo, $this->nombre, $this->requisitos, $tipo, $this->accionFinal, $this->activo, $this->etapas,
            $conceptoId, $this->campos, $this->tipoPrevioId, $this->programaIds, $this->adjuntos,
        );
    }
}
