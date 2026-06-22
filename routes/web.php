<?php

use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Blog\CompanyController;
use App\Http\Controllers\Blog\PostController as BlogPostController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Redirect bare /blog to the default English version
Route::redirect('/blog', '/en/blog');

// Public blog routes — locale-prefixed, sets app locale via SetLocale middleware
Route::prefix('{locale}')
    ->where(['locale' => 'en|bn'])
    ->middleware('setlocale')
    ->name('blog.')
    ->group(function () {
        Route::get('/blog', [BlogPostController::class, 'index'])->name('index');
        Route::get('/blog/{slug}', [BlogPostController::class, 'show'])->name('show');
    });

// Community businesses directory
Route::prefix('businesses')->name('companies.')->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('index');
    Route::get('/{slug}', [CompanyController::class, 'category'])->name('category');
    Route::get('/{slug}/{companySlug}', [CompanyController::class, 'show'])->name('show');
});

// Admin routes — requires login + admin flag (no locale prefix needed)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::resource('posts', AdminPostController::class)->except(['show']);
});

require __DIR__.'/settings.php';
