<?php

declare(strict_types=1);

Route::group([
    'middleware' => [],
    'prefix' => 'v1',
], function () {
    Route::prefix('/test')->group(function () {
        Route::get('/', [\App\Http\Api\Controllers\TestController::class, 'index']);

        Route::middleware(\App\Http\Middleware\RequireDebugToken::class)->group(function () {
            Route::get('/{id}', [\App\Http\Api\Controllers\TestController::class, 'show']);
        });
    });

    Route::prefix('/user')->group(function () {
        Route::post('/sign-up', [\App\Http\API\v1\Controllers\UserController::class, 'signUp']);
        Route::post('/sign-in', [\App\Http\API\v1\Controllers\UserController::class, 'signIn']);
        Route::post('/logout', [\App\Http\API\v1\Controllers\UserController::class, 'logout']);

        Route::middleware(\App\Http\Middleware\HandleRefreshJwtToken::class)->group(function () {
            Route::post('/refresh-token', [\App\Http\API\v1\Controllers\UserController::class, 'refreshToken']);
        });
    });

    Route::middleware(\App\Http\Middleware\HandleJwtToken::class)->group(function () {
        Route::prefix('/user')->group(function () {
            Route::get('/profile', [\App\Http\API\v1\Controllers\UserController::class, 'profile']);
        });

        Route::prefix('/lists')->group(function () {
            Route::get('/', [\App\Http\API\v1\Controllers\ListController::class, 'index']);
            Route::post('/', [\App\Http\API\v1\Controllers\ListController::class, 'create']);
            Route::get('/delete-types/{id}', [\App\Http\API\v1\Controllers\ListController::class, 'deleteTypes']);
            Route::delete('/left/{id}', [\App\Http\API\v1\Controllers\ListController::class, 'left']);
            Route::get('/{id}', [\App\Http\API\v1\Controllers\ListController::class, 'view']);
            Route::put('/{id}', [\App\Http\API\v1\Controllers\ListController::class, 'update']);
            Route::delete('/{id}', [\App\Http\API\v1\Controllers\ListController::class, 'delete']);
        });

        Route::prefix('/list-items')->group(function () {
            Route::post('/', [\App\Http\API\v1\Controllers\ListItemController::class, 'create']);
            Route::put('/complete/{id}', [\App\Http\API\v1\Controllers\ListItemController::class, 'complete']);
            Route::put('/{id}', [\App\Http\API\v1\Controllers\ListItemController::class, 'update']);
            Route::delete('/{id}', [\App\Http\API\v1\Controllers\ListItemController::class, 'delete']);
        });
    });
});
