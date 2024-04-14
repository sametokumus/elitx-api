<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ResetPasswordController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ProductController;


use App\Http\Controllers\Api\V1\BankBinPairController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CarrierController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CimriController;
use App\Http\Controllers\Api\V1\CitiesController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\ContactRulesController;
use App\Http\Controllers\Api\V1\CountriesController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\CreditCardController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PopupController;
use App\Http\Controllers\Api\V1\ProductDocumentController;
use App\Http\Controllers\Api\V1\ProductTypeController;
use App\Http\Controllers\Api\V1\ProductVariationGroupTypeController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SeoController;
use App\Http\Controllers\Api\V1\SliderController;
use App\Http\Controllers\Api\V1\SubscribeController;
use App\Http\Controllers\Api\V1\TabController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\UserContactRulesController;
use App\Http\Controllers\Api\V1\UserDocumentChecksController;
use App\Http\Controllers\Api\V1\UserDocumentController;
use Illuminate\Support\Facades\Route;

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
Route::post('password/forgotPasswordByEmail', [ResetPasswordController::class, 'store']);
Route::post('password/reset',[ResetPasswordController::class, 'resetPassword']);



Route::middleware(['auth:sanctum', 'type.user'])->group(function (){

    Route::get('/logout', [AuthController::class, 'logout']);

    Route::get('/user/getUser', [UserController::class, 'getUser']);
    Route::post('/user/updateUser', [UserController::class, 'updateUser']);
    Route::get('/user/deleteUser', [UserController::class, 'deleteUser']);
    Route::post('/user/changePassword', [UserController::class, 'changePassword']);
//    Route::post('/user/addRefundRequest', [UserController::class, 'addRefundRequest']);
//    Route::get('/user/getUsers', [UserController::class, 'getUsers']);


    Route::get('/addresses/getAddressesByUser', [AddressController::class, 'getAddressesByUser']);
    Route::get('/addresses/getUserAddress/{address_id}', [AddressController::class, 'getUserAddress']);
    Route::post('/addresses/addUserAddress', [AddressController::class, 'addUserAddress']);
    Route::post('/addresses/updateUserAddress/{address_id}', [AddressController::class, 'updateUserAddress']);
    Route::get('/addresses/deleteUserAddress/{address_id}', [AddressController::class, 'deleteUserAddress']);


    Route::get('/messages/getMessageListByUser', [MessageController::class, 'getMessageListByUser']);
    Route::get('/messages/getMessagesByUserProductIdAndUserId/{user_product_id}/{partner_id}', [MessageController::class, 'getMessagesByUserProductIdAndUserId']);
    Route::post('/messages/sendMessage', [MessageController::class, 'sendMessage']);


    Route::get('/countries/getCountries', [CountriesController::class, 'getCountries']);
    Route::post('/countries/addCountries', [CountriesController::class, 'addCountries']);

    Route::get('/cities/getCitiesByCountryId/{country_id}', [CitiesController::class, 'getCitiesByCountryId']);
    Route::get('/cities/getDistrictsByCityId/{city_id}', [CitiesController::class, 'getDistrictsByCityId']);
    Route::get('/cities/getNeighbourhoodsByDistrictId/{district_id}', [CitiesController::class, 'getNeighbourhoodsByDistrictId']);
    Route::get('/cities/getNeighbourhoodById/{neighbourhood_id}', [CitiesController::class, 'getNeighbourhoodById']);
    Route::post('/cities/addCities/{country_id}', [CitiesController::class, 'addCities']);

    Route::get('/contactRules/getContactRules', [ContactRulesController::class, 'getContactRules']);
    Route::post('/contactRules/addContactRules', [ContactRulesController::class, 'addContactRules']);
    Route::post('/contactRules/updateContactRules/{id}', [ContactRulesController::class, 'updateContactRules']);

    Route::get('/contactRules/getContactRulesByUserId/{user_id}', [UserContactRulesController::class, 'getContactRulesByUserId']);
    Route::post('/contactRules/updateContactRulesByUserId/{user_id}/{contact_rule_id}', [UserContactRulesController::class, 'updateContactRulesByUserId']);

    Route::get('/userDocuments/getUserDocuments', [UserDocumentController::class, 'getUserDocuments']);
    Route::post('/userDocuments/addUserDocuments', [UserDocumentController::class, 'addUserDocuments']);
    Route::post('/userDocuments/updateUserDocuments/{document_id}', [UserDocumentController::class, 'updateUserDocuments']);
    Route::get('/userDocuments/deleteUserDocuments/{document_id}', [UserDocumentController::class, 'deleteUserDocuments']);

    Route::get('/userDocuments/getUserDocumentChecksByUserId/{user_id}', [UserDocumentChecksController::class, 'getUserDocumentChecksByUserId']);
    Route::post('/userDocuments/updateUserDocumentChecksByUserId/{document_id}/{user_id}', [UserDocumentChecksController::class, 'updateUserDocumentChecksByUserId']);
//    Route::post('/userDocuments/deleteUserDocumentsChecksByUserId/{document_id}', [UserDocumentChecksController::class, 'deleteUserDocumentsChecksByUserId']);


    //Favorites
    Route::get('/product/addFavorite/{product_id}', [ProductController::class, 'addFavorite']);
    Route::get('/product/removeFavorite/{product_id}', [ProductController::class, 'removeFavorite']);
    Route::get('/product/getFavorites', [ProductController::class, 'getFavorites']);




});



