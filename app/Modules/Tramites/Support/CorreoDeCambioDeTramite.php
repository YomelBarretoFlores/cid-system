<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\Tramite;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class CorreoDeCambioDeTramite extends Mailable
{
    public function __construct(
        public readonly Tramite $tramite,
        public readonly Movimiento $movimiento,
        public readonly string $nombres,
    ) {}

    public static function asunto(Tramite $tramite, Movimiento $movimiento): string
    {
        return "Tu trámite {$tramite->codigo}: {$movimiento->estado->etiqueta()}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::asunto($this->tramite, $this->movimiento));
    }

    public function content(): Content
    {
        return new Content(markdown: 'tramites::correos.cambio-de-tramite');
    }
}
