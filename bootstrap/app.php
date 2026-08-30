<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Shared\DomainException;
use Src\Presentation\Http\ApiExceptionPresenter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(static fn (UseCaseException $e) => (new ApiExceptionPresenter())->fromUseCase($e));
        $exceptions->render(static fn (DomainException $e) => (new ApiExceptionPresenter())->fromDomain($e));

        $exceptions->render(static function (ValidationException $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Проверьте правильность переданных полей.',
                    'fields' => $e->errors(),
                ],
            ], 422);
        });

        $exceptions->render(static function (AuthenticationException $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return response()->json([
                'error' => ['code' => 'unauthorized', 'message' => 'Требуется аутентификация.'],
            ], 401);
        });
    })->create();
