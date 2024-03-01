<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Shop\AuthController;
use App\Http\Controllers\Api\Shop\ResetPasswordController;
use App\Http\Controllers\Api\Shop\UserController;
use App\Http\Controllers\Api\Shop\ProductController;

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

    //Product
    Route::post('shop/addProduct', [ProductController::class, 'addProduct']);

});



