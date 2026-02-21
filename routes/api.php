<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Hosts\HostController;
use App\Http\Controllers\Api\Operations\OperationController;
use Illuminate\Support\Facades\Route;

Route::prefix('hosts')
    ->group(function () {
        Route::post('/', [HostController::class, 'create']);
        Route::get('/', [HostController::class, 'list']);
        Route::whereUuid('hostId')
            ->middleware([
                'auth:sanctum',
                'ability:rename-host',
                'rate-limiter'
            ])->patch('/{hostId}/rename', [HostController::class, 'rename']);
    });

Route::prefix('operations')
    ->group(function () {
        Route::whereUuid('operationId')
            ->get('/{operationId}', [OperationController::class, 'getById']);
    });

Route::prefix('auth')
    ->group(function () {
        Route::post('/', [AuthController::class, 'token']);
    });

