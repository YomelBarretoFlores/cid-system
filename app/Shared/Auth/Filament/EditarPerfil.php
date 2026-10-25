<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;

// Name and e-mail come from the CILN's records, so staff only change their password here.
final class EditarPerfil extends EditProfile
{
    protected function getNameFormComponent(): TextInput
    {
        return TextInput::make('name')
            ->label(__('filament-panels::auth/pages/edit-profile.form.name.label'))
            ->disabled()
            ->dehydrated(false);
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/edit-profile.form.email.label'))
            ->disabled()
            ->dehydrated(false);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()
            ->visible(fn (Get $get): bool => filled($get('password')));
    }
}
