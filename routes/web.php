<?php

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

Route::get('/', function () {
	return Redirect::to('/admin/dashboard');
});

Route::group(['prefix' => '/admin'], function () {

	Route::get('/', function () {
            return Redirect::to('/dashboard');
	});

	Route::get('/login', 'Auth\LoginController@index');
	Route::post('/logincheck', 'Auth\LoginController@authenticate');
	Route::get('/logout', 'Admin\DashboardController@getLogout');
	Route::get('/dashboard', 'Admin\DashboardController@index');
	Route::get('/analytics', 'Admin\DashboardController@siteAnalytics');

//	Category Routes
	Route::get('/categories', 'Admin\CategoryController@index');
	Route::get('/category/create', 'Admin\CategoryController@create');
	Route::get('/category/edit/{id}', 'Admin\CategoryController@edit');
	Route::get('/category/delete/{id}', 'Admin\CategoryController@delete');
	Route::post('/category/savecategory', 'Admin\CategoryController@save');
	Route::post('/getParentCategory', 'Admin\CategoryController@getParentCategory');

//      Sub-Category Routes
    Route::get('/category/subcategories/{parentId}', 'Admin\CategoryController@subCategoriesListing');
    Route::get('/category/subcategory/{parentId}/addsubcategory', 'Admin\CategoryController@addSubcategory');
    Route::post('/category/subcategory/{parentId}/savesubcategory', 'Admin\CategoryController@saveSubcategory');
    Route::get('/category/subcategory/{parentId}/editsubcategory/{editId}', 'Admin\CategoryController@editSubcategory');
    Route::get('/category/subcategory/{parentId}/deletesubcategory/{editId}', 'Admin\CategoryController@deleteSubcategory');

//      Email Templates Routes
	Route::get('/templates', 'Admin\EmailTemplatesController@index');
	Route::get('/addtemplate', 'Admin\EmailTemplatesController@add');
	Route::get('/edittemplate/{id}', 'Admin\EmailTemplatesController@edit');
	Route::get('/deletetemplate/{id}', 'Admin\EmailTemplatesController@delete');
	Route::post('/savetemplate', 'Admin\EmailTemplatesController@save');


//	Subscription Routes
	Route::get('/subscriptions', 'Admin\SubscriptionsController@index');
	Route::get('/addsubscription', 'Admin\SubscriptionsController@add');
	Route::post('/savesubscription', 'Admin\SubscriptionsController@save');
	Route::get('/editsubscription/{id}', 'Admin\SubscriptionsController@edit');
	Route::get('/deletesubscription/{id}', 'Admin\SubscriptionsController@delete');

//	Users Routes
	Route::any('/users', 'Admin\UserController@index');
	Route::get('/adduser', 'Admin\UserController@add');
	Route::get('/addagent', 'Admin\UserController@addAgent');
	Route::post('/saveuser', 'Admin\UserController@save');
	Route::get('/edituser/{id}', 'Admin\UserController@edit');
	Route::get('/editagent/{id}', 'Admin\UserController@editAgent');
	Route::get('/deleteuser/{id}', 'Admin\UserController@delete');
	Route::get('/activeuser/{id}', 'Admin\UserController@active');
	Route::get('/activate/{id}', 'Admin\UserController@setUserActive');
	Route::get('/harddeleteuser/{id}', 'Admin\UserController@hardDelete');


//	User's business Routes
	Route::any('/allbusiness', 'Admin\BusinessController@getAllBusinesses');
	Route::get('/allbusiness/{id}', 'Admin\BusinessController@getAllBusinessesByCategory');
	Route::get('/premiumbusiness', 'Admin\BusinessController@getPremiumBusinesses');
	Route::get('/user/business/{id}', 'Admin\BusinessController@index');
	Route::get('/user/business/add/{id}', 'Admin\BusinessController@add');
	Route::post('/user/business/save', 'Admin\BusinessController@save');
	Route::get('/user/business/edit/{id}', 'Admin\BusinessController@edit');
	Route::get('/user/business/delete/{id}', 'Admin\BusinessController@delete');
	Route::post('/search/subcategory', 'Admin\BusinessController@getSubCategotyById');
	Route::post('/search/businessmetatags', 'Admin\BusinessController@getBusinessMetaTags');
	Route::post('/search/addCategotyHierarchy', 'Admin\BusinessController@addCategotyHierarchy');
	Route::post('/user/business/approved', 'Admin\BusinessController@setStatusBusinessApproved');
	Route::post('/user/business/rejected', 'Admin\BusinessController@businessRejected');
	Route::post('/remove/businessimage', 'Admin\BusinessController@removeBusinessImage');
	Route::post('/user/business/savebusinessinfo', 'Admin\BusinessController@saveBusinessInfo');
	Route::post('/user/business/savecontactinfo', 'Admin\BusinessController@saveContactInfo');
	Route::post('/user/business/saveworkinghours', 'Admin\BusinessController@saveWorkingHours');
	Route::post('/user/business/savesocialprofiles', 'Admin\BusinessController@saveSocialProfiles');
	Route::post('/user/business/savesocialactivities', 'Admin\BusinessController@saveSocialActivities');
	Route::post('/user/business/savecategryhierarchy', 'Admin\BusinessController@saveCategryHierarchy');



//      NewsLetters Routes
    Route::get('/newsletter', 'Admin\NewsletterController@index');
    Route::get('/newsletter/create', 'Admin\NewsletterController@create');
    Route::get('/newsletter/edit/{id}', 'Admin\NewsletterController@edit');
    Route::get('/newsletter/delete/{id}', 'Admin\NewsletterController@delete');
    Route::post('/newsletter/save', 'Admin\NewsletterController@save');
    Route::get('/newsletter/savesend/{id}', 'Admin\NewsletterController@updateNotifySubscriberStatus');

//	User's business service Routes
	Route::get('/user/business/service/{id}', 'Admin\ServiceController@index');
	Route::get('/user/business/service/add/{id}', 'Admin\ServiceController@add');
	Route::post('/user/business/service/save', 'Admin\ServiceController@save');
	Route::get('/user/business/service/edit/{id}', 'Admin\ServiceController@edit');
	Route::get('/user/business/service/delete/{id}', 'Admin\ServiceController@delete');
	Route::post('/remove/serviceimage', 'Admin\ServiceController@removeServiceImage');

//	User's business product Routes
	Route::get('/user/business/product/{id}', 'Admin\ProductController@index');
	Route::get('/user/business/product/add/{id}', 'Admin\ProductController@add');
	Route::post('/user/business/product/save', 'Admin\ProductController@save');
	Route::get('/user/business/product/edit/{id}', 'Admin\ProductController@edit');
	Route::get('/user/business/product/delete/{id}', 'Admin\ProductController@delete');
	Route::post('/remove/productimage', 'Admin\ProductController@removeProductImage');

//	User's business owner Routes
	Route::get('/user/business/owner/{id}', 'Admin\OwnerController@index');
	Route::get('/user/business/owner/add/{id}', 'Admin\OwnerController@add');
	Route::post('/user/business/owner/save', 'Admin\OwnerController@save');
	Route::get('/user/business/owner/edit/{id}', 'Admin\OwnerController@edit');
	Route::get('/user/business/owner/delete/{id}', 'Admin\OwnerController@delete');

//	User's business Membership plan Routes
	Route::get('/user/business/membership/{id}', 'Admin\MembershipController@index');
	Route::get('/user/business/membership/add/{id}', 'Admin\MembershipController@add');
	Route::post('/user/business/membership/save', 'Admin\MembershipController@save');
	Route::get('/user/business/membership/edit/{id}', 'Admin\MembershipController@edit');
	Route::get('/user/business/membership/delete/{id}', 'Admin\MembershipController@delete');

//      Investment Ideas Routes
	Route::get('/investmentideas', 'Admin\InvestmentController@index');
	Route::get('/investmentideas/add', 'Admin\InvestmentController@add');
	Route::post('/investmentideas/save', 'Admin\InvestmentController@save');
	Route::get('/investmentideas/edit/{id}', 'Admin\InvestmentController@edit');
	Route::get('/investmentideas/delete/{id}', 'Admin\InvestmentController@delete');

//     	Trending Services Routes
    Route::get('/getAllTrendingServices', 'Admin\CategoryController@getAllTrendingServices');
    Route::post('/updateTrendingService', 'Admin\CategoryController@updateTrendingService');

//      Trending Category Routes
    Route::get('/getAllTrendingCategory', 'Admin\CategoryController@getAllTrendingCategory');
    Route::post('/updateTrendingCategory', 'Admin\CategoryController@updateTrendingCategory');

//      Promoted Businesses Routes
    Route::get('/getAllPromotedBusinesses', 'Admin\BusinessController@getAllPromotedBusinesses');
    Route::post('/updatePromotedBusinesses', 'Admin\BusinessController@updatePromotedBusinesses');

    Route::get('/notifications', 'Admin\DashboardController@notifications');
    Route::get('/send/{type}/notification', 'Admin\DashboardController@sendNotification');
    Route::get('/getPushNotification', 'Admin\DashboardController@getPushNotification');
    Route::post('/sendPushNotification', 'Admin\DashboardController@sendPushNotification');

//	    Agent Routes
	Route::get('agents', 'Admin\AgentController@index');
	Route::get('agentrequest/{id}', 'Admin\AgentController@agentRequest');

//      Country Routes
	Route::get('/country', 'Admin\CountryController@index');
    Route::get('/addcountry', 'Admin\CountryController@add');
	Route::get('/editcountry/{id}', 'Admin\CountryController@edit');
	Route::get('/deletecountry/{id}', 'Admin\CountryController@delete');
	Route::post('/savecountry', 'Admin\CountryController@save');

//      State Routes
	Route::get('/state', 'Admin\StateController@index');
    Route::get('/addstate', 'Admin\StateController@add');
	Route::get('/editstate/{id}', 'Admin\StateController@edit');
	Route::get('/deletestate/{id}', 'Admin\StateController@delete');
	Route::post('/savestate', 'Admin\StateController@save');

//      City Routes
	Route::get('/city', 'Admin\CityController@index');
    Route::get('/addcity', 'Admin\CityController@add');
	Route::get('/editcity/{id}', 'Admin\CityController@edit');
	Route::get('/deletecity/{id}', 'Admin\CityController@delete');
	Route::post('/savecity', 'Admin\CityController@save');

// Membership request Route
	Route::any('/membershiprequest', 'Admin\SubscriptionsController@membershipRequest');
	Route::get('/membershipapprove/{id}/{status}', 'Admin\SubscriptionsController@membershipApprove');
	Route::post('/membershipreject', 'Admin\SubscriptionsController@membershipReject');

// CMS template Routes
	Route::get('/cms', 'Admin\CmsController@index');
	Route::get('/addcms', 'Admin\CmsController@add');
	Route::get('/editcms/{id}', 'Admin\CmsController@edit');
	Route::get('/deletecms/{id}', 'Admin\CmsController@delete');
	Route::post('/savecms', 'Admin\CmsController@save');
	Route::get('/searchterm', 'Admin\CmsController@getSearchTerm');

// Branding Routes
	Route::get('/branding', 'Admin\CmsController@brandingImage');
	Route::post('/savebranding', 'Admin\CmsController@savebrandingImage');
	Route::get('/deletebranding', 'Admin\CmsController@deletebrandingImage');

// OTP routes
	Route::get('/otp', 'Admin\UserController@getOtpList');
	Route::get('/editotp/{id}', 'Admin\UserController@editOtp');
	Route::get('/sendotp/{id}/{type}', 'Admin\UserController@sendOtp');
	Route::post('/saveotp', 'Admin\UserController@saveOtp');

});
