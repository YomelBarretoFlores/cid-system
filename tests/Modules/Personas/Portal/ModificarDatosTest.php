<?php

declare(strict_types=1);

use App\Modules\Personas\Livewire\ModificarDatos;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Notifications\AvisoDeCambioDeContrasena;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->alumno = User::factory()->create(['email' => 'rosa@example.test', 'password' => 'clave-actual-1']);
    $this->ficha = Persona::factory()->create([
        'user_id' => $this->alumno->id, 'nombres' => 'Rosa Elena', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani',
        'numero_documento' => '41234567', 'fecha_nacimiento' => '2001-05-14', 'correo' => 'rosa@example.test', 'celular' => '987654321', 'direccion' => null,
    ]);
});

it('shows the names, document, birth date and email as read-only data that the CILN changes', function (): void {
    $this->actingAs($this->alumno)->get(route('estudiante.perfil.datos'))
        ->assertOk()
        ->assertSeeInOrder(['QUISPE MAMANI ROSA ELENA', 'DNI 41234567', 'Ver perfil',
            'Datos personales', 'Estos datos los actualiza el CILN', '14/05/2001', 'rosa@example.test', 'Dirección', 'Celular', 'Guardar datos',
            'Cambio de contraseña', 'Ingresa primero tu contraseña actual', 'Contraseña actual', 'Nueva contraseña', 'Confirma la nueva contraseña', 'Cambiar contraseña'])
        ->assertSee(route('estudiante.inicio'))
        ->assertDontSee('wire:model="nombres"', false)
        ->assertDontSee('wire:model="email"', false);
});

it('saves the normalized phone and the address without touching the names, document or email, and audits the change', function (): void {
    $auditados = DB::table('aud_registros')->where('tabla', 'per_personas')->count();

    Livewire::actingAs($this->alumno)->test(ModificarDatos::class)
        ->assertSet('celular', '987654321')
        ->set('celular', ' 912 345-678 ')
        ->set('direccion', '  Jr. Los Pinos 123, Huaraz ')
        ->call('guardarContacto')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.perfil.datos'));

    $persona = $this->ficha->fresh();
    expect($persona->celular)->toBe('912345678')
        ->and($persona->direccion)->toBe('Jr. Los Pinos 123, Huaraz')
        ->and([$persona->nombres, $persona->apellido_paterno, $persona->numero_documento, $persona->correo, $persona->fecha_nacimiento?->toDateString()])
        ->toBe(['Rosa Elena', 'Quispe', '41234567', 'rosa@example.test', '2001-05-14'])
        ->and(DB::table('aud_registros')->where('tabla', 'per_personas')->count())->toBe($auditados + 1)
        ->and(DB::table('aud_registros')->where('tabla', 'per_personas')->latest('id')->value('operacion'))->toBe('UPDATE');
});

it('clears the phone and the address when the student leaves them empty', function (): void {
    $this->ficha->update(['direccion' => 'Av. Centenario 200']);

    Livewire::actingAs($this->alumno)->test(ModificarDatos::class)
        ->set('celular', '')
        ->set('direccion', '   ')
        ->call('guardarContacto')
        ->assertHasNoErrors();

    expect($this->ficha->fresh()->only('celular', 'direccion'))->toBe(['celular' => null, 'direccion' => null]);
});

it('rejects an invalid phone or a too long address and saves nothing', function (string $campo, string $valor, string $mensaje): void {
    Livewire::actingAs($this->alumno)->test(ModificarDatos::class)
        ->set($campo, $valor)
        ->call('guardarContacto')
        ->assertHasErrors([$campo])
        ->assertSee($mensaje);

    expect($this->ficha->fresh()->only('celular', 'direccion'))->toBe(['celular' => '987654321', 'direccion' => null]);
})->with([
    'phone with letters' => ['celular', '98765abc1', 'Escribe un celular válido, solo con números.'],
    'phone too short' => ['celular', '12345', 'Escribe un celular válido, solo con números.'],
    'address over 200 characters' => ['direccion', str_repeat('a', 201), 'El campo dirección admite como máximo 200 caracteres.'],
]);

it('refuses a password change with a wrong current password and keeps the old one', function (): void {
    Notification::fake();

    Livewire::actingAs($this->alumno)->test(ModificarDatos::class)
        ->set('password_actual', 'otra-clave-9')
        ->set('password', 'clave-nueva-2')
        ->set('password_confirmation', 'clave-nueva-2')
        ->call('cambiarContrasena')
        ->assertHasErrors(['password_actual'])
        ->assertSee('Tu contraseña actual no es correcta.');

    expect(Hash::check('clave-actual-1', $this->alumno->fresh()->password))->toBeTrue();
    Notification::assertNothingSent();
});

