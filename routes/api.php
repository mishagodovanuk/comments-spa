<?php
use App\Http\Controllers\Api\CommentController;
use Illuminate\Support\Facades\Route;

Route::prefix('comments')->group(function () {
    Route::middleware('throttle:api.comments.read')->group(function () {
    Route::get('/', [CommentController::class, 'index']);
    Route::get('/search', [CommentController::class, 'search']);
    });
    
    Route::middleware(['web', 'throttle:api.comments.create'])->group(function () {
        Route::post('/', [CommentController::class, 'store']);
        Route::post('/preview', [CommentController::class, 'preview']);
    });
});

Route::middleware('throttle:api.captcha')->group(function () {
Route::get('/captcha', [CommentController::class, 'captcha']);
});
