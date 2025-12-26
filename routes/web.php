<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('CommentsPage'));

// SPA fallback, but DO NOT catch /api, /storage, /docs, /_debugbar
Route::get('/{any}', fn () => Inertia::render('CommentsPage'))
    ->where('any', '^(?!api|storage|docs|_debugbar).*$');

