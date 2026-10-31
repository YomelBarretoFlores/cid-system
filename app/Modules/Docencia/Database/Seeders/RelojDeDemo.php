<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use Carbon\CarbonImmutable;
use Closure;

// The Actions read the current time, so the demo moves the clock to each step and always puts it back.
final class RelojDeDemo
{
    /**
     * @template T
     *
     * @param  Closure(): T  $paso
     * @return T
     */
    public static function en(CarbonImmutable $momento, Closure $paso): mixed
    {
        $antes = CarbonImmutable::getTestNow();
        CarbonImmutable::setTestNow($momento);

        try {
            return $paso();
        } finally {
            CarbonImmutable::setTestNow($antes);
        }
    }
}
