<?php

declare(strict_types=1);

use App\Modules\Tramites\Enums\CondicionDeEtapa as C;
use App\Modules\Tramites\Support\ReglasDeEtapas;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

/**
 * @param  list<array{0: int, 1: C}>  $etapas
 * @return list<array{nombre: string, avance: int, condicion: C}>
 */
function etapas(array $etapas): array
{
    return array_map(fn (array $etapa, int $indice): array => ['nombre' => "Etapa {$indice}", 'avance' => $etapa[0], 'condicion' => $etapa[1]], $etapas, array_keys($etapas));
}

it('accepts the Anexo pipeline with a fee and a short one without it', function (): void {
    ReglasDeEtapas::validar(etapas([[0, C::Solicitud], [33, C::PagoValidado], [66, C::AccionDelPersonal], [100, C::AccionDelPersonal]]), true);
    ReglasDeEtapas::validar(etapas([[0, C::Solicitud], [100, C::AccionDelPersonal]]), false);
})->throwsNoExceptions();

it('reads percentages typed as text in the panel as numbers', function (): void {
    ReglasDeEtapas::validar([
        ['nombre' => 'Solicitud', 'avance' => '0', 'condicion' => C::Solicitud],
        ['nombre' => 'Atendido', 'avance' => '100', 'condicion' => C::AccionDelPersonal],
    ], false);
})->throwsNoExceptions();

it('rejects a pipeline that breaks a rule, saying which', function (array $etapas, bool $cobraTasa, string $mensaje): void {
    expect(fn () => ReglasDeEtapas::validar(etapas($etapas), $cobraTasa))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
})->with([
    'one stage' => [[[0, C::Solicitud]], false, 'al menos dos etapas'],
    'first stage not the request' => [[[10, C::Solicitud], [100, C::AccionDelPersonal]], false, 'La primera etapa es la solicitud'],
    'request reached twice' => [[[0, C::Solicitud], [50, C::Solicitud], [100, C::AccionDelPersonal]], false, 'Solo la primera etapa'],
    'last stage below 100' => [[[0, C::Solicitud], [90, C::AccionDelPersonal]], false, '100 % de avance'],
    'progress going back' => [[[0, C::Solicitud], [60, C::AccionDelPersonal], [60, C::AccionDelPersonal], [100, C::AccionDelPersonal]], false, 'crecer'],
    'fee without its stage' => [[[0, C::Solicitud], [100, C::AccionDelPersonal]], true, 'una sola etapa de pago validado'],
    'fee stage not second' => [[[0, C::Solicitud], [50, C::AccionDelPersonal], [70, C::PagoValidado], [100, C::AccionDelPersonal]], true, 'justo después de la solicitud'],
    'fee validated as the last stage' => [[[0, C::Solicitud], [100, C::PagoValidado]], true, 'La última etapa la avanza el personal'],
    'payment stage without fee' => [[[0, C::Solicitud], [50, C::PagoValidado], [100, C::AccionDelPersonal]], false, 'sin tasa no tiene etapa de pago'],
]);
