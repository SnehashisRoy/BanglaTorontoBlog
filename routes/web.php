<?php

use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Blog\CompanyController;
use App\Http\Controllers\Blog\PostController as BlogPostController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Vendor\ProductSuggestionController;
use App\Http\Controllers\Vendor\RegistrationController as VendorRegistrationController;
use App\Http\Controllers\Vendor\ShopController as VendorShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Product marketplace — vendor shops and their products
Route::prefix('shops')->name('shops.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::get('/{vendor}', [ShopController::class, 'show'])->name('show');
    Route::get('/{vendor}/{product}', [ProductController::class, 'show'])->name('products.show');
});

// Become a vendor — any logged-in user
Route::middleware('auth')->group(function () {
    Route::get('/vendor/register', [VendorRegistrationController::class, 'create'])->name('vendor.register');
    Route::post('/vendor/register', [VendorRegistrationController::class, 'store'])->name('vendor.register.store');
});

// Vendor dashboard — requires login + an owned vendor shop
Route::prefix('vendor')->name('vendor.')->middleware(['auth', 'vendor'])->group(function () {
    Route::get('/', [VendorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/shop', [VendorShopController::class, 'edit'])->name('shop.edit');
    Route::put('/shop', [VendorShopController::class, 'update'])->name('shop.update');
    Route::post('/products/suggest-description', [ProductSuggestionController::class, 'store'])->name('products.suggest-description');
    Route::resource('products', VendorProductController::class)->except(['show']);
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

    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', [AdminVendorController::class, 'index'])->name('index');
        Route::patch('/{vendor}/approve', [AdminVendorController::class, 'approve'])->name('approve');
        Route::patch('/{vendor}/mark-pending', [AdminVendorController::class, 'markPending'])->name('mark-pending');
    });
});

require __DIR__.'/settings.php';
