<?php

declare(strict_types=1);

namespace App\Shared\Auth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

// Sent by the queue so a slow mail server never makes the student wait; the Spanish text comes from AuthServiceProvider.
#[Backoff([30, 120])]
#[Tries(3)]
final class CorreoDeVerificacion extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }
}
