<?php

declare(strict_types=1);

use App\Modules\Tramites\Livewire\PortadaPublica;
use Illuminate\Support\Facades\Route;

Route::livewire('/', PortadaPublica::class)->name('portada');
