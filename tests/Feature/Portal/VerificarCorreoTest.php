<?php

declare(strict_types=1);

use App\Shared\Auth\Livewire\VerificarCorreo;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function enlaceDeVerificacion(User $user, ?string $correo = null): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->getKey(),
        'hash' => sha1($correo ?? $user->email),
    ]);
}

beforeEach(fn () => Notification::fake());

it('resends the verification email in Spanish at most once a minute', function (): void {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    Livewire::test(VerificarCorreo::class)->call('reenviar')->assertSee('Te enviamos un nuevo enlace de verificación.');
    Livewire::test(VerificarCorreo::class)->call('reenviar')->assertSee('Espera un minuto antes de pedir otro enlace.');

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    Notification::assertSentTo($user, VerifyEmail::class, fn (VerifyEmail $aviso): bool => $aviso->toMail($user)->subject === 'Verifica tu correo del CILN');
});

it('verifies the email from the signed link', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(enlaceDeVerificacion($user))
        ->assertRedirect(route('estudiante.inicio'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a link made for another email', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(enlaceDeVerificacion($user, 'otro@correo.test'))
        ->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('sends a verified user from the notice to the portal', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('verification.notice'))
        ->assertRedirect(route('estudiante.inicio'));
});
