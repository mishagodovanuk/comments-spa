<?php
use App\Http\Controllers\Api\CommentController;
use Illuminate\Support\Facades\Route;

Route::prefix('comments')->group(function () {
    Route::get('/', [CommentController::class, 'index']);
    Route::get('/search', [CommentController::class, 'search']);
    Route::middleware('web')->group(function () {
        Route::post('/', [CommentController::class, 'store']);
        Route::post('/preview', [CommentController::class, 'preview']);
    });
});

Route::get('/captcha', [CommentController::class, 'captcha']);