Route::post('/cart/addCart', [CartController::class, 'addCart']);
Route::post('/cart/updateCartProduct', [CartController::class, 'updateCartProduct']);
Route::post('/cart/deleteCartProduct', [CartController::class, 'deleteCartProduct']);
Route::get('/cart/getCartById/{cart_id}', [CartController::class, 'getCartById']);
Route::get('/cart/getUserAllCartById/{user_id}', [CartController::class, 'getUserAllCartById']);
Route::get('/cart/getClearCartById/{cart_id}', [CartController::class, 'getClearCartById']);
Route::get('/cart/getUserToCart/{user_id}/{cart_id}', [CartController::class, 'getUserToCart']);

Route::post('/cart/getCheckoutPrices', [CartController::class, 'getCheckoutPrices']);
Route::get('/cart/setIsOrder/{cart_id}/{is_order}', [CartController::class, 'setIsOrder']);

Route::post('order/addBankRequest', [OrderController::class, 'addBankRequest']);
Route::post('order/updateBankRequest', [OrderController::class, 'updateBankRequest']);

Route::post('/order/addOrder',[OrderController::class,'addOrder']);
Route::get('/order/getOrdersByUserId/{user_id}',[OrderController::class,'getOrdersByUserId']);
Route::get('/order/getOrderById/{order_id}',[OrderController::class,'getOrderById']);
Route::get('/order/getOrderByPaymentId/{payment_id}',[OrderController::class,'getOrderByPaymentId']);
Route::post('/order/addPayment',[OrderController::class,'addPayment']);
Route::post('/order/updatePayment',[OrderController::class,'updatePayment']);
Route::get('/order/getOrderPaymentStatusByPaymentId/{payment_id}',[OrderController::class,'getOrderPaymentStatusByPaymentId']);

Route::get('/brand/getBrands', [BrandController::class, 'getBrands']);
Route::get('/brand/getBrandById/{id}', [BrandController::class, 'getBrandById']);
Route::get('/productType/getProductType', [ProductTypeController::class, 'getProductType']);
Route::get('/productType/getProductTypeById/{type_id}', [ProductTypeController::class, 'getProductTypeById']);
Route::get('/category/getCategory', [CategoryController::class, 'getCategory']);
Route::get('/category/getParentCategory', [CategoryController::class, 'getParentCategory']);
Route::get('/category/getCategoryById/{category_id}', [CategoryController::class, 'getCategoryById']);