it('rejects a new password that is weak or does not match its confirmation', function (string $nueva, string $confirmacion, string $mensaje): void {
    Livewire::actingAs($this->alumno)->test(ModificarDatos::class)
        ->set('password_actual', 'clave-actual-1')
        ->set('password', $nueva)
        ->set('password_confirmation', $confirmacion)
        ->call('cambiarContrasena')
        ->assertHasErrors(['password'])
        ->assertSee($mensaje);

    expect(Hash::check('clave-actual-1', $this->alumno->fresh()->password))->toBeTrue();
})->with([
    'too short' => ['abc12', 'abc12', 'La contraseña debe tener al menos 8 caracteres.'],
    'not confirmed' => ['clave-nueva-2', 'clave-nueva-3', 'Las contraseñas no coinciden.'],
]);

it('changes the password, keeps this session, logs out the other devices and tells the student by e-mail', function (): void {
    Notification::fake();
    Event::fake([OtherDeviceLogout::class]);
    $tokenAnterior = $this->alumno->remember_token;
    $hashAnterior = $this->alumno->password;

    Livewire::actingAs($this->alumno)->test(ModificarDatos::class)
        ->set('password_actual', 'clave-actual-1')
        ->set('password', 'clave-nueva-2')
        ->set('password_confirmation', 'clave-nueva-2')
        ->call('cambiarContrasena')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.perfil.datos'));

    $alumno = $this->alumno->fresh();
    expect(Hash::check('clave-nueva-2', $alumno->password))->toBeTrue()
        ->and($alumno->remember_token)->not->toBe($tokenAnterior)
        ->and(session('password_hash_web'))->toBe(Auth::guard('web')->hashPasswordForCookie($alumno->password));
    Event::assertDispatched(OtherDeviceLogout::class, fn (OtherDeviceLogout $evento): bool => $evento->user->is($alumno));
    Notification::assertSentTo($alumno, AvisoDeCambioDeContrasena::class);

    $this->actingAs($alumno)->get(route('estudiante.perfil.datos'))->assertOk();
    $this->actingAs($alumno)->withSession(['password_hash_web' => Auth::guard('web')->hashPasswordForCookie($hashAnterior)])
        ->get(route('estudiante.perfil.datos'))
        ->assertRedirect(route('ingresar'));
});

it('writes the password change notice in Spanish, in the tú form, and sends it through the queue after commit', function (): void {
    $aviso = new AvisoDeCambioDeContrasena;
    $correo = $aviso->toMail($this->alumno);

    expect($aviso)->toBeInstanceOf(ShouldQueue::class)
        ->and($aviso->afterCommit)->toBeTrue()
        ->and($aviso->via($this->alumno))->toBe(['mail'])
        ->and($correo->subject)->toBe('Tu contraseña del CILN cambió')
        ->and(implode(' ', $correo->introLines))->toContain('Si no fuiste tú');
});

it('limits the password change attempts of an account', function (): void {
    $componente = Livewire::actingAs($this->alumno)->test(ModificarDatos::class)
        ->set('password_actual', 'otra-clave-9')
        ->set('password', 'clave-nueva-2')
        ->set('password_confirmation', 'clave-nueva-2');

    foreach (range(1, 5) as $intento) {
        $componente->call('cambiarContrasena')->assertHasErrors(['password_actual']);
    }

    $componente->set('password_actual', 'clave-actual-1')
        ->call('cambiarContrasena')
        ->assertHasErrors(['password_actual'])
        ->assertSee('Demasiados intentos.');

    expect(Hash::check('clave-actual-1', $this->alumno->fresh()->password))->toBeTrue();
    RateLimiter::clear("cambio-de-contrasena|{$this->alumno->id}");
});

it('limits how often an account saves its contact data', function (): void {
    $componente = Livewire::actingAs($this->alumno)->test(ModificarDatos::class);

    foreach (range(1, 10) as $intento) {
        $componente->set('direccion', "Jr. Simón Bolívar {$intento}")->call('guardarContacto')->assertHasNoErrors();
    }

    $componente->set('direccion', 'Av. Luzuriaga 999')
        ->call('guardarContacto')
        ->assertHasErrors(['contacto'])
        ->assertSee('Demasiados intentos.');

    expect($this->ficha->fresh()->direccion)->toBe('Jr. Simón Bolívar 10');
});
