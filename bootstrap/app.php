<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Ошибка аутентификации
        $exceptions->renderable(function (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        });

        // Ошибка авторизации
        $exceptions->renderable(function (AccessDeniedHttpException $e) {
            return response()->json([
                'message' => 'Недостаточно прав',
            ], 403);
        });

        // Ресурс не найден
        $exceptions->renderable(function (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Ресурс не найден',
            ], 404);
        });

        // Конфликт
        $exceptions->renderable(function (ConflictHttpException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        });

        // Обработка валидации
        $exceptions->renderable(function (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status);
        });

        //Другие непредвиденные ошибки
        $exceptions->renderable(function (Throwable $e) {
            if (!config('app.debug')) {
                return response()->json([
                    'message' => 'Что-то пошло не так, попробуйте позднее',
                ], 500);
            }
        });

    })->create();
