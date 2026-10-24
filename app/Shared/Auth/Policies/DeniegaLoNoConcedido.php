<?php

declare(strict_types=1);

namespace App\Shared\Auth\Policies;

// The panel runs in strict authorization mode, so every ability Filament may ask for must be declared.
trait DeniegaLoNoConcedido
{
    public function update(): bool
    {
        return false;
    }

    public function delete(): bool
    {
        return false;
    }

    public function deleteAny(): bool
    {
        return false;
    }

    public function restore(): bool
    {
        return false;
    }

    public function restoreAny(): bool
    {
        return false;
    }

    public function forceDelete(): bool
    {
        return false;
    }

    public function forceDeleteAny(): bool
    {
        return false;
    }

    public function replicate(): bool
    {
        return false;
    }

    public function reorder(): bool
    {
        return false;
    }

    public function associate(): bool
    {
        return false;
    }

    public function attach(): bool
    {
        return false;
    }

    public function detach(): bool
    {
        return false;
    }

    public function detachAny(): bool
    {
        return false;
    }

    public function dissociate(): bool
    {
        return false;
    }

    public function dissociateAny(): bool
    {
        return false;
    }
}
