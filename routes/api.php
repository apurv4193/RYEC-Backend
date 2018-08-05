<?php

use Illuminate\Http\Request;

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

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:api');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization, mimetype, Platform');

Route::post('register', 'Api\UsersController@register');
Route::post('login', 'Api\AuthController@login');
Route::post('forgotpassword', 'Api\UsersController@forgotpassword');
Route::post('getLanguageLabels', 'Api\UsersController@getLanguageLabels');
Route::post('getAppUpdateStatus', 'Api\UsersController@getAppUpdateStatus');

//reset password routes
Route::post('user/resetpasswordrequest', 'Api\PasswordController@resetPasswordRequest');
Route::post('user/resetpasswordrequestconfirm', 'Api\PasswordController@resetPasswordRequestConfirm');
Route::post('user/resetpassword', 'Api\PasswordController@resetPassword');

// get country code
Route::get('getCountryCode', 'Api\UsersController@getCountryCode');

// Middleware for jwt.auth
Route::group(['middleware' => 'jwt.auth'], function () {
   
//  Category Apis DgetPopularBusinessesetails
    Route::get('getMainCategory', 'Api\CategoryController@getMainCategory');
    Route::post('getSubCategory', 'Api\CategoryController@getSubCategory');    

//  Business APIs
    Route::post('addBusiness', 'Api\BusinessController@addBusiness');
    Route::post('getBusinessListing', 'Api\BusinessController@getBusinessListingByCatId');
    Route::post('getBusinessNearByMap', 'Api\BusinessController@getBusinessNearByMap');
    Route::get('getBrandingData', 'Api\BusinessController@getBrandingFileOrText');

    Route::post('logout', 'Api\UsersController@logout');
    Route::post('getBusinessDetail', 'Api\BusinessController@getBusinessDetail');
    Route::post('getRecentlyAddedBusinessListing', 'Api\BusinessController@getRecentlyAddedBusinessListing');
    Route::get('getPopularBusinesses', 'Api\BusinessController@getPopularBusinesses');
    Route::post('getNearByBusinesses', 'Api\BusinessController@getNearByBusinesses');
    Route::post('sendAddMemberOTP', 'Api\BusinessController@sendAddMemberOTP');
    Route::post('verifyAgentOTP', 'Api\BusinessController@verifyAgentOTP');
    Route::post('agentSaveUser', 'Api\BusinessController@agentSaveUser');
    Route::post('saveBusiness', 'Api\BusinessController@saveBusiness');
    Route::post('saveBusinessImages', 'Api\BusinessController@saveBusinessImages');
    Route::post('deleteBusinessImage', 'Api\BusinessController@deleteBusinessImage');
    Route::get('getTimezone', 'Api\BusinessController@getTimezone');
    Route::post('getAgentBusinesses', 'Api\BusinessController@getAgentBusinesses');
    

//  Business Ratings APIs    
    Route::post('addBusinessRating', 'Api\BusinessController@addBusinessRating');
    Route::post('getBusinessRatings', 'Api\BusinessController@getBusinessRatings');
    
    
//  Product APIs   
    Route::post('getProductDetails', 'Api\ProductController@getProductDetails');
    Route::post('saveProduct', 'Api\ProductController@saveProduct');
    Route::post('removeProductImage', 'Api\ProductController@removeProductImage');
    Route::post('removeProduct', 'Api\ProductController@removeProduct');
    
//  Service APIs
    Route::post('getServiceDetails', 'Api\ServiceController@getServiceDetails');
    Route::post('saveService', 'Api\ServiceController@saveService');
    Route::post('removeService', 'Api\ServiceController@removeService');
    
//  User's APIs
    Route::get('getprofile', 'Api\UsersController@getProfile');
    Route::post('saveprofile', 'Api\UsersController@saveProfile');
    Route::post('changepassword', 'Api\UsersController@changePassword');
    Route::post('saveProfilePicture', 'Api\UsersController@saveProfilePicture');

//  Investment Ideas Routes
    Route::post('getInvestmentIdeas', 'Api\InvestmentController@getInvestmentIdeas');
    Route::post('addInvestmentIdea', 'Api\InvestmentController@addInvestmentIdea');
    Route::post('showInterestOnInvestmentIdea', 'Api\InvestmentController@showInterestOnInvestmentIdea');
    Route::post('getInvestmentIdeaDetails', 'Api\InvestmentController@getInvestmentIdeaDetails');
    Route::post('addInvestmentInterest', 'Api\InvestmentController@saveInvestmentInterest');
    Route::post('getInvestmentInterestDetail', 'Api\InvestmentController@getInvestmentInterestById');
    Route::get('getAllInvestmentInterest', 'Api\InvestmentController@getAllInvestmentInterest');
    Route::post('getMyInvestmentInterest', 'Api\InvestmentController@getMyInvestmentInterest');
    Route::post('deleteInvestmentIdea', 'Api\InvestmentController@deleteInvestmentIdea');
    Route::get('getInvestmentFilters', 'Api\InvestmentController@getInvestmentFilters');

    
//  Home Screen Routes
    Route::get('getTrendingServices', 'Api\CategoryController@getTrendingServices');
    Route::get('getTrendingCategories', 'Api\CategoryController@getTrendingCategories');
    Route::post('getCategoryMetaTags', 'Api\CategoryController@getCategoryMetaTags');
    Route::get('getPromotedBusinesses', 'Api\BusinessController@getPromotedBusinesses');
    Route::get('getPremiumBusinesses', 'Api\BusinessController@getPremiumBusinesses');
    Route::get('getAllServices', 'Api\CategoryController@getAllServices');

// Contactus Route
    Route::post('contactUs','Api\UsersController@contactUs');

// Agent Request Route
    Route::post('addAgentRequest','Api\UsersController@addAgentRequest');

// Business Owner Route
    Route::post('getOwnerInfo','Api\OwnerController@getOwnerInfo');
    Route::post('addOwner','Api\OwnerController@addOwner');
    Route::post('editOwner','Api\OwnerController@editOwner');
    Route::post('getOwners','Api\OwnerController@getOwners');
    Route::post('deleteOwner','Api\OwnerController@deleteOwner');
    Route::post('saveOwnerProfilePicture', 'Api\OwnerController@saveProfilePicture');

// Chats Route
    Route::post('sendEnquiry', 'Api\ChatsController@sendEnquiry');
    Route::post('sendEnquiryToCustomer', 'Api\ChatsController@sendEnquiryToCustomer');
    Route::post('getThreadListing', 'Api\ChatsController@getThreadListing');
    Route::post('getThreadMessages', 'Api\ChatsController@getThreadMessages');
    Route::post('sendEnquiryMessage', 'Api\ChatsController@sendEnquiryMessage');
    Route::get('getUnreadThreadsCount', 'Api\ChatsController@getUnreadThreadsCount');
    Route::post('getNewThreadMessages', 'Api\ChatsController@getNewThreadMessages');
    Route::post('deleteThread', 'Api\ChatsController@deleteThread');


// Get All countrie,states and cities for address component
    Route::get('getAddressMaster', 'Api\UsersController@getAddressMaster');

// Subscription Route
    Route::get('getSubscriptionPlanList','Api\SubscriptionController@getSubscriptionPlanList');
    Route::post('getSubscriptionPlanDetail','Api\SubscriptionController@getSubscriptionPlanDetail');

// Autocomplete Route
    Route::post('getSearchAutocomplete','Api\BusinessController@getSearchAutocomplete');
    Route::post('getSearchBusinesses','Api\BusinessController@getSearchBusinesses');

// Business approved
    Route::post('getBusinessApproved','Api\BusinessController@getBusinessApproved');

// Get subscription List
    Route::get('getSubscriptionPlanList','Api\SubscriptionController@getSubscriptionPlanList');
    Route::get('getCurrentSubscriptionPlan','Api\SubscriptionController@getCurrentSubscriptionPlan');
});

// Add membership request

    Route::post('sendMembershipRequest','Api\BusinessController@sendMembershipRequest');

// Get CMS
    Route::get('getCMSList','Api\UsersController@getCms');

// send OTP for user registeration
    Route::post('sendRegisterOTP', 'Api\UsersController@sendRegisterOTP');

// update notification
     Route::post('updateNotificationToken', 'Api\UsersController@updateNotificationToken');

//  notification list
     Route::post('notificationList', 'Api\UsersController@notificationList');
     Route::post('deleteNotification','Api\UsersController@deleteNotification');