Route::get('/product/getAllProduct', [ProductController::class, 'getAllProduct']);
Route::get('/product/getAllProductById/{id}', [ProductController::class, 'getAllProductById']);
Route::get('/product/getAllProductWithVariationById/{user_id}/{product_id}/{variation_id}', [ProductController::class, 'getAllProductWithVariationById']);

Route::get('/product/getProduct', [ProductController::class, 'getProduct']);
Route::post('/product/getFilteredProduct', [ProductController::class, 'getFilteredProduct']);
Route::post('/product/getProductsByFilter/{user_id}', [ProductController::class, 'getProductsByFilter']);
Route::get('/product/getProductById/{id}', [ProductController::class, 'getProductById']);

Route::get('/product/getProductsByCategoryId/{category_id}', [ProductController::class, 'getProductsByCategoryId']);
Route::get('/product/getProductsWithParentCategory/{user_id}', [ProductController::class, 'getProductsWithParentCategory']);
Route::get('/product/getProductsBySlug/{user_id}/{slug}', [ProductController::class, 'getProductsBySlug']);
Route::get('/product/getProductsByType/{user_id}/{slug}', [ProductController::class, 'getProductsByType']);

Route::get('/product/getProductsByBrand/{user_id}/{slug}', [ProductController::class, 'getProductsByBrand']);
Route::get('/product/getBrandsWithProductsAndLimit/{limit}', [ProductController::class, 'getBrandsWithProductsAndLimit']);


Route::get('/product/getAllCampaignProducts/{user_id}', [ProductController::class, 'getAllCampaignProducts']);
Route::get('/product/getCampaignProductsByLimit/{user_id}/{limit}', [ProductController::class, 'getCampaignProductsByLimit']);
Route::get('/product/getFeaturedProducts/{user_id}', [ProductController::class, 'getFeaturedProducts']);
Route::get('/product/getNewProducts/{user_id}', [ProductController::class, 'getNewProducts']);
Route::get('/product/getSimilarProducts/{product_id}', [ProductController::class, 'getSimilarProducts']);

Route::get('/product/getCheckProductSku/{product_sku}', [ProductController::class, 'getCheckProductSku']);
Route::get('/product/getCheckProductVariationSku/{product_sku}', [ProductController::class, 'getCheckProductVariationSku']);




Route::get('/product/getProductTagById/{product_id}', [ProductController::class, 'getProductTagById']);
Route::get('/product/getProductCategoryById/{product_id}', [ProductController::class, 'getProductCategoryById']);
Route::get('/product/getProductDocumentById/{product_id}', [ProductController::class, 'getProductDocumentById']);
Route::get('/product/getProductVariationGroupById/{product_id}', [ProductController::class, 'getProductVariationGroupById']);
Route::get('/product/getProductVariationById/{id}', [ProductController::class, 'getProductVariationById']);
Route::get('/product/getProductVariationsById/{id}', [ProductController::class, 'getProductVariationsById']);
Route::get('/product/getVariationsImageById/{product_id}', [ProductController::class, 'getVariationsImageById']);
Route::get('/product/getVariationImageById/{variation_id}', [ProductController::class, 'getVariationImageById']);
Route::get('/product/getProductTabsById/{product_id}', [ProductController::class, 'getProductTabsById']);
Route::get('/product/getProductTabById/{tab_id}', [ProductController::class, 'getProductTabById']);
Route::get('/product/getCategoriesByBranId', [ProductController::class, 'getCategoriesByBranId']);

Route::get('/product/getProductColors', [ProductController::class, 'getProductColors']);

Route::get('/product/getCreditCartInstallments/{product_variation_id}', [ProductController::class, 'getCreditCartInstallments']);


