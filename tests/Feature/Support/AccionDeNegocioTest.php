<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\AuthenticationException;

it('runs the operation as the signed-in user and confirms success', function (): void {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $recibido = null;

    AccionDeNegocio::ejecutar(Action::make('probar'), function (User $actual) use (&$recibido): void {
        $recibido = $actual;
    }, 'Operación realizada');

    expect($recibido?->is($usuario))->toBeTrue();
    Notification::assertNotified('Operación realizada');
});

it('shows a broken business rule to the user and keeps the action open', function (): void {
    $this->actingAs(User::factory()->create());

    expect(fn () => AccionDeNegocio::ejecutar(
        Action::make('probar'),
        fn (): never => throw new ReglaDeNegocioIncumplida('Un pago Validado no puede pasar a Rechazado.'),
        'Operación realizada',
    ))->toThrow(Halt::class);

    Notification::assertNotified('Un pago Validado no puede pasar a Rechazado.');
    Notification::assertNotNotified('Operación realizada');
});

it('refuses to run without a signed-in user', function (): void {
    expect(fn () => AccionDeNegocio::ejecutar(Action::make('probar'), fn (): null => null, 'Operación realizada'))
        ->toThrow(AuthenticationException::class);
});
