<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\ConfirmarClaseDictada;
use App\Modules\Docencia\Actions\DesignarDelegado;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Livewire\ClasesPorConfirmar;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    Persona::query()->whereKey($this->docente->persona_id)->update(['apellido_paterno' => 'Ramos', 'apellido_materno' => 'Vega', 'nombres' => 'Julia']);
    [$this->grupo, [$this->ana, $this->luis, $this->rosa]] = PortalDocente::grupoConEstudiantes($this->docente, 'Quispe', 'Álvarez', 'Zapata');
    app(DesignarDelegado::class)($this->grupo->id, $this->docente->id, $this->ana->id, $this->usuario);
    $this->clase = PortalDocente::dictarClase(
        $this->grupo,
        $this->docente,
        $this->usuario,
        [$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'falta'],
        [$this->ana->id => 'presente', $this->luis->id => 'falta'],
    );
});

function usuarioDeLaMatricula(Matricula $matricula): User
{
    return User::query()->findOrFail(Persona::query()->findOrFail($matricula->persona_id)->user_id);
}

function enElPortalALas(string $hora): void
{
    test()->travelTo(CarbonImmutable::parse("2027-03-10 {$hora}", 'America/Lima'));
}

it('lists the classes the delegate has to confirm with their teacher and attendance', function (): void {
    enElPortalALas('20:10');

    $this->actingAs(usuarioDeLaMatricula($this->ana))
        ->get(route('estudiante.clases'))
        ->assertOk()
        ->assertSeeInOrder(['Clases por confirmar', 'Clase del 10/03/2027', '18:00 – 20:00', 'ING-01-A', 'Ramos Vega, Julia', '1 de 3 estudiantes presentes al cierre', 'Puedes responder hasta las 22:00', 'Sí, se dictó', 'No se dictó / observar']);
});

it('keeps the page for students with a verified account', function (): void {
    $this->get(route('estudiante.clases'))->assertRedirect(route('ingresar'));
});

it('confirms a class from the page and moves it to the reviewed ones', function (): void {
    enElPortalALas('20:10');

    Livewire::actingAs(usuarioDeLaMatricula($this->ana))
        ->test(ClasesPorConfirmar::class)
        ->call('confirmar', $this->clase->id)
        ->assertHasNoErrors()
        ->assertSee('Confirmaste la clase del 10/03/2027.')
        ->assertDontSee('Sí, se dictó')
        ->assertSee(['Revisadas recientemente', 'Clase del 10/03/2027', 'Confirmada']);

    expect($this->clase->refresh()->only(['confirmacion', 'confirmada_por_matricula_id']))->toBe(['confirmacion' => ConfirmacionDeClase::Confirmada, 'confirmada_por_matricula_id' => $this->ana->id]);
});

it('observes a class only with a comment', function (): void {
    enElPortalALas('20:10');
    Log::spy();
    $mensaje = 'Explica en al menos 5 caracteres por qué observas la clase.';

    $pagina = Livewire::actingAs(usuarioDeLaMatricula($this->ana))
        ->test(ClasesPorConfirmar::class)
        ->call('observar', $this->clase->id)
        ->assertSet('observando', $this->clase->id)
        ->assertSee('¿Qué pasó en la clase?')
        ->set('observacion', '  ')
        ->call('enviarObservacion')
        ->assertHasErrors(['observacion' => $mensaje])
        ->assertSee($mensaje);

    expect($this->clase->refresh()->confirmacion)->toBe(ConfirmacionDeClase::Pendiente);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', Mockery::on(fn (array $contexto): bool => $contexto['regla'] === $mensaje))->once();

    $pagina->set('observacion', 'El docente llegó a las 19:30.')
        ->call('enviarObservacion')
        ->assertHasNoErrors()
        ->assertSet('observando', null)
        ->assertSee('Observaste la clase del 10/03/2027.')
        ->assertSee(['Revisadas recientemente', 'Observada', 'El docente llegó a las 19:30.']);

    expect($this->clase->refresh()->only(['confirmacion', 'observacion']))->toBe(['confirmacion' => ConfirmacionDeClase::Observada, 'observacion' => 'El docente llegó a las 19:30.']);
});

