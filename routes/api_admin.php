<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminPermissionController;
use App\Http\Controllers\Api\Admin\AdminRoleController;
use App\Http\Controllers\Api\Admin\AdminUserComments;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Admin\Old\BrandController;
use App\Http\Controllers\Api\Admin\Old\CarrierController;
use App\Http\Controllers\Api\Admin\Old\CartController;
use App\Http\Controllers\Api\Admin\Old\ContactController;
use App\Http\Controllers\Api\Admin\Old\CouponController;
use App\Http\Controllers\Api\Admin\Old\CreditCardController;
use App\Http\Controllers\Api\Admin\Old\DashboardController;
use App\Http\Controllers\Api\Admin\Old\DeliveryController;
use App\Http\Controllers\Api\Admin\Old\ImportController;
use App\Http\Controllers\Api\Admin\Old\PopupController;
use App\Http\Controllers\Api\Admin\Old\ProductTypeController;
use App\Http\Controllers\Api\Admin\Old\ProductVariationGroupTypeController;
use App\Http\Controllers\Api\Admin\Old\ProformaController;
use App\Http\Controllers\Api\Admin\Old\SeoController;
use App\Http\Controllers\Api\Admin\Old\ShippingTypeController;
use App\Http\Controllers\Api\Admin\Old\SliderController;
use App\Http\Controllers\Api\Admin\Old\SubscribeController;
use App\Http\Controllers\Api\Admin\Old\TabController;
use App\Http\Controllers\Api\Admin\Old\TagController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\OrderStatusController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ShopController;
use App\Http\Controllers\Api\Admin\SupportController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\CommentController;
use App\Http\Controllers\Api\Admin\EstateController;
use App\Http\Controllers\Api\Admin\CarController;
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


Route::post('auth/login', [AuthController::class, 'login'])->name('admin.login');
Route::get('language/getLibraryByPlatform/{platform}', [LanguageController::class, 'getLibraryByPlatform']);
Route::get('language/getLibraryLastUpdateByPlatform/{platform}', [LanguageController::class, 'getLibraryLastUpdateByPlatform']);


