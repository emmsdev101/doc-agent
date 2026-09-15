<?php

use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\WidgetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('widget.origin')->group(function () {
    Route::get('/widget/{widgetToken}', [WidgetController::class, 'show'])
        ->name('api.v1.widget.show');

    Route::post('/chat', [ChatController::class, 'store'])
        ->middleware('throttle:widget-chat')
        ->name('api.v1.chat');
});
