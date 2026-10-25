<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Listeners;

use App\Modules\Personas\Contracts\FichaDePersona;
use App\Modules\Tramites\Enums\EstadoNotificacion;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\Notificacion;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\CorreoDeCambioDeTramite;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Mail;
use Throwable;

// One log row per change, created on the first attempt and reused by the retries; a failed delivery never undoes the change.
#[Backoff([30, 120])]
#[Tries(3)]
final readonly class NotificarCambioDeTramite implements ShouldQueue
{
    public function __construct(private FichaDePersona $fichaDePersona) {}

    public function handle(TramiteActualizado $evento): void
    {
        $tramite = Tramite::query()->findOrFail($evento->tramiteId);
        $movimiento = Movimiento::query()->findOrFail($evento->movimientoId);
        $ficha = ($this->fichaDePersona)($tramite->persona_id);

        $notificacion = Notificacion::query()->firstOrCreate(['movimiento_id' => $movimiento->id], [
            'tramite_id' => $tramite->id,
            'destinatario' => $ficha->correo ?? '—',
            'asunto' => CorreoDeCambioDeTramite::asunto($tramite, $movimiento),
        ]);

        if ($notificacion->estado === EstadoNotificacion::Enviado) {
            return;
        }

        if ($ficha->correo === null) {
            $notificacion->update(['estado' => EstadoNotificacion::Fallido, 'error' => 'La persona no tiene correo registrado.']);

            return;
        }

        $notificacion->increment('intentos');
        Mail::to($ficha->correo)->send(new CorreoDeCambioDeTramite($tramite, $movimiento, $ficha->nombres));
        $notificacion->update(['estado' => EstadoNotificacion::Enviado, 'enviado_en' => CarbonImmutable::now(), 'error' => null]);
    }

    public function failed(TramiteActualizado $evento, Throwable $error): void
    {
        Notificacion::query()->where('movimiento_id', $evento->movimientoId)
            ->update(['estado' => EstadoNotificacion::Fallido->value, 'error' => mb_substr(class_basename($error), 0, 200)]);
    }
}
