<?php

declare(strict_types=1);

namespace App\Shared\Auth\Exceptions;

use Illuminate\Validation\ValidationException;

// Wrong e-mail or password, told apart from the attempt limit by its type rather than by its message.
final class CredencialesIncorrectas extends ValidationException {}
