<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('CommentsPage'));
Route::get('/{any}', fn () => Inertia::render('CommentsPage'))->where('any', '.*');
