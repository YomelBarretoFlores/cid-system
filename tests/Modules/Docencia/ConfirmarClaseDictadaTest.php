<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\ConfirmarClaseDictada;
use App\Modules\Docencia\Actions\DesignarDelegado;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\EstadoDeLaClase;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    [$this->grupo, [$this->ana, $this->luis, $this->rosa]] = PortalDocente::grupoConEstudiantes($this->docente, 'Quispe', 'Álvarez', 'Zapata');
    app(DesignarDelegado::class)($this->grupo->id, $this->docente->id, $this->ana->id, $this->usuario);
    $this->clase = PortalDocente::dictarClase(
        $this->grupo,
        $this->docente,
        $this->usuario,
        [$this->ana->id => 'presente', $this->luis->id => 'tardanza', $this->rosa->id => 'falta'],
        [$this->ana->id => 'presente', $this->luis->id => 'falta'],
    );
});

function horaDeConfirmar(string $hora): void
{
    test()->travelTo(CarbonImmutable::parse("2027-03-10 {$hora}", 'America/Lima'));
}

function confirmarComo(Matricula $matricula, bool $dictada = true, ?string $observacion = null, ?int $claseId = null): ClaseDictada
{
    return app(ConfirmarClaseDictada::class)($claseId ?? test()->clase->id, $matricula->persona_id, $dictada, $observacion);
}

function revisionDelDelegado(ClaseDictada $clase): array
{
    $clase->refresh();

    return [$clase->confirmacion, $clase->confirmada_por_matricula_id, $clase->confirmada_en?->setTimezone('America/Lima')->format('Y-m-d H:i'), $clase->observacion];
}

it('confirms the class taught when the delegate answers within the window', function (string $hora): void {
    horaDeConfirmar($hora);

    confirmarComo($this->ana);

    expect(revisionDelDelegado($this->clase))->toBe([ConfirmacionDeClase::Confirmada, $this->ana->id, "2027-03-10 {$hora}", null])
        ->and(EstadoDeLaClase::de($this->clase, CarbonImmutable::parse('2027-03-11 09:00', 'America/Lima')))->toBe(EstadoDeClase::Confirmada);
})->with(['right after the closing call' => '19:50', 'at the deadline' => '22:00']);

it('observes the class with the trimmed comment of the delegate', function (): void {
    horaDeConfirmar('20:10');

    confirmarComo($this->ana, false, "  El docente llegó a las 19:30.\n ");

    expect(revisionDelDelegado($this->clase))->toBe([ConfirmacionDeClase::Observada, $this->ana->id, '2027-03-10 20:10', 'El docente llegó a las 19:30.'])
        ->and(EstadoDeLaClase::de($this->clase))->toBe(EstadoDeClase::Observada);
});

it('refuses a confirmation that breaks a rule and leaves the class unchanged', function (Closure $preparar, string $mensaje): void {
    horaDeConfirmar('20:10');
    [$matricula, $dictada, $observacion, $claseId] = $preparar->call($this) + [$this->ana, true, null, null];
    $antes = $this->clase->refresh()->getAttributes();

    expect(fn (): ClaseDictada => confirmarComo($matricula, $dictada, $observacion, $claseId))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and($this->clase->refresh()->getAttributes())->toBe($antes);
})->with([
    'a student who is not the delegate' => [fn (): array => [$this->luis], 'Solo el delegado del grupo ING-01-A puede confirmar sus clases.'],
    'a delegate whose enrollment is no longer active' => [function (): array {
        $this->ana->update(['estado' => 'retirada', 'retirado_en' => now()]);

        return [];
    }, 'Solo el delegado del grupo ING-01-A puede confirmar sus clases.'],
    'a delegate who left before the closing call' => [function (): array {
        app(DesignarDelegado::class)($this->grupo->id, $this->docente->id, $this->luis->id, $this->usuario);

        return [$this->luis];
    }, 'Solo puedes confirmar una clase en la que figuras presente en el llamado de cierre.'],
    'a delegate absent from the start' => [function (): array {
        app(DesignarDelegado::class)($this->grupo->id, $this->docente->id, $this->rosa->id, $this->usuario);

        return [$this->rosa];
    }, 'Solo puedes confirmar una clase en la que figuras presente en el llamado de cierre.'],
    'after the deadline' => [function (): array {
        horaDeConfirmar('22:01');

        return [];
    }, 'El plazo para confirmar la clase del 10/03/2027 venció a las 22:00.'],
    'an observation without a comment' => [fn (): array => [1 => false], 'Explica en al menos 5 caracteres por qué observas la clase.'],
    'an observation with a short comment' => [fn (): array => [1 => false, 2 => '  Mal  '], 'Explica en al menos 5 caracteres por qué observas la clase.'],
    'an observation with a long comment' => [fn (): array => [1 => false, 2 => str_repeat('a', 501)], 'La observación admite hasta 500 caracteres.'],
    'a class that does not exist' => [fn (): array => [3 => 999_999], 'La clase no existe.'],
]);

it('refuses to confirm a class twice and keeps the first answer', function (bool $dictada, ?string $observacion): void {
    horaDeConfirmar('20:10');
    confirmarComo($this->ana);
    horaDeConfirmar('20:20');

    expect(fn (): ClaseDictada => confirmarComo($this->ana, $dictada, $observacion))->toThrow(ReglaDeNegocioIncumplida::class, 'Esta clase ya fue confirmada el 10/03/2027 a las 20:10.')
        ->and(revisionDelDelegado($this->clase))->toBe([ConfirmacionDeClase::Confirmada, $this->ana->id, '2027-03-10 20:10', null]);
})->with(['the same answer' => [true, null], 'an observation afterwards' => [false, 'El docente no llegó.']]);

it('refuses to confirm a class whose closing call was not taken', function (Closure $preparar, string $mensaje): void {
    $clase = $preparar->call($this);
    $antes = $clase->getAttributes();

    expect(fn (): ClaseDictada => confirmarComo($this->ana, claseId: $clase->id))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and($clase->refresh()->getAttributes())->toBe($antes);
})->with([
    'only the first call' => [function (): ClaseDictada {
        $clase = PortalDocente::dictarClase($this->grupo, $this->docente, $this->usuario, [$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente'], fecha: '2027-03-15');
        $this->travelTo(CarbonImmutable::parse('2027-03-15 20:10', 'America/Lima'));

        return $clase;
    }, 'El docente aún no toma el llamado de cierre de esta clase.'],
    'registro posterior' => [function (): ClaseDictada {
        horaDeConfirmar('20:10');

        return app(RegistrarAsistencia::class)($this->grupo->id, $this->docente->id, '2027-03-08', '18:00', [$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente'], $this->usuario)->refresh();
    }, 'Esta clase es un registro posterior; no necesita la confirmación del delegado.'],
]);