it('shows and logs a refused confirmation', function (): void {
    enElPortalALas('20:10');
    Log::spy();
    $pagina = Livewire::actingAs(usuarioDeLaMatricula($this->ana))->test(ClasesPorConfirmar::class);
    app(ConfirmarClaseDictada::class)($this->clase->id, $this->ana->persona_id, true, null);
    $mensaje = 'Esta clase ya fue confirmada el 10/03/2027 a las 20:10.';

    // The message holds a colon, which assertHasErrors would read as a rule with parameters.
    $pagina->call('confirmar', $this->clase->id)->assertHasErrors('confirmacion')->assertSee($mensaje);

    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', Mockery::on(fn (array $contexto): bool => $contexto['regla'] === $mensaje))->once();
});

it('refuses to confirm a class of another group from the page', function (): void {
    enElPortalALas('20:10');

    Livewire::actingAs(usuarioDeLaMatricula($this->luis))
        ->test(ClasesPorConfirmar::class)
        ->call('confirmar', $this->clase->id)
        ->assertHasErrors(['confirmacion' => 'Solo el delegado del grupo ING-01-A puede confirmar sus clases.']);

    expect($this->clase->refresh()->confirmacion)->toBe(ConfirmacionDeClase::Pendiente);
});

it('tells a student who is not a delegate that there is nothing to confirm', function (): void {
    enElPortalALas('20:10');

    $this->actingAs(usuarioDeLaMatricula($this->luis))
        ->get(route('estudiante.clases'))
        ->assertOk()
        ->assertSee('No eres delegado de ningún grupo.')
        ->assertDontSee('Clase del 10/03/2027');
});

it('stops listing a class once its confirmation window passes', function (): void {
    enElPortalALas('22:01');

    $this->actingAs(usuarioDeLaMatricula($this->ana))
        ->get(route('estudiante.clases'))
        ->assertOk()
        ->assertSee('No tienes clases por confirmar.')
        ->assertDontSee('Sí, se dictó');
});

it('tells a delegate absent from the closing call that they cannot confirm the class', function (): void {
    enElPortalALas('20:10');
    app(DesignarDelegado::class)($this->grupo->id, $this->docente->id, $this->luis->id, $this->usuario);

    $this->actingAs(usuarioDeLaMatricula($this->luis))
        ->get(route('estudiante.clases'))
        ->assertOk()
        ->assertSee('Clase del 10/03/2027')
        ->assertSee('No figuras presente en el llamado de cierre, así que no puedes confirmar esta clase.')
        ->assertDontSee('Sí, se dictó');
});

it('shows the delegate how many classes wait for them on the portal home', function (string $hora, Closure $quien, bool $visible): void {
    enElPortalALas($hora);

    $pagina = $this->actingAs(usuarioDeLaMatricula($quien->call($this)))->get(route('estudiante.inicio'))->assertOk()->assertSee('Hola,');

    $visible
        ? $pagina->assertSee('Tienes 1 clase por confirmar')->assertSee(route('estudiante.clases'))
        : $pagina->assertDontSee('por confirmar')->assertDontSee(route('estudiante.clases'));
})->with([
    'the delegate within the window' => ['20:10', fn (): Matricula => $this->ana, true],
    'the delegate after the window' => ['22:01', fn (): Matricula => $this->ana, false],
    'a student who is not the delegate' => ['20:10', fn (): Matricula => $this->luis, false],
]);

it('shows the delegate the topic the teacher wrote so the class can be checked', function (): void {
    enElPortalALas('20:10');

    $this->actingAs(usuarioDeLaMatricula($this->ana))
        ->get(route('estudiante.clases'))
        ->assertSeeInOrder(['Clase del 10/03/2027', 'Tema dictado', 'Saludos y presentaciones', 'Sí, se dictó']);
});
