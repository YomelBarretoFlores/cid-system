<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EmitirTokenDeApiRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // An email or the username of a staff-created teacher account.
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'dispositivo' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => trim($this->input('email'))]);
        }
    }
}
