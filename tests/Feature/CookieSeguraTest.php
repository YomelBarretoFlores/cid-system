<?php

declare(strict_types=1);

function configuracionDeSesion(array $variables): array
{
    $anteriores = [];

    foreach ($variables as $nombre => $valor) {
        $anteriores[$nombre] = $_SERVER[$nombre] ?? null;
        unset($_SERVER[$nombre], $_ENV[$nombre]);

        if ($valor !== null) {
            $_SERVER[$nombre] = $valor;
        }
    }

    try {
        return require base_path('config/session.php');
    } finally {
        foreach ($anteriores as $nombre => $valor) {
            if ($valor === null) {
                unset($_SERVER[$nombre]);

                continue;
            }

            $_SERVER[$nombre] = $valor;
        }
    }
}

it('sends the session cookies only over https when the site address is https, even without the setting in the env file', function (): void {
    expect(configuracionDeSesion(['APP_URL' => 'https://ciln.unasam.edu.pe', 'SESSION_SECURE_COOKIE' => null])['secure'])->toBeTrue();
});

it('keeps the session cookies usable over plain http on a local address', function (): void {
    expect(configuracionDeSesion(['APP_URL' => 'http://localhost', 'SESSION_SECURE_COOKIE' => null])['secure'])->toBeFalse();
});

it('lets the env file still decide the secure flag explicitly', function (): void {
    expect(configuracionDeSesion(['APP_URL' => 'https://ciln.unasam.edu.pe', 'SESSION_SECURE_COOKIE' => 'false'])['secure'])->toBeFalse();
});
