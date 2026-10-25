<?php

declare(strict_types=1);

namespace App\Shared\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

// Sent by the queue so a slow mail server never makes the user wait; the Spanish text comes from AuthServiceProvider.
#[Backoff([30, 120])]
#[Tries(3)]
final class CorreoDeRestablecimiento extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(string $token)
    {
        parent::__construct($token);
        $this->afterCommit();
    }
}
