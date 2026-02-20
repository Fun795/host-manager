<?php

use App\Http\Controllers\Api\Hosts\HostController;
use Illuminate\Support\Facades\Route;

Route::prefix('hosts')
    ->group(function () {
        Route::post('/', [HostController::class, 'create']);
        Route::get('/', [HostController::class, 'list']);
        Route::whereUuid('hostId')
            ->patch('/{hostId}/rename', [HostController::class, 'rename']);
    });

