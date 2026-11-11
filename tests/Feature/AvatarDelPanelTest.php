<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use App\Shared\Ui\AvatarDeIniciales;
use Filament\Facades\Filament;

it('draws the panel avatar locally from the user initials, without a third-party service', function (string $panel): void {
    $usuario = User::factory()->make(['name' => 'Administrador Demo']);

    Filament::setCurrentPanel($panel);
    $avatar = Filament::getUserAvatarUrl($usuario);

    expect($avatar)->toStartWith('data:image/svg+xml;base64,')
        ->not->toContain('ui-avatars.com')
        ->and(base64_decode(str($avatar)->after('base64,')->toString(), true))->toContain('>AD</text>');
})->with(['admin', 'docente']);

it('uses one initial for a single name and escapes the markup', function (): void {
    $avatar = (new AvatarDeIniciales)->get(User::factory()->make(['name' => '<b>ana']));

    expect(base64_decode(str($avatar)->after('base64,')->toString(), true))->toContain('>&lt;</text>')->not->toContain('<b>');
});
