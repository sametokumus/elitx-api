<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Shop\AuthController;
use App\Http\Controllers\Api\Shop\ResetPasswordController;
use App\Http\Controllers\Api\Shop\UserController;
use App\Http\Controllers\Api\Shop\ProductController;
use App\Http\Controllers\Api\Shop\CategoryController;
use App\Http\Controllers\Api\Shop\CommentController;
use App\Http\Controllers\Api\Shop\SupportController;
use App\Http\Controllers\Api\Shop\OrderController;
use App\Http\Controllers\Api\Shop\OrderStatusController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('auth/register', [AuthController::class, 'register']);
Route::get('auth/verify/{token}', [AuthController::class, 'verify'])->name('verification.verify');
Route::post('auth/resend-verify-email', [AuthController::class, 'resend']);

Route::get('password/find/{token}', [ResetPasswordController::class, 'find']);
Route::post('password/resetPassword', [ResetPasswordController::class, 'store']);
Route::post('password/newPassword',[ResetPasswordController::class, 'newPassword']);



Route::middleware(['auth:sanctum', 'type.shop'])->group(function (){

    //User & Auth
    Route::get('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/registerDocument', [AuthController::class, 'registerDocument']);
    Route::post('auth/registerAllDocument', [AuthController::class, 'registerAllDocument']);
    Route::get('auth/registerComplete', [AuthController::class, 'registerComplete']);
    Route::get('user/checkRegisterDocuments', [UserController::class, 'checkRegisterDocuments']);
    Route::get('user/getRegisterDocuments', [UserController::class, 'getRegisterDocuments']);
    Route::get('user/deleteRegisterDocument/{id}', [UserController::class, 'deleteRegisterDocument']);
    Route::get('user/getShopProfile', [UserController::class, 'getShopProfile']);

    //Product
    Route::post('product/addProduct', [ProductController::class, 'addProduct']);
    Route::post('product/updateProduct/{id}', [ProductController::class, 'updateProduct']);
    Route::get('product/getProducts', [ProductController::class, 'getProducts']);
    Route::get('product/getProductById/{id}', [ProductController::class, 'getProductById']);

    //Comment
    Route::post('product/addProductCommentAnswer', [CommentController::class, 'addProductCommentAnswer']);
    Route::get('product/getCommentsByProductId/{product_id}', [CommentController::class, 'getCommentsByProductId']);
    Route::get('product/getCommentedProducts', [CommentController::class, 'getCommentedProducts']);

    //Category
    Route::get('category/getCategories', [CategoryController::class, 'getCategories']);

    //Support
    Route::get('support/getSupportCategories', [SupportController::class, 'getSupportCategories']);
    Route::post('support/addSupportRequest', [SupportController::class, 'addSupportRequest']);
    Route::post('support/addSupportMessage', [SupportController::class, 'addSupportMessage']);
    Route::get('support/getSupportList', [SupportController::class, 'getSupportList']);
    Route::get('support/getSupportConversation/{request_id}', [SupportController::class, 'getSupportConversation']);

    //Order
    Route::get('/order/getOnGoingOrders',[OrderController::class,'getOnGoingOrders']);
    Route::get('/order/getCompletedOrders',[OrderController::class,'getCompletedOrders']);
    Route::get('/order/getOrderById/{order_id}',[OrderController::class,'getOrderById']);
    Route::get('/order/getOrderStatusHistoriesById/{id}', [OrderController::class, 'getOrderStatusHistoriesById']);
    Route::get('/order/getUpdateProductStatus/{order_id}/{product_id}/{old_status_id}/{status_id}', [OrderController::class, 'getUpdateProductStatus']);
    Route::post('/order/addShippingInfo', [OrderController::class, 'addShippingInfo']);

    //OrderStatus
    Route::get('/orderStatus/getOrderStatuses', [OrderStatusController::class, 'getOrderStatuses']);

    //BankInfo
    Route::post('/account/addBankInfo', [UserController::class, 'addBankInfo']);
    Route::post('/account/updateBankInfo/{id}', [UserController::class, 'updateBankInfo']);
    Route::get('/account/getBankInfos',[UserController::class,'getBankInfos']);
    Route::get('/account/getBankInfoById/{id}',[UserController::class,'getBankInfoById']);

});