Route::middleware(['auth:sanctum', 'type.admin'])->group(function (){

    Route::get('logout', [AuthController::class, 'logout'])->name('admin.logout');
    Route::post('register', [AuthController::class, 'register'])->name('admin.register');


    //Language Library
    Route::get('language/getLibrary', [LanguageController::class, 'getLibrary']);
    Route::get('language/getLibraryById/{id}', [LanguageController::class, 'getLibraryById']);
    Route::post('language/addLibrary', [LanguageController::class, 'addLibrary']);
    Route::post('language/updateLibrary/{id}', [LanguageController::class, 'updateLibrary']);
    Route::get('language/deleteLibrary/{id}', [LanguageController::class, 'deleteLibrary']);



    Route::get('adminUserComment/getAdminUserComment', [AdminUserComments::class, 'getAdminUserComment']);
    Route::post('adminUserComment/addAdminUserComment', [AdminUserComments::class, 'addAdminUserComment']);
    Route::post('adminUserComment/updateAdminUserComment/{id}', [AdminUserComments::class, 'updateAdminUserComment']);
    Route::get('adminUserComment/deleteAdminUserComment/{id}', [AdminUserComments::class, 'deleteAdminUserComment']);

    Route::get('adminRole/getAdmins', [AdminRoleController::class, 'getAdmins']);
    Route::get('adminRole/getAdminById/{id}', [AdminRoleController::class, 'getAdminById']);
    Route::post('adminRole/addAdmin', [AdminRoleController::class, 'addAdmin']);
    Route::post('adminRole/updateAdmin/{id}', [AdminRoleController::class, 'updateAdmin']);
    Route::get('adminRole/deleteAdmin/{id}', [AdminRoleController::class, 'deleteAdmin']);

    Route::get('adminRole/getAdminRoles', [AdminRoleController::class, 'getAdminRoles']);
    Route::get('adminRole/getAdminRoleById/{id}', [AdminRoleController::class, 'getAdminRoleById']);
    Route::post('adminRole/addAdminRole', [AdminRoleController::class, 'addAdminRole']);
    Route::post('adminRole/updateAdminRole/{role_id}', [AdminRoleController::class, 'updateAdminRole']);
    Route::get('adminRole/deleteAdminRole/{role_id}', [AdminRoleController::class, 'deleteAdminRole']);

    Route::get('adminRole/getAdminRolePermissions/{role_id}', [AdminRoleController::class, 'getAdminRolePermissions']);
    Route::get('adminRole/addAdminRolePermission/{role_id}/{permission_id}', [AdminRoleController::class, 'addAdminRolePermission']);
    Route::get('adminRole/deleteAdminRolePermission/{role_id}/{permission_id}', [AdminRoleController::class, 'deleteAdminRolePermission']);

    Route::get('adminPermission/getAdminPermissions', [AdminPermissionController::class, 'getAdminPermissions']);
    Route::post('adminPermission/addAdminPermission', [AdminPermissionController::class, 'addAdminPermission']);
    Route::post('adminPermission/updateAdminPermission/{id}', [AdminPermissionController::class, 'updateAdminPermission']);
    Route::get('adminPermission/deleteAdminPermission/{id}', [AdminPermissionController::class, 'deleteAdminPermission']);



    //Shop
    Route::get('shop/getShops', [ShopController::class, 'getShops']);
    Route::get('shop/getShopById/{id}', [ShopController::class, 'getShopById']);
    Route::get('shop/getShopConfirmed/{id}', [ShopController::class, 'getShopConfirmed']);
    Route::get('shop/getShopRejected/{id}', [ShopController::class, 'getShopRejected']);
    Route::get('shop/getShopRegisterDocuments/{id}', [ShopController::class, 'getShopRegisterDocuments']);
    Route::get('shop/getOpenShop/{id}', [ShopController::class, 'getOpenShop']);
    Route::get('shop/getCloseShop/{id}', [ShopController::class, 'getCloseShop']);
    Route::post('shop/addShopPayment', [ShopController::class, 'addShopPayment']);

    //Product
    Route::get('product/getProducts', [ProductController::class, 'getProducts']);
    Route::get('/product/getProductById/{id}', [ProductController::class, 'getProductById']);
    Route::get('product/getProductConfirmed/{id}', [ProductController::class, 'getProductConfirmed']);
    Route::get('product/getProductRejected/{id}', [ProductController::class, 'getProductRejected']);
    Route::get('product/getCommentsByProductId/{product_id}', [CommentController::class, 'getCommentsByProductId']);
    Route::get('product/deleteProduct/{id}', [ProductController::class, 'deleteProduct']);
    Route::post('product/addProduct', [ProductController::class, 'addProduct']);
    Route::post('product/updateProduct/{id}', [ProductController::class, 'updateProduct']);

    //Category
    Route::get('category/getCategories', [CategoryController::class, 'getCategories']);
    Route::get('category/getCategoryById/{id}', [CategoryController::class, 'getCategoryById']);
    Route::post('category/addCategory', [CategoryController::class, 'addCategory']);
    Route::post('category/updateCategory/{id}', [CategoryController::class, 'updateCategory']);
    Route::get('category/deleteCategory/{id}', [CategoryController::class, 'deleteCategory']);

    //Support
    Route::get('support/getSupportCategories', [SupportController::class, 'getSupportCategories']);
    Route::post('support/addSupportMessage', [SupportController::class, 'addSupportMessage']);
    Route::get('support/getSupportList', [SupportController::class, 'getSupportList']);
    Route::get('support/getSupportConversation/{request_id}', [SupportController::class, 'getSupportConversation']);
    Route::get('support/getCloseSupportRequest/{request_id}', [SupportController::class, 'getCloseSupportRequest']);

    //Order
    Route::get('/order/getOnGoingOrders',[OrderController::class,'getOnGoingOrders']);
    Route::get('/order/getCompletedOrders',[OrderController::class,'getCompletedOrders']);
    Route::get('/order/getOrderById/{order_id}',[OrderController::class,'getOrderById']);
    Route::get('/order/getOrderStatusHistoriesById/{id}', [OrderController::class, 'getOrderStatusHistoriesById']);
    Route::get('/order/getUpdateOrderBillingAddress/{order_id}/{address_id}', [OrderController::class, 'getUpdateOrderBillingAddress']);
    Route::get('/order/getUpdateOrderShippingAddress/{order_id}/{address_id}', [OrderController::class, 'getUpdateOrderShippingAddress']);


    Route::post('order/updateOrder/{id}', [OrderController::class, 'updateOrder']);
    Route::post('order/updateOrderStatus', [OrderController::class, 'updateOrderStatus']);
    Route::get('order/deleteOrder/{order_id}', [OrderController::class, 'deleteOrder']);
    Route::get('order/confirmOrder/{order_id}', [OrderController::class, 'confirmOrder']);
    Route::get('order/cancelOrder/{order_id}', [OrderController::class, 'cancelOrder']);
    Route::get('order/cancelProvision/{order_id}', [OrderController::class, 'cancelProvision']);

    //OrderStatus
    Route::get('/orderStatus/getOrderStatuses', [OrderStatusController::class, 'getOrderStatuses']);

    //User
    Route::get('/user/getUserAddressesByOrderId/{order_id}',[UserController::class,'getUserAddressesByOrderId']);

    //Estate
    Route::get('estate/getEstateConfirmed/{id}', [EstateController::class, 'getEstateConfirmed']);
    Route::get('estate/getEstateRejected/{id}', [EstateController::class, 'getEstateRejected']);

    //Car
    Route::get('car/getCarConfirmed/{id}', [CarController::class, 'getCarConfirmed']);
    Route::get('car/getCarRejected/{id}', [CarController::class, 'getCarRejected']);











    Route::post('brand/addBrand', [BrandController::class, 'addBrand']);
    Route::post('brand/updateBrand/{id}', [BrandController::class, 'updateBrand']);
    Route::get('brand/deleteBrand/{id}', [BrandController::class, 'deleteBrand']);
    Route::get('brand/activeBrand/{id}', [BrandController::class, 'activeBrand']);
    Route::get('brand/getBrandPassive', [BrandController::class, 'getBrandPassive']);

    Route::post('productType/addProductType', [ProductTypeController::class, 'addProductType']);
    Route::post('productType/updateProductType/{id}', [ProductTypeController::class, 'updateProductType']);
    Route::get('productType/deleteProductType/{id}', [ProductTypeController::class, 'deleteProductType']);
    Route::post('productType/updateProductTypeOrder', [ProductTypeController::class, 'updateProductTypeOrder']);

    Route::post('category/updateHomeCategoryBanner/{id}', [CategoryController::class, 'updateHomeCategoryBanner']);

    Route::post('product/addFullProduct', [ProductController::class, 'addFullProduct']);
    Route::post('product/updateFullProduct/{id}', [ProductController::class, 'updateFullProduct']);
    Route::post('product/updateProductStatus', [ProductController::class, 'updateProductStatus']);

    Route::post('product/addProductTag', [ProductController::class, 'addProductTag']);
    Route::post('product/deleteProductTag', [ProductController::class, 'deleteProductTag']);

    Route::post('product/addCampaignProduct', [ProductController::class, 'addCampaignProduct']);
    Route::post('product/updateCampaignProductOrder', [ProductController::class, 'updateCampaignProductOrder']);
    Route::get('product/deleteCampaignProduct/{id}', [ProductController::class, 'deleteCampaignProduct']);

    Route::post('product/addProductCategory', [ProductController::class, 'addProductCategory']);
    Route::post('product/deleteProductCategory', [ProductController::class, 'deleteProductCategory']);

    Route::post('product/addProductDocument', [ProductController::class, 'addProductDocument']);
    Route::post('product/updateProductDocument/{id}', [ProductController::class, 'updateProductDocument']);
    Route::get('product/deleteProductDocument/{id}', [ProductController::class, 'deleteProductDocument']);

    Route::post('product/addProductVariationGroup', [ProductController::class, 'addProductVariationGroup']);
    Route::post('product/updateProductVariationGroup/{id}', [ProductController::class, 'updateProductVariationGroup']);
    Route::get('product/deleteProductVariationGroup/{id}', [ProductController::class, 'deleteProductVariationGroup']);


    Route::post('product/addFullProductVariationGroup', [ProductController::class, 'addFullProductVariationGroup']);
    Route::post('product/updateFullProductVariationGroup/{id}', [ProductController::class, 'updateFullProductVariationGroup']);
    Route::get('product/deleteFullProductVariationGroup/{}', [ProductController::class, 'deleteFullProductVariationGroup']);

    Route::post('product/updateProductDiscountRate', [ProductController::class, 'updateProductDiscountRate']);
    Route::post('product/updateBrandIdDiscountRate/{brand_id}', [ProductController::class, 'updateBrandIdDiscountRate']);
    Route::post('product/updateTypeIdDiscountRate/{type_id}', [ProductController::class, 'updateTypeIdDiscountRate']);
    Route::post('product/updateCategoryIdDiscountRate/{category_id}', [ProductController::class, 'updateCategoryIdDiscountRate']);


    Route::post('productVariationGroupType/addProductVariationGroupType', [ProductVariationGroupTypeController::class, 'addProductVariationGroupType']);
    Route::post('productVariationGroupType/updateProductVariationGroupType/{id}', [ProductVariationGroupTypeController::class, 'updateProductVariationGroupType']);
    Route::get('productVariationGroupType/deleteProductVariationGroupType/{id}', [ProductVariationGroupTypeController::class, 'deleteProductVariationGroupType']);

    Route::post('product/addProductVariation', [ProductController::class, 'addProductVariation']);
    Route::post('product/updateProductVariation/{id}', [ProductController::class, 'updateProductVariation']);
    Route::get('product/deleteProductVariation/{id}', [ProductController::class, 'deleteProductVariation']);

    Route::get('product/getProductFeaturedVariationById/{id}', [ProductController::class, 'getProductFeaturedVariationById']);
    Route::post('product/updateProductFeaturedVariationById/{id}', [ProductController::class, 'updateProductFeaturedVariationById']);

    Route::post('variationImage/updateVariationImage/{id}', [ProductController::class, 'updateVariationImage']);
    Route::get('variationImage/deleteVariationImage/{id}', [ProductController::class, 'deleteVariationImage']);

    Route::post('product/addProductImage', [ProductController::class, 'addProductImage']);

    Route::get('product/getProductMaterials', [ProductController::class, 'getProductMaterials']);
    Route::get('product/getProductMaterialById/{id}', [ProductController::class, 'getProductMaterialById']);
    Route::post('product/addProductMaterial', [ProductController::class, 'addProductMaterial']);
    Route::post('product/updateProductMaterial', [ProductController::class, 'updateProductMaterial']);
    Route::get('product/deleteProductMaterial/{id}', [ProductController::class, 'deleteProductMaterial']);

    Route::post('orderStatus/addOrderStatus', [OrderStatusController::class, 'addOrderStatus']);
    Route::post('orderStatus/updateOrderStatus/{id}', [OrderStatusController::class, 'updateOrderStatus']);
    Route::post('orderStatus/updateOrderAllStatus', [OrderStatusController::class, 'updateOrderAllStatus']);
    Route::get('orderStatus/deleteOrderStatus/{id}', [OrderStatusController::class, 'deleteOrderStatus']);
    Route::get('orderStatus/getOrderStatusById/{id}', [OrderStatusController::class, 'getOrderStatusById']);

    Route::post('order/updateOrderInfo/{order_id}', [OrderController::class, 'updateOrderInfo']);
    Route::post('order/updateOrderShipment/{order_id}', [OrderController::class, 'updateOrderShipment']);
    Route::post('order/updateOrderBilling/{order_id}', [OrderController::class, 'updateOrderBilling']);
    Route::post('order/updateOrderShipping/{order_id}', [OrderController::class, 'updateOrderShipping']);

    Route::get('order/getRefundOrders', [OrderController::class, 'getRefundOrders']);
    Route::get('order/getOrderRefundStatuses', [OrderController::class, 'getOrderRefundStatuses']);
    Route::post('order/updateRefundStatus/{order_id}', [OrderController::class, 'updateRefundStatus']);


    Route::get('/order/getOrderPaymentInfoById/{order_id}',[OrderController::class,'getOrderPaymentInfoById']);
    Route::get('/order/getOrderPaymentProvizyonById/{payment_id}',[OrderController::class,'getOrderPaymentProvizyonById']);
    Route::get('/order/getOrderBillingInfoById/{order_id}',[OrderController::class,'getOrderBillingInfoById']);
    Route::post('/order/updateOrderBillingInfoById/{order_id}',[OrderController::class,'updateOrderBillingInfoById']);
    Route::get('/order/getOrderShippingInfoById/{order_id}',[OrderController::class,'getOrderShippingInfoById']);
    Route::post('/order/updateOrderShippingInfoById/{order_id}',[OrderController::class,'updateOrderShippingInfoById']);
    Route::get('/order/getOrderShipmentInfoById/{order_id}',[OrderController::class,'getOrderShipmentInfoById']);



    Route::post('product/addProductTab', [ProductController::class, 'addProductTab']);
    Route::post('product/updateProductTab', [ProductController::class, 'updateProductTab']);
    Route::get('product/deleteProductTab/{tab_id}', [ProductController::class, 'deleteProductTab']);

    Route::post('productPackageType/addProductPackageType', [ProductController::class, 'addProductPackageType']);
    Route::post('productPackageType/updateProductPackageType/{id}', [ProductController::class, 'updateProductPackageType']);
    Route::get('productPackageType/deleteProductPackageType/{id}', [ProductController::class, 'deleteProductPackageType']);


    Route::post('productSeo/addProductSeo', [ProductController::class, 'addProductSeo']);
    Route::get('productSeo/deleteProductSeo/{id}', [ProductController::class, 'deleteProductSeo']);

    Route::post('tab/addTab', [TabController::class, 'addTab']);
    Route::post('tab/updateTab/{id}', [TabController::class, 'updateTab']);
    Route::get('tab/deleteTab/{id}', [TabController::class, 'deleteTab']);

    Route::post('tag/addTag', [TagController::class, 'addTag']);
    Route::post('tag/updateTag/{id}', [TagController::class, 'updateTag']);
    Route::get('tag/deleteTag/{id}', [TagController::class, 'deleteTag']);


    Route::get('cart/getAllCart', [CartController::class, 'getAllCart']);

    Route::post('carrier/addCarrier', [CarrierController::class, 'addCarrier']);
    Route::post('carrier/updateCarrier/{id}', [CarrierController::class, 'updateCarrier']);
    Route::get('carrier/deleteCarrier/{id}', [CarrierController::class, 'deleteCarrier']);
    Route::get('carrier/getCarriers', [CarrierController::class, 'getCarriers']);
    Route::get('carrier/getIncreasingDesis', [CarrierController::class, 'getIncreasingDesis']);
    Route::get('carrier/getIncreasingDesiById/{id}', [CarrierController::class, 'getIncreasingDesiById']);
    Route::post('carrier/updateIncreasingDesi', [CarrierController::class, 'updateIncreasingDesi']);


    Route::post('excel/productExcelImport', [ImportController::class, 'productExcelImport']);
    Route::post('excel/priceExcelImport', [ImportController::class, 'priceExcelImport']);
    Route::post('excel/zipCodeExcelImport', [ImportController::class, 'zipCodeExcelImport']);

    Route::get('excel/addAllProduct', [ImportController::class, 'addAllProduct']);
    Route::get('excel/addProductPrice', [ImportController::class, 'addProductPrice']);
    Route::get('excel/addZipCodeToNeighbour/{min}/{max}', [ImportController::class, 'addZipCodeToNeighbour']);
    Route::get('excel/addAddressZipCodeAndNeighbour', [ImportController::class, 'addAddressZipCodeAndNeighbour']);

    Route::get('excel/productVariationUpdate', [ImportController::class, 'productVariationUpdate']);
    Route::get('excel/setProductCategory', [ImportController::class, 'setProductCategory']);

    Route::post('excel/newProduct', [ImportController::class, 'newProduct']);
    Route::post('excel/postNewProducts', [ImportController::class, 'postNewProducts']);


    Route::get('excel/updateProductNew', [ImportController::class, 'updateProductNew']);



    Route::get('shippingType/getShippingTypes',[ShippingTypeController::class,'getShippingTypes']);
    Route::get('shippingType/getShippingTypeById/{id}',[ShippingTypeController::class,'getShippingTypeById']);

    Route::get('creditCard/getCreditCards',[CreditCardController::class,'getCreditCards']);
    Route::get('creditCard/getCreditCardById/{card_id}',[CreditCardController::class,'getCreditCardById']);
    Route::post('creditCard/postCreditInstallmentUpdate/{id}',[CreditCardController::class,'postCreditInstallmentUpdate']);
    Route::get('creditCard/getCreditCardInstallmentById/{id}',[CreditCardController::class,'getCreditCardInstallmentById']);
    Route::post('creditCard/addVinovExpiry', [CreditCardController::class, 'addVinovExpiry']);
    Route::post('creditCard/updateVinovExpiry', [CreditCardController::class, 'updateVinovExpiry']);
    Route::get('creditCard/deleteVinovExpiry/{id}', [CreditCardController::class, 'deleteVinovExpiry']);

    Route::get('user/getUsers',[UserController::class,'getUsers']);
    Route::get('user/getPassiveUsers',[UserController::class,'getPassiveUsers']);
    Route::get('user/getUsersByTypeId/{id}',[UserController::class,'getUsersByTypeId']);
    Route::get('user/getUserTypes', [UserController::class, 'getUserTypes']);
    Route::get('user/getUserTypeById/{id}', [UserController::class, 'getUserTypeById']);
    Route::post('user/addUserType', [UserController::class, 'addUserType']);
    Route::post('user/updateUserType/{id}', [UserController::class, 'updateUserType']);
    Route::get('user/deleteUserType/{id}', [UserController::class, 'deleteUserType']);
    Route::get('user/getUserTypeDiscounts', [UserController::class, 'getUserTypeDiscounts']);
    Route::get('user/getUserTypeDiscountById/{id}', [UserController::class, 'getUserTypeDiscountById']);
    Route::post('user/addUserTypeDiscount', [UserController::class, 'addUserTypeDiscount']);
    Route::post('user/updateUserTypeDiscount/{id}', [UserController::class, 'updateUserTypeDiscount']);
    Route::get('user/deleteUserTypeDiscount/{id}', [UserController::class, 'deleteUserTypeDiscount']);
    Route::get('user/activateUser/{user_id}', [UserController::class, 'activateUser']);
    Route::get('user/verifyUser/{user_id}', [UserController::class, 'verifyUser']);
    Route::get('user/deleteUser/{user_id}', [UserController::class, 'deleteUser']);
    Route::post('user/updateTypeToUser', [UserController::class, 'updateTypeToUser']);
    Route::post('user/addUserForAdmin', [UserController::class, 'addUserForAdmin']);

    Route::post('slider/addSlider', [SliderController::class, 'addSlider']);
    Route::post('slider/updateSlider/{id}', [SliderController::class, 'updateSlider']);
    Route::get('slider/deleteSlider/{id}', [SliderController::class, 'deleteSlider']);

    Route::post('seo/addSeo', [SeoController::class, 'addSeo']);
    Route::post('seo/updateSeo/{id}', [SeoController::class, 'updateSeo']);

    Route::post('coupon/addCoupon', [CouponController::class, 'addCoupon']);
    Route::post('coupon/updateCoupon/{id}', [CouponController::class, 'updateCoupon']);
    Route::get('coupon/deleteCoupon/{id}', [CouponController::class, 'deleteCoupon']);
    Route::get('coupon/getCoupons', [CouponController::class, 'getCoupons']);
    Route::get('coupon/getCouponById/{id}', [CouponController::class, 'getCouponById']);

    Route::get('delivery/getDeliveryPrices', [DeliveryController::class, 'getDeliveryPrices']);
    Route::get('delivery/getDeliveryPriceById/{id}', [DeliveryController::class, 'getDeliveryPriceById']);
    Route::post('delivery/addDeliveryPrice', [DeliveryController::class, 'addDeliveryPrice']);
    Route::post('delivery/updateDeliveryPrice/{id}', [DeliveryController::class, 'updateDeliveryPrice']);
    Route::get('delivery/deleteDeliveryPrice/{id}', [DeliveryController::class, 'deleteDeliveryPrice']);
    Route::get('delivery/syncCitiesToRegionalDelivery', [DeliveryController::class, 'syncCitiesToRegionalDelivery']);
    Route::get('delivery/resetAllPricesToDefault', [DeliveryController::class, 'resetAllPricesToDefault']);
    Route::get('delivery/resetPricesToDefaultByCityId/{city_id}', [DeliveryController::class, 'resetPricesToDefaultByCityId']);
    Route::get('delivery/resetPricesToDefaultByDeliveryPriceId/{delivery_price_id}', [DeliveryController::class, 'resetPricesToDefaultByDeliveryPriceId']);
    Route::get('delivery/getRegionalDeliveryPriceByCityId/{id}', [DeliveryController::class, 'getRegionalDeliveryPriceByCityId']);
    Route::get('delivery/getRegionalDeliveryPrice/{city_id}/{delivery_price_id}', [DeliveryController::class, 'getRegionalDeliveryPrice']);
    Route::post('delivery/updateRegionalDeliveryPrice/{city_id}/{delivery_price_id}', [DeliveryController::class, 'updateRegionalDeliveryPrice']);


    Route::get('delivery/syncDistrictsDelivery', [DeliveryController::class, 'syncDistrictsDelivery']);
    Route::get('delivery/getDistrictDeliveries', [DeliveryController::class, 'getDistrictDeliveries']);
    Route::get('delivery/getDistrictDeliveryById/{id}', [DeliveryController::class, 'getDistrictDeliveryById']);
    Route::post('delivery/updateDistrictDelivery/{id}', [DeliveryController::class, 'updateDistrictDelivery']);

    Route::post('popup/addPopup', [PopupController::class, 'addPopup']);
    Route::post('popup/updatePopup/{id}', [PopupController::class, 'updatePopup']);
    Route::get('popup/deletePopup/{id}', [PopupController::class, 'deletePopup']);
    Route::get('popup/changePopupStatus/{id}/{status}', [PopupController::class, 'changePopupStatus']);
    Route::get('popup/changePopupFormStatus/{id}/{status}', [PopupController::class, 'changePopupFormStatus']);

    Route::get('subscribe/getSubscribers', [SubscribeController::class, 'getSubscribers']);
    Route::get('subscribe/getSubscriberById/{id}', [SubscribeController::class, 'getSubscriberById']);
    Route::post('subscribe/updateSubscriber/{id}', [SubscribeController::class, 'updateSubscriber']);
    Route::get('subscribe/deleteSubscriber/{id}', [SubscribeController::class, 'deleteSubscriber']);

    Route::post('proforma/addProformaOrder', [ProformaController::class, 'addProformaOrder']);
    Route::get('proforma/getProformaProducts', [ProformaController::class, 'getProformaProducts']);
    Route::post('proforma/getProformaProductsByFilter', [ProformaController::class, 'getProformaProductsByFilter']);
    Route::get('proforma/getDuplicateProformaOrder/{order_id}', [ProformaController::class, 'getDuplicateProformaOrder']);


    Route::get('dashboard/getDashboard', [DashboardController::class, 'getDashboard']);
    Route::get('dashboard/getLastOrders',[DashboardController::class,'getLastOrders']);


    Route::get('contact/getContactForms', [ContactController::class, 'getContactForms']);

});

