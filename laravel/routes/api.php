<?php

use Illuminate\Support\Facades\Route;
use NexDeve\NexPush\Http\Controllers\NexPushController;

Route::prefix(config('nexpush.route_prefix', 'api/nexpush'))
    ->middleware(config('nexpush.middleware', ['api']))
    ->group(function () {
        Route::post('register',              [NexPushController::class, 'register']);
        Route::post('send',                  [NexPushController::class, 'send']);
        Route::post('send-to-user/{userId}', [NexPushController::class, 'sendToUser']);
        Route::post('broadcast',             [NexPushController::class, 'broadcast']);
        Route::delete('unregister',          [NexPushController::class, 'unregister']);
    });
