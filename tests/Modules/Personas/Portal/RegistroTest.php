<?php

declare(strict_types=1);

use App\Modules\Personas\Actions\RegistrarCuentaDeEstudiante;
use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Livewire\Registro;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $cambios
 */
function registrarse(array $cambios = []): Testable
{
    $datos = [
        'tipo_documento' => 'dni',
        'numero_documento' => '41234567',
        'apellido_paterno' => 'Quispe',
        'apellido_materno' => 'Mamani',
        'nombres' => 'Rosa Elena',
        'fecha_nacimiento' => '2001-05-14',
        'email' => 'rosa@correo.test',
        'celular' => '987 654 321',
        'password' => 'clave-segura1',
        'password_confirmation' => 'clave-segura1',
        'acepta_tratamiento' => true,
        ...$cambios,
    ];

    $componente = Livewire::test(Registro::class);

    foreach ($datos as $campo => $valor) {
        $componente->set($campo, $valor);
    }

    return $componente->call('registrarse');
}

beforeEach(fn () => Notification::fake());

it('shows the registration form to guests', function (): void {
    $this->get(route('estudiante.registro'))
        ->assertOk()
        ->assertSee('Crea tu cuenta de estudiante')
        ->assertSee('Ley N° 29733');
});

it('creates the person and an unverified account, signs it in and asks to verify the email', function (): void {
    registrarse(['email' => '  Rosa@Correo.TEST '])
        ->assertHasNoErrors()
        ->assertRedirect(route('verification.notice'));

    $persona = Persona::query()->sole();
    $user = User::query()->where('email', 'rosa@correo.test')->sole();

    expect($persona)
        ->user_id->toBe($user->id)
        ->tipo_documento->toBe(TipoDocumento::Dni)
        ->numero_documento->toBe('41234567')
        ->nombres->toBe('Rosa Elena')
        ->correo->toBe('rosa@correo.test')
        ->celular->toBe('987654321')
        ->tipo_vinculo->toBe(TipoVinculo::PublicoGeneral)
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and(Hash::check('clave-segura1', $user->password))->toBeTrue()
        ->and($user->name)->toBe('Rosa Elena Quispe');

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('links an existing CILN record when its email matches and keeps its data', function (): void {
    $persona = Persona::factory()->estudianteUnasam()->create([
        'numero_documento' => '41234567', 'nombres' => 'Rosa', 'correo' => 'rosa@correo.test',
    ]);

    registrarse(['email' => 'ROSA@correo.test', 'nombres' => 'Otro Nombre'])
        ->assertHasNoErrors()
        ->assertRedirect(route('verification.notice'));

    $persona->refresh();

    expect(Persona::query()->count())->toBe(1)
        ->and($persona->user_id)->toBe(User::query()->where('email', 'rosa@correo.test')->sole()->id)
        ->and($persona->nombres)->toBe('Rosa')
        ->and($persona->tipo_vinculo)->toBe(TipoVinculo::EstudianteUnasam);
});

it('refuses to link a CILN record registered with another email or without one', function (?string $correoRegistrado): void {
    Persona::factory()->create(['numero_documento' => '41234567', 'correo' => $correoRegistrado]);

    registrarse()->assertHasErrors([
        'numero_documento' => RegistrarCuentaDeEstudiante::DOCUMENTO_NO_DISPONIBLE,
    ]);

    expect(User::query()->count())->toBe(0)
        ->and(Persona::query()->sole()->user_id)->toBeNull();
    $this->assertGuest();
})->with([
    'another email' => ['otra@correo.test'],
    'no email' => [null],
]);

it('refuses a document that already has an account', function (): void {
    $dueno = User::factory()->create(['email' => 'rosa@correo.test']);
    Persona::factory()->create(['numero_documento' => '41234567', 'correo' => 'rosa@correo.test', 'user_id' => $dueno->id]);

    registrarse(['email' => 'rosa.nueva@correo.test'])->assertHasErrors([
        'numero_documento' => RegistrarCuentaDeEstudiante::DOCUMENTO_NO_DISPONIBLE,
    ]);

    expect(User::query()->count())->toBe(1);
});

it('refuses an email that another account already uses', function (): void {
    User::factory()->create(['email' => 'rosa@correo.test']);

    registrarse()->assertHasErrors(['email' => 'Ya existe una cuenta con ese correo.']);

    expect(Persona::query()->count())->toBe(0);
});

it('validates the registration data', function (array $cambios, string $campo, string $mensaje): void {
    registrarse($cambios)->assertHasErrors([$campo => $mensaje]);

    expect(User::query()->count())->toBe(0);
})->with([
    'short DNI' => [['numero_documento' => '1234567'], 'numero_documento', 'El DNI tiene 8 dígitos.'],
    'bad passport' => [['tipo_documento' => 'pasaporte', 'numero_documento' => 'AB-12'], 'numero_documento', 'El documento tiene de 6 a 12 letras o números.'],
    'future birth date' => [['fecha_nacimiento' => now()->addDay()->toDateString()], 'fecha_nacimiento', 'Escribe una fecha de nacimiento válida.'],
    'bad phone' => [['celular' => '12-ab'], 'celular', 'Escribe un celular válido, solo con números.'],
    'missing names' => [['nombres' => ''], 'nombres', 'Completa el campo nombres.'],
    'weak password' => [['password' => 'solo-letras', 'password_confirmation' => 'solo-letras'], 'password', 'La contraseña debe incluir al menos una letra y un número.'],
    'no consent' => [['acepta_tratamiento' => false], 'acepta_tratamiento', 'Debes aceptar el tratamiento de tus datos personales para crear la cuenta.'],
]);

it('accepts a passport with lowercase letters and stores it in uppercase', function (): void {
    registrarse(['tipo_documento' => 'pasaporte', 'numero_documento' => 'ab123456'])->assertHasNoErrors();

    expect(Persona::query()->sole()->numero_documento)->toBe('AB123456');
});

it('blocks the twenty-first registration attempt from the same address within a minute', function (): void {
    foreach (range(1, 20) as $intento) {
        registrarse(['numero_documento' => '1234567']);
    }

    $componente = registrarse();

    expect($componente->errors()->first('registro'))->toStartWith('Demasiados intentos. Vuelve a intentarlo en')
        ->and(User::query()->count())->toBe(0);
});

it('sends signed-in users away from the registration form', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('estudiante.registro'))
        ->assertRedirect(route('estudiante.inicio'));
});

it('links a CILN record whose email was typed with capitals and spaces', function (): void {
    $persona = Persona::factory()->create(['numero_documento' => '41234567', 'correo' => ' Rosa@Correo.TEST ']);

    registrarse()->assertHasNoErrors();

    expect($persona->refresh()->user_id)->not->toBeNull();
});
