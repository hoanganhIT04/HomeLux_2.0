<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;

use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Pages
|--------------------------------------------------------------------------
*/
Route::get('/shop', [ProductController::class, 'shop'])
    ->name('shop');

Route::get('/detail/{id}', [ProductController::class, 'show'])->name('detail');

Route::get('/wishlist', function () {
    return Inertia::render('Wishlist');
})->name('wishlist');

/*
|--------------------------------------------------------------------------
| Category Routes
|--------------------------------------------------------------------------
*/
Route::get('/categories', [CategoryController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Product Routes
|--------------------------------------------------------------------------
*/
Route::get('/home', [ProductController::class, 'index'])->name('home');
Route::get('/products/{id}/related', [ProductController::class, 'relatedProducts']);

/*
|--------------------------------------------------------------------------
| Cart Routes (AUTH REQUIRED)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])
        ->name('cart.index');

    Route::post('/cart/add', [CartController::class, 'add'])
        ->name('cart.add');

    Route::post('/cart/update', [CartController::class, 'update'])
        ->name('cart.update');

    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])
        ->name('cart.remove');
    Route::get('/cart/count', [CartController::class, 'count'])
        ->name('cart.count');
});

/*
|--------------------------------------------------------------------------
| Wisshlist Routes
|--------------------------------------------------------------------------
*/

Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])
    ->middleware('auth')
    ->name('wishlist.toggle');

Route::get('/wishlist', [WishlistController::class, 'index'])
    ->middleware('auth')
    ->name('wishlist');

Route::get('/wishlist/count', [WishlistController::class, 'count'])
    ->middleware('auth')
    ->name('wishlist.count');
/*
|--------------------------------------------------------------------------
| Checkout Routes và Payment Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;

Route::middleware('auth')->group(function () {

    Route::get('/checkout', [CheckoutController::class, 'index'])
        ->name('checkout');

    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->name('checkout.store');

    // Route::get('/payment/momo/create/{order_id}', [PaymentController::class, 'createMomo'])
    //     ->name('payment.momo.create');
    Route::get('/payment/momo/create', [PaymentController::class, 'createMomo'])
        ->name('payment.momo.create');

    Route::get('/payment/momo/callback', [PaymentController::class, 'momoCallback'])
        ->name('payment.momo.callback');
});

/*
|--------------------------------------------------------------------------
| Order Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\OrderController;

Route::middleware('auth')->group(function () {
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');

    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->name('orders.cancel');
});

/*
|--------------------------------------------------------------------------
| Authentication & OTP
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Auth\RegisteredUserController;

Route::get('/otp-verify', [RegisteredUserController::class, 'verify_form'])
    ->name('verification.otp');

Route::post('/otp-verify', [RegisteredUserController::class, 'verify'])
    ->name('verification.verify_otp');

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\PasswordController;


Route::middleware('auth')->group(function () {

    // Route::get('/account', function () {
    //     return Inertia::render('Profile/Account');
    // })->name('account');
    Route::get('/account', [ProfileController::class, 'account'])
        ->name('account');


    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::put('/profile/password', [PasswordController::class, 'update'])
        ->name('password.update');
});
/*
|--------------------------------------------------------------------------
| PDF invoice Routes
|--------------------------------------------------------------------------
*/
Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])
    ->name('orders.invoice');

Route::get('/orders/{order}/invoice/pdf', [OrderController::class, 'invoicePdf'])
    ->name('orders.invoice.pdf');


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\OrderController as AdminOrderController;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->as('admin.') // 👈 QUAN TRỌNG
    ->group(function () {

        Route::get(
            '/dashboard',
            fn() =>
            Inertia::render('Admin/Dashboard')
        )->name('dashboard');

        Route::resource('products', \App\Http\Controllers\Admin\ProductController::class);

        Route::get(
            '/categories',
            fn() =>
            Inertia::render('Admin/Categories/Index')
        )->name('categories');

        Route::get(
            '/orders',
            fn() =>
            Inertia::render('Admin/Orders/Index')
        )->name('orders');

        Route::patch(
            '/orders/{order}/status',
            [AdminOrderController::class, 'updateStatus']
        )->name('orders.updateStatus');

        Route::get(
            '/users',
            fn() =>
            Inertia::render('Admin/Users/Index')
        )->name('users');
    });

/*
|--------------------------------------------------------------------------
| User Address Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\UserAddressController;

Route::middleware('auth')->group(function () {

    Route::get('/account/addresses', [UserAddressController::class, 'index'])
        ->name('addresses.index');

    Route::post('/account/addresses', [UserAddressController::class, 'store'])
        ->name('addresses.store');

    Route::put('/account/addresses/{id}', [UserAddressController::class, 'update'])
        ->name('addresses.update');

    Route::delete('/account/addresses/{id}', [UserAddressController::class, 'destroy'])
        ->name('addresses.destroy');
});

/*
|--------------------------------------------------------------------------
| Reviews Routes
|--------------------------------------------------------------------------
*/
Route::get('/products/{product}/can-review', [ProductController::class, 'canReview'])
    ->middleware('auth');

Route::get('/products/{product}/reviews', [ProductController::class, 'reviews']);

Route::post('/reviews', [ProductController::class, 'storeReview'])
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
