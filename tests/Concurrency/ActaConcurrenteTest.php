<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Certificacion\Models\Rectificacion;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->curso = ActasFinales::curso();
    $hoy = CarbonImmutable::today('America/Lima');
    Periodo::query()->whereKey($this->curso['grupo']->periodo_id)->update(['actas_inicio' => $hoy->subDay()->toDateString(), 'actas_fin' => $hoy->addDay()->toDateString()]);
    foreach (['Alva', 'Bravo'] as $apellido) {
        ActasFinales::preActa(ActasFinales::estudiante($this->curso['grupo'], $apellido), [$this->curso['final']->id => '14.00']);
    }
});

it('closes the acta once when it is validated twice at the same time', function (): void {
    $argumentos = ['cerrar', $this->curso['grupo']->id, $this->curso['docente']->id, $this->curso['usuario']->id];

    $salidas = enParalelo('cerrar-o-autorizar-acta.php', [$argumentos, $argumentos]);

    expect($salidas)->toBe(['El acta final del grupo ING-1-M ya está cerrada.', 'hecho'])
        ->and(Acta::query()->sole()->estado)->toBe(EstadoActa::Cerrada)
        ->and(NotaFinal::query()->count())->toBe(2);
});

it('authorizes one rectification when two are requested at the same time', function (): void {
    app(CerrarActa::class)($this->curso['grupo']->id, $this->curso['docente']->id, $this->curso['usuario']);
    $argumentos = ['autorizar', Acta::query()->sole()->id, 0, User::factory()->create()->id];

    $salidas = enParalelo('cerrar-o-autorizar-acta.php', [$argumentos, $argumentos]);

    expect($salidas)->toBe(['El acta final del grupo ING-1-M ya tiene una rectificación autorizada.', 'hecho'])
        ->and(Rectificacion::query()->count())->toBe(1);
});
