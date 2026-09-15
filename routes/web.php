<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\KnowledgeBaseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('knowledge-bases', KnowledgeBaseController::class);

    Route::post('/knowledge-bases/{knowledge_base}/documents', [DocumentController::class, 'store'])
        ->name('knowledge-bases.documents.store');
    Route::post('/knowledge-bases/{knowledge_base}/documents/{document}/reprocess', [DocumentController::class, 'reprocess'])
        ->name('knowledge-bases.documents.reprocess');
    Route::delete('/knowledge-bases/{knowledge_base}/documents/{document}', [DocumentController::class, 'destroy'])
        ->name('knowledge-bases.documents.destroy');
});
