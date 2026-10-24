<?php

declare(strict_types=1);

namespace App\Shared\Support\Livewire;

trait MensajesDeValidacion
{
    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'required' => 'Completa el campo :attribute.',
            'email' => 'Escribe un correo válido.',
            'string' => 'El campo :attribute no es válido.',
            'max' => 'El campo :attribute admite como máximo :max caracteres.',
            'confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra y un número.',
            'password.numbers' => 'La contraseña debe incluir al menos una letra y un número.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'email' => 'correo',
            'password' => 'contraseña',
        ];
    }
}
