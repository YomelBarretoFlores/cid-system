<?php

declare(strict_types=1);

namespace App\Shared\Ui;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

// Drawn here instead of Filament's ui-avatars.com default: no staff name leaves the server and the CSP allows only local images.
final class AvatarDeIniciales implements AvatarProvider
{
    private const string FONDO = '#032b45';

    public function get(Model|Authenticatable $record): string
    {
        $iniciales = e($this->iniciales(Filament::getNameForDefaultAvatar($record)));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="'.self::FONDO.'"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="26" font-weight="600">'.$iniciales.'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function iniciales(string $nombre): string
    {
        return str($nombre)->squish()->explode(' ')
            ->take(2)
            ->map(fn (string $parte): string => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->join('');
    }
}
