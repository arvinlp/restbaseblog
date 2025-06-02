<?php

use GuzzleHttp\Exception\ServerException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Psy\Exception\FatalErrorException;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        using: function () {
            Route::middleware('api')
                ->namespace('App\Http\Controllers')
                ->prefix('api/v1/')
                ->name('api.')
                ->group(base_path('routes/api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 401
            ], 401);
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {

            return response()->json([
                'message' =>  $e->getMessage(),
                'status' => 404
            ], 404);
        });
        $exceptions->render(function (ServerException $e, Request $request) {

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        });
        $exceptions->render(function (InternalErrorException $e, Request $request) {

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        });
        $exceptions->render(function (FatalErrorException $e, Request $request) {

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        });
    })->create();