Route::get('/productSeo/getProductSeoById/{product_id}', [ProductController::class, 'getProductSeoById']);


Route::get('/productDocument/getProductDocument', [ProductDocumentController::class, 'getProductDocument']);


Route::get('/productVariationGroupType/getProductVariationGroupTypes', [ProductVariationGroupTypeController::class, 'getProductVariationGroupTypes']);
Route::get('/productVariationGroupType/getProductVariationGroupTypeById/{id}', [ProductVariationGroupTypeController::class, 'getProductVariationGroupTypeById']);


Route::get('/productType/getProductTypes', [ProductTypeController::class, 'getProductTypes']);
Route::get('/productType/getProductVariationById/{variation_id}', [ProductTypeController::class, 'getProductTypeById']);

Route::get('/tab/getTabs', [TabController::class, 'getTabs']);
Route::get('/tab/getTabById/{tab_id}', [TabController::class, 'getTabById']);

Route::get('/tag/getTags', [TagController::class, 'getTags']);
Route::get('/tag/getTagById/{id}', [TagController::class, 'getTagById']);

Route::get('/carrier/getCarriers', [CarrierController::class, 'getCarriers']);
Route::get('/carrier/getCarrierById/{id}', [CarrierController::class, 'getCarrierById']);

Route::get('/creditCard/getCreditCarts', [CreditCardController::class, 'getCreditCarts']);
Route::get('/creditCard/getCreditCardById/{member_no}/{cart_id}/{coupon_code}/{partial}/{total}', [CreditCardController::class, 'getCreditCardById']);
Route::get('/creditCard/getVinovExpiries', [CreditCardController::class, 'getVinovExpiries']);
Route::get('/creditCard/getVinovExpiriesWithPayment/{cart_id}/{coupon_code}/{total}/{delivery}', [CreditCardController::class, 'getVinovExpiriesWithPayment']);
Route::get('/creditCard/getVinovExpiryById/{id}', [CreditCardController::class, 'getVinovExpiryById']);


Route::get('/bankBinPair/getBankBinPairMemberNo/{prefix_no}', [BankBinPairController::class, 'getBankBinPairMemberNo']);
Route::post('/search/categoryByIdSearch/{user_id}', [SearchController::class, 'categoryByIdSearch']);
Route::post('/search/filterProducts/{user_id}', [SearchController::class, 'filterProducts']);

Route::get('/slider/getSliders', [SliderController::class, 'getSliders']);
Route::get('/slider/getSliderById/{id}', [SliderController::class, 'getSliderById']);

Route::get('/seo/getSeos', [SeoController::class, 'getSeos']);
Route::get('/seo/getSeoById/{id}', [SeoController::class, 'getSeoById']);

Route::post('/coupon/useCoupon', [CouponController::class, 'useCoupon']);

Route::get('/popup/getPopups', [PopupController::class, 'getPopups']);
Route::get('/popup/getPopupById/{id}', [PopupController::class, 'getPopupById']);
Route::get('/popup/getActivePopup', [PopupController::class, 'getActivePopup']);

Route::post('/subscribe/addSubscriber', [SubscribeController::class, 'addSubscriber']);

Route::post('/cimri/getCimriProductsByFilter', [CimriController::class, 'getCimriProductsByFilter']);
Route::post('/cimri/addProductCimri', [CimriController::class, 'addProductCimri']);
Route::post('/cimri/deleteProductCimri', [CimriController::class, 'deleteProductCimri']);

Route::get('/cimri/getProducts', [CimriController::class, 'getProducts']);
Route::get('/cimri/getProductById/{product_id}', [CimriController::class, 'getProductById']);
Route::post('/cimri/updateProduct/{product_id}', [CimriController::class, 'updateProduct']);
Route::get('/cimri/deleteProduct/{product_id}', [CimriController::class, 'deleteProduct']);

Route::post('contact/addContactForm', [ContactController::class, 'addContactForm']);
