<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concurrency\TruncatesAcademicOffer;
use Tests\TestCase;

require_once __DIR__.'/Concurrency/Helpers.php';

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Modules');

// Concurrent processes only see committed rows, so these tests cannot run inside a transaction.
pest()->extend(TestCase::class)
    ->use(TruncatesAcademicOffer::class)
    ->in('Concurrency');
