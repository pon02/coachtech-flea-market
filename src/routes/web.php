<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShippingAddressController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ========================================
// 認証不要ルート（ゲストアクセス可能）
// ========================================

// トップページ（商品一覧・検索機能）
Route::get('/', [ItemController::class, 'index'])->name('home');
Route::get('/search', [ItemController::class, 'index'])->name('search');

// 商品詳細ページ
Route::get('/item/{id}', [ItemController::class, 'show'])->name('items.show');

// ========================================
// 認証必要ルート
// ========================================

Route::middleware(['auth'])->group(function () {

    // ----------------------------------------
    // マイページ・プロフィール関連（優先配置）
    // ----------------------------------------
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');
    Route::get('/mypage/profile', [MypageController::class, 'showProfile'])->name('mypage.profile');
    Route::post('/mypage/profile', [MypageController::class, 'updateProfile'])->name('mypage.profile.update');
    Route::delete('/mypage/profile/image', [MypageController::class, 'deleteProfileImage'])->name('mypage.profile.delete-image');

    // ----------------------------------------
    // 商品出品機能
    // ----------------------------------------
    Route::get('/sell', [ItemController::class, 'create'])->name('items.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store');

    // ----------------------------------------
    // 購入・決済機能
    // ----------------------------------------
    Route::get('/purchase/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/purchase/{id}', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/purchase/complete/{item}', [OrderController::class, 'completeStripePayment'])->name('orders.completeStripePayment');

    // 配送先住所変更
    Route::get('/purchase/address/{id}', [ShippingAddressController::class, 'edit'])->name('shipping.edit');
    Route::post('/purchase/address/{id}', [ShippingAddressController::class, 'update'])->name('shipping.update');

    // Stripe決済
    Route::get('/payment/checkout/{item}', [PaymentController::class, 'createCheckoutSession'])->name('payment.checkout');
    Route::get('/payment/success/{item}', [PaymentController::class, 'success'])->name('payment.success');

    // ----------------------------------------
    // いいね機能
    // ----------------------------------------
    Route::post('/like/{product}', [LikeController::class, 'toggleLike'])->name('likes.toggle');

    // ========================================
    // コメント機能（authミドルウェアで保護）
    // ========================================
    Route::post('/item/{id}/comment', [CommentController::class, 'store'])->name('comments.store');
});



// ========================================
// メール認証関連（Fortifyが自動処理）
// ========================================
// /email/verify - メール認証画面
// /email/verification-notification - 認証メール再送