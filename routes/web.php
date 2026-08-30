<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', static fn () => response()->json([
    'service' => 'Затея — платформа промо-механик',
    'api' => url('/api/v1'),
    'openapi' => 'docs/openapi/openapi.yaml',
    'health' => url('/up'),
]));
