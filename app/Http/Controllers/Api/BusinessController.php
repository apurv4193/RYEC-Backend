<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Helpers;
use Config;
use Image;
use File;
use DB;
use Input;
use Redirect;
use App\Business;
use App\BusinessImage;
use App\Owners;
use App\OwnerChildren;
use App\OwnerSocialActivity;
use App\User;
use App\UserAgentOTP;
use App\UserMetaData;
use App\UserRole;
use App\Category;
use App\BusinessRatings;
use App\BusinessActivities;
use App\BusinessWorkingHours;
use App\Product;
use App\ProductImage;
use App\Service;
use App\Metatag;
use App\Notification;
use App\MembershipRequest;
use App\NotificationList;
use App\TempSearchTerm;
use App\Branding;
use Crypt;
use Response;
use Carbon\Carbon;
use Mail;
use Session;
use Validator;
use JWTAuth;
use JWTAuthException;
use Cache;
use Storage;
use \stdClass;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Cviebrock\EloquentSluggable\Services\SlugService;

class BusinessController extends Controller
{
    public function __construct()
    {
        $this->objBusiness = new Business();
        $this->objMembershipRequest = new MembershipRequest();
        $this->objBusinessImage = new BusinessImage();
        $this->objBusinessActivities = new BusinessActivities();
        $this->objBusinessWorkingHours = new BusinessWorkingHours();
        $this->objUser = new User();
        $this->objUserAgentOTP = new UserAgentOTP();
        $this->objUserMetaData = new UserMetaData();
        $this->objUserRole = new UserRole();
        $this->objBusinessRatings = new BusinessRatings();
        $this->objCategory = new Category();
        $this->objProduct = new Product();
        $this->objProductImage = new ProductImage();
        $this->objService = new Service();
        $this->objOwner = new Owners();
        $this->objOwnerChildren = new OwnerChildren();
        $this->objOwnerSocialActivity = new OwnerSocialActivity();
        $this->objMetatag = new Metatag();

        $this->PRODUCT_ORIGINAL_IMAGE_PATH = Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH');
        $this->PRODUCT_THUMBNAIL_IMAGE_PATH = Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH');

        $this->BUSINESS_ORIGINAL_IMAGE_PATH = Config::get('constant.BUSINESS_ORIGINAL_IMAGE_PATH');
        $this->BUSINESS_THUMBNAIL_IMAGE_PATH = Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH');

        $this->businessImageThumbImageHeight = Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_HEIGHT');
        $this->businessImageThumbImageWidth = Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_WIDTH');

        $this->SERVICE_ORIGINAL_IMAGE_PATH = Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH');
        $this->SERVICE_THUMBNAIL_IMAGE_PATH = Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH');

        $this->USER_ORIGINAL_IMAGE_PATH = Config::get('constant.USER_ORIGINAL_IMAGE_PATH');
        $this->USER_THUMBNAIL_IMAGE_PATH = Config::get('constant.USER_THUMBNAIL_IMAGE_PATH');
        $this->USER_PROFILE_PIC_WIDTH = Config::get('constant.USER_PROFILE_PIC_WIDTH');
        $this->USER_PROFILE_PIC_HEIGHT = Config::get('constant.USER_PROFILE_PIC_HEIGHT');

        $this->OWNER_ORIGINAL_IMAGE_PATH = Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH');
        $this->OWNER_THUMBNAIL_IMAGE_PATH = Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH');
        $this->OWNER_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.OWNER_THUMBNAIL_IMAGE_WIDTH');
        $this->OWNER_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.OWNER_THUMBNAIL_IMAGE_HEIGHT');

        $this->categoryLogoOriginalImagePath = Config::get('constant.CATEGORY_LOGO_ORIGINAL_IMAGE_PATH');
        $this->categoryLogoThumbImagePath = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH');
        $this->categoryLogoThumbImageHeight = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_HEIGHT');
        $this->categoryLogoThumbImageWidth = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_WIDTH');

        $this->categoryBannerImagePath = Config::get('constant.CATEGORY_BANNER_ORIGINAL_IMAGE_PATH');

        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('business-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));

        $this->catgoryTempImage = Config::get('constant.CATEGORY_TEMP_PATH');
    }


    /**
     * Get Agent Businesses
     */
    public function getAgentBusinesses(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 0;
        $outputArray = [];
        $requestData = array_map('trim', $request->all());
        try
        {
            $validator = Validator::make($requestData, [
                'agent_id' => 'required'
            ]);
            if ($validator->fails())
            {
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray, $statusCode);
            }
            $agent_id = $requestData['agent_id'];
            $filters = [];
            $filters['created_by'] = $agent_id;
            if (!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                if (isset($request->limit) && !empty($request->limit))
                {
                    $filters['take'] = $request->limit;
                    $filters['skip'] = 0;
                }
                elseif (isset($request->page) && !empty($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
                if (isset($request->sortBy) && !empty($request->sortBy))
                {
                    if ($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                }
            }
            else
            {
                if (isset($request->page) && !empty($request->page))
                {
                    $offset = Helpers::getOffset($pageNo);
                    $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
                if (isset($request->sortBy) && !empty($request->sortBy))
                {
                    if ($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                }
            }
            if (isset($request->sortBy) && !empty($request->sortBy) && $request->sortBy == 'ratings')
            {
                $filters['sortBy'] = 'ratings';
                $agentBusinessData = $this->objBusiness->getBusinessesByRating($filters);
            }
            else
            {
                if (!isset($request->sortBy) && empty($request->sortBy))
                {
                    $filters['sortBy'] = 'promoted';
                }
                $agentBusinessData = $this->objBusiness->getAll($filters);
            }

            if ($agentBusinessData && count($agentBusinessData) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.recently_added_business_fetched_successfully');
                $statusCode = 200;
                $outputArray['businessesTotalCount'] = (isset($agentBusinessData) && count($agentBusinessData) > 0) ? count($agentBusinessData) : 0;

                $outputArray['data'] = array();
                $i = 0;
                foreach ($agentBusinessData as $key => $value)
                {
                    $outputArray['data'][$i]['id'] = $value->id;
                    $outputArray['data'][$i]['name'] = $value->name;
                    $outputArray['data'][$i]['business_slug'] = (isset($value->business_slug) && !empty($value->business_slug)) ? $value->business_slug : '';
                    $outputArray['data'][$i]['user_name'] = (isset($value->user) && !empty($value->user) && !empty($value->user->name)) ? $value->user->name : '';

                    $outputArray['data'][$i]['owners'] = '';

                    if($value->owners && count($value->owners) > 0)
                    {
                        $owners = [];
                        foreach($value->owners as $owner)
                        {
                            $owners[] = $owner->full_name;
                        }
                        if(!empty($owners))
                        {
                            $outputArray['data'][$i]['owners'] = implode(', ',$owners);
                        }
                    }

                    $outputArray['data'][$i]['categories'] = array();
                    if (!empty($value->category_id) && $value->category_id != '')
                    {
                        $categoryIdsArray = (explode(',', $value->category_id));
                        if (count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach ($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if (count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH') . $categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH') . $categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                                    $outputArray['data'][$i]['categories'][$j]['category_logo'] = $catLogoPath;

                                    $outputArray['data'][$i]['categories'][$j]['parent_category_id'] = $categoryData->parent;
                                    $j++;
                                }
                            }
                        }
                    }
                    $outputArray['data'][$i]['parent_categories'] = array();
                    $parentCatArray = [];
                    if (!empty($value->parent_category) && $value->parent_category != '')
                    {
                        $parentcategoryIdsArray = (explode(',', $value->parent_category));
                        if (count($parentcategoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach ($parentcategoryIdsArray as $pIdKey => $pIdValue)
                            {
                                $parentcategoryData = Category::find($pIdValue);
                                if (count($parentcategoryData) > 0 && !empty($parentcategoryData))
                                {
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_id'] = $parentcategoryData->id;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_name'] = $parentcategoryData->name;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_slug'] = (!empty($parentcategoryData->category_slug)) ? $parentcategoryData->category_slug : '';
                                    $catLogoPath = (($parentcategoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH') . $parentcategoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH') . $parentcategoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                                    $outputArray['data'][$i]['parent_categories'][$j]['category_logo'] = $catLogoPath;

                                    $parentCatArray[] =  $parentcategoryData->name;

                                    $j++;
                                }
                            }
                        }
                    }

                    $outputArray['data'][$i]['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                    $outputArray['data'][$i]['categories_name_list'] = $outputArray['data'][$i]['parent_category_name'];
                    $outputArray['data'][$i]['approved'] = $value->approved;
                    $outputArray['data'][$i]['descriptions'] = $value->description;
                    $outputArray['data'][$i]['phone'] = $value->phone;
                    $outputArray['data'][$i]['mobile'] = $value->mobile;
                    $outputArray['data'][$i]['latitude'] = (!empty($value->latitude)) ? $value->latitude : '';
                    $outputArray['data'][$i]['longitude'] = (!empty($value->longitude)) ? $value->longitude : '';
                    $outputArray['data'][$i]['address'] = (!empty($value->address)) ? $value->address : '';
                    $outputArray['data'][$i]['street_address'] = $value->street_address;
                    $outputArray['data'][$i]['locality'] = $value->locality;
                    $outputArray['data'][$i]['country'] = $value->country;
                    $outputArray['data'][$i]['state'] = $value->state;
                    $outputArray['data'][$i]['city'] = $value->city;
                    $outputArray['data'][$i]['taluka'] = $value->taluka;
                    $outputArray['data'][$i]['district'] = $value->district;
                    $outputArray['data'][$i]['pincode'] = $value->pincode;
                    $outputArray['data'][$i]['country_code'] = $value->country_code;
                    $outputArray['data'][$i]['email_id'] = (!empty($value->email_id)) ? $value->email_id : '';
                    $outputArray['data'][$i]['website_url'] = (!empty($value->website_url)) ? $value->website_url : '';
                    $outputArray['data'][$i]['membership_type'] = $value->membership_type;
                    if($value->membership_type == 2)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                    }
                    elseif($value->membership_type == 1)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                    }
                    else
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                    }

                    $imgThumbUrl = ((isset($value->businessImagesById) && !empty($value->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH') . $value->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH') . $value->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $outputArray['data'][$i]['business_image'] = $imgThumbUrl;

                    $businessLogoThumbImgPath = ((isset($value->business_logo) && !empty($value->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) : $imgThumbUrl;
                    $outputArray['data'][$i]['logo_thumbnail'] = $businessLogoThumbImgPath;

                    $i++;
                }

                $this->log->info('API agent businesses get successfully', array('login_user_id' => $user->id));
            } else {
                $this->log->info('API agent businesses not found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while get agent businesses', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Delete Business Image
     */
    public function deleteBusinessImage(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $outputArray = [];
        $requestData = $request->all();
        try
        {
            DB::beginTransaction();
            $validator = Validator::make($requestData, [
                'id' => 'required'
            ]);
            if ($validator->fails())
            {
                DB::rollback();
                $this->log->error('API validation failed while delete business images', array('login_user_id' => $user->id));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray, $statusCode);
            }
            $id = $requestData['id'];
            $businessId = (isset($requestData['business_id']) && $requestData['business_id'] > 0) ? $requestData['business_id'] : 0;
            $businessImageData = BusinessImage::find($id);
            if($businessImageData)
            {
                $response = BusinessImage::where('id', $id)->where('business_id', $businessId)->delete();
                $businessImageName = $businessImageData->image_name;
                $pathOriginal =  $this->BUSINESS_ORIGINAL_IMAGE_PATH.$businessImageName;
                $pathThumb = $this->BUSINESS_THUMBNAIL_IMAGE_PATH.$businessImageName;

//              Delete Image From Storage
                $originalImageDelete = Helpers::deleteFileToStorage($businessImageName, $this->BUSINESS_ORIGINAL_IMAGE_PATH, "s3");
		$thumbImageDelete = Helpers::deleteFileToStorage($businessImageName, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, "s3");

//              Deleting Local Files
                File::delete($pathOriginal, $pathThumb);
                if($response)
                {
                    DB::commit();
                    $outputArray['status'] = 1;
                    $outputArray['message'] = trans('apimessages.business_image_deleted_successfully');
                    $statusCode = 200;
                }
                else
                {
                    $outputArray['status'] = 0;
                    $outputArray['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 200;
                }
            }
            else
            {
                $outputArray['status'] = 0;
                $outputArray['message'] = trans('apimessages.business_image_not_found');
                $statusCode = 200;
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while delete business image', array('login_user_id' =>  $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }
    /**
     * Save Business Images
     */
    public function saveBusinessImages(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $outputArray = [];
        $requestData = $request->all();
        try
        {
            DB::beginTransaction();
            $validator = Validator::make($requestData, [
                'business_id' => 'required',
                'business_images.*' => 'mimes:jpeg,jpg,bmp,png,gif|max:52400'
            ]);
            if ($validator->fails())
            {
                DB::rollback();
                $this->log->error('API validation failed while save business images', array('login_user_id' => $user->id));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray, $statusCode);
            }
            $businessId = $requestData['business_id'];

            if (Input::file('business_logo'))
            {
                $logo = Input::file('business_logo');

                if (!empty($logo) && count($logo) > 0)
                {
                    $fileName = '   ' . uniqid() . '.' . $logo->getClientOriginalExtension();

                    $pathOriginal = public_path($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                    $pathThumb = public_path($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);

                    Image::make($logo->getRealPath())->save($pathOriginal);
                    Image::make($logo->getRealPath())->resize($this->businessImageThumbImageWidth, $this->businessImageThumbImageHeight)->save($pathThumb);

                    // if logo exist then delete

                    $businessDetail = $this->objBusiness->find($businessId);
                    $oldLogo = $businessDetail->business_logo;

                    if($oldLogo != '')
                    {
                        $originalImageDelete = Helpers::deleteFileToStorage($oldLogo, $this->BUSINESS_ORIGINAL_IMAGE_PATH, "s3");
                        $thumbImageDelete = Helpers::deleteFileToStorage($oldLogo, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, "s3");
                    }

                    //Uploading on AWS
                    $originalImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                    $thumbImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                    //Deleting Local Files
                    \File::delete($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                    \File::delete($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);

                    $businessDetail->business_logo = $fileName;
                    $businessDetail->save();
                    $response = $businessDetail;
                }
            }
            if (Input::file('business_images'))
            {
                $fileImagesArray = Input::file('business_images');
                if (isset($fileImagesArray) && count($fileImagesArray) > 0 && !empty($fileImagesArray) )
                {
                    foreach($fileImagesArray as $fileImageKey => $fileImageValue)
                    {
                        $fileImgName = 'business_' . str_random(10). '.'. $fileImageValue->getClientOriginalExtension();
                        $pathOriginal = public_path($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileImgName);
                        $pathThumb = public_path($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileImgName);
                        Image::make($fileImageValue->getRealPath())->save($pathOriginal);
                        Image::make($fileImageValue->getRealPath())->resize($this->businessImageThumbImageWidth, $this->businessImageThumbImageHeight)->save($pathThumb);
                        $businessImageInsert = [];
                        $businessImageInsert['business_id'] = $businessId;
                        $businessImageInsert['image_name'] = $fileImgName;
                        $response = BusinessImage::firstOrCreate($businessImageInsert);
//                      Uploading on AWS
                        $originalImage = Helpers::addFileToStorage($fileImgName, $this->BUSINESS_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                        $thumbImage = Helpers::addFileToStorage($fileImgName, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                        //Deleting Local Files
                        File::delete($pathOriginal, $pathThumb);
                    }
                }
            }
            // if(isset($response) && $response)
            // {
                DB::commit();
                $this->log->info('API business images save successfully', array('login_user_id' => $user->id, 'business_id' => $businessId));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.business_images_added_successfully');
                $statusCode = 200;
            // }
            // else
            // {
            //     $this->log->error('API something went wrong while save business images', array('login_user_id' => $user->id));
            //     $outputArray['status'] = 0;
            //     $outputArray['message'] = trans('apimessages.default_error_msg');
            //     $statusCode = 200;
            // }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while save business images', array('login_user_id' =>  $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Save Business
     */
    public function saveBusiness(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $outputArray = [];
        $requestData = $request->all();
        try
        {
//          $requestData = array_map('trim', $requestData);
            DB::beginTransaction();
            $validator = Validator::make($requestData, [
                'user_id' => 'required',
                'name' => ['required', 'max:100'],
                'email_id' => 'email'

            ]);
            if ($validator->fails())
            {
                DB::rollback();
                $this->log->error('API validation failed while save business', array('login_user_id' => $user->id));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray, $statusCode);
            }

            $businessData = [];
            $businessData['user_id'] = $requestData['user_id'];
            $businessData['id'] = (isset($requestData['id']) && $requestData['id'] > 0) ? $requestData['id'] : 0;

            if(isset($requestData['parent_category']) && $requestData['parent_category'] > 0) {
                $businessData['parent_category'] = $requestData['parent_category'];
            }

            if(isset($requestData['metatags'])) {
                $businessData['metatags'] = $requestData['metatags'];
            }

            if(isset($requestData['category_id']))
            {
                $businessData['category_id'] = (isset($requestData['category_id']) && $requestData['category_id'] > 0) ? $requestData['category_id'] : '';
                if($businessData['category_id'] != '') {
                    $businessData['category_hierarchy'] = Helpers::getCategoryHierarchy($businessData['category_id']);
                }
            }

            if(isset($requestData['name'])) {
                $businessData['name'] = $requestData['name'];
            }

            if(isset($requestData['name']))
            {
                $businessData['name'] = $requestData['name'];
                $businessName = trim($businessData['name']);
                if($businessData['id'] == 0)
                {
//                  $businessSlug = (!empty($businessName) &&  $businessName != '') ? Helpers::getSlug($businessName) : NULL;
//                  $businessData['business_slug'] = $businessSlug;
                    $businessSlug = SlugService::createSlug(Business::class, 'business_slug', $businessName);
                    $businessData['business_slug'] = (isset($businessSlug) && !empty($businessSlug)) ? $businessSlug : NULL;
                }
            }

            if(isset($requestData['description'])) {
                $businessData['description'] = $requestData['description'];
            }

            if(isset($requestData['establishment_year'])) {
                $businessData['establishment_year'] = $requestData['establishment_year'];
            }

            if(isset($requestData['country_code'])) {
                $businessData['country_code'] = $requestData['country_code'];
            }

            if(isset($requestData['mobile'])) {
                $businessData['mobile'] = $requestData['mobile'];
            }

            if(isset($requestData['phone'])) {
                $businessData['phone'] = $requestData['phone'];
            }

            if(isset($requestData['address'])) {
                $businessData['address'] = $requestData['address'];
            }

            if(isset($requestData['website_url'])) {
                $businessData['website_url'] = $requestData['website_url'];
            }

            if(isset($requestData['facebook_url'])) {
                $businessData['facebook_url'] = $requestData['facebook_url'];
            }

            if(isset($requestData['twitter_url'])) {
                $businessData['twitter_url'] = $requestData['twitter_url'];
            }

            if(isset($requestData['linkedin_url'])) {
                $businessData['linkedin_url'] = $requestData['linkedin_url'];
            }

            if(isset($requestData['instagram_url'])) {
                $businessData['instagram_url'] = $requestData['instagram_url'];
            }

            if(isset($requestData['latitude']) && isset($requestData['longitude']))
            {
                $businessData['latitude'] = $requestData['latitude'];
                $businessData['longitude'] = $requestData['longitude'];
            }

            if(isset($requestData['street_address'])) {
                $businessData['street_address'] = $requestData['street_address'];
            }

            if(isset($requestData['locality'])) {
                $businessData['locality'] = $requestData['locality'];
            }

            if(isset($requestData['country'])) {
                $businessData['country'] = $requestData['country'];
            }

            if(isset($requestData['state'])) {
                $businessData['state'] = $requestData['state'];
            }

            if(isset($requestData['city'])) {
                $businessData['city'] = $requestData['city'];
            }

            if(isset($requestData['taluka'])) {
                $businessData['taluka'] = $requestData['taluka'];
            }

            if(isset($requestData['district'])) {
                $businessData['district'] = $requestData['district'];
            }

            if(isset($requestData['pincode'])) {
                $businessData['pincode'] = $requestData['pincode'];
            }

            if(isset($requestData['email_id'])) {
                $businessData['email_id'] = $requestData['email_id'];
            }

            if(isset($requestData['suggested_categories'])) {
                $businessData['suggested_categories'] = $requestData['suggested_categories'];
            }


            $businessSaveData = $this->objBusiness->insertUpdate($businessData);
            if($businessSaveData)
            {

                if(!isset($businessData['id']) || $businessData['id'] == 0)
                {
                    $useDetail = User::find($requestData['user_id']);
                    Helpers::sendMessage($useDetail->phone, "Dear ".$useDetail->name.", Welcome to Rajput Yuva Entrepreneur Club, We received your business profile. Our team will review and get in touch with you.");
                }

                DB::commit();
                $bunsinessId = ($businessData['id'] > 0) ? $businessData['id'] : $businessSaveData->id;
                if($bunsinessId > 0)
                {
                    $buisinessDetails = Business::find($bunsinessId);
                    if(isset($requestData['latitude']) && isset($requestData['longitude']))
                    {
                        $business_address_attributes = Helpers::getAddressAttributes($requestData['latitude'], $requestData['longitude']);
                        $business_address_attributes['business_id'] = $bunsinessId;
                        $businessObject = Business::find($bunsinessId);
                        if (!$businessObject->business_address)
                        {
                            $businessObject->business_address()->create($business_address_attributes);
                        }
                        else
                        {
                            $businessObject->business_address()->update($business_address_attributes);
                        }
                    }
                    if(isset($requestData['business_activities']) && !empty($requestData['business_activities']))
                    {
                        foreach($requestData['business_activities'] as $activitiesKey => $activitiesValue)
                        {

                            $activityInsert = [];

                            if($activitiesValue['operation'] == 'delete')
                            {
                                $activity = BusinessActivities::find($activitiesValue['id']);
                                if($activity)
                                    $activity->delete($activitiesValue['id']);
                            }
                            else
                            {
                                $activityInsert['id'] = (isset($activitiesValue['id']) && $activitiesValue['id'] > 0) ? $activitiesValue['id'] : 0;
                                $activityInsert['business_id'] = $bunsinessId;
                                $activityInsert['activity_title'] = (!empty($activitiesValue['activity_title'])) ? $activitiesValue['activity_title'] : NULL;
                                $activitySave = $this->objBusinessActivities->insertUpdate($activityInsert);
                            }
                        }
                    }
                    if(isset($requestData['working_hours']) && !empty($requestData['working_hours']))
                    {
                        $workingHoursDetails = BusinessWorkingHours::where('business_id', $bunsinessId)->orderBy('id', 'DESC')->first();
                        $workingHoursInsert = Helpers::setWorkingHours($requestData['working_hours']);
                        $workingHoursInsert['id'] = (count($workingHoursDetails) > 0 && $workingHoursDetails->id > 0) ? $workingHoursDetails->id : 0;
                        $workingHoursInsert['business_id'] = $bunsinessId;
                        if(isset($requestData['working_hours']['timezone']) && $requestData['working_hours']['timezone'] != '')
                        {
                            $workingHoursInsert['timezone'] = $requestData['working_hours']['timezone'];
                        }

                        $workingHoursSave = $this->objBusinessWorkingHours->insertUpdate($workingHoursInsert);
                    }
                    if($businessData['id'] == 0 && !isset($requestData['id']))
                    {
                        $userData = User::find($requestData['user_id']);
                        if($userData)
                        {
                            $ownerInsert = [];
                            $ownerInsert['id'] = 0;
                            $ownerInsert['business_id'] = $bunsinessId;
                            $ownerInsert['full_name'] = $userData->name;
                            $ownerInsert['gender'] = $userData->gender;
                            $ownerInsert['dob'] = $userData->dob;
                            $ownerInsert['email_id'] = $userData->email;
                            if($userData->profile_pic && !empty($userData->profile_pic))
                            {
                                $imageArray = Helpers::getProfileExtraFields($userData);
                                if($imageArray && !empty($imageArray['profile_pic_original']) && !empty($imageArray['profile_pic_original']))
                                {
                                    $userOriginalImgPath = $imageArray['profile_pic_original'];
                                    $userThumbnailImgPath = $imageArray['profile_pic_thumbnail'];

                                    $userOriginalImageInfo = pathinfo($userOriginalImgPath);
                                    $userThumbnailImageInfo = pathinfo($userThumbnailImgPath);

                                    $ownereExtension = $userOriginalImageInfo['extension'];
                                    $ownerFileName = 'owner_'.uniqid().'.'.$ownereExtension;

                                    $ownerOriginalImgPath = public_path($this->OWNER_ORIGINAL_IMAGE_PATH.$ownerFileName);
                                    $ownerThumbnailImgPath = public_path($this->OWNER_THUMBNAIL_IMAGE_PATH.$ownerFileName);

                                    File::copy($userOriginalImgPath, $ownerOriginalImgPath);
                                    File::copy($userThumbnailImgPath, $ownerThumbnailImgPath);

                                    //Uploading on AWS
                                    $originalOwnerImage = Helpers::addFileToStorage($ownerFileName, $this->OWNER_ORIGINAL_IMAGE_PATH, $ownerOriginalImgPath, "s3");
                                    $thumbOwnerImage = Helpers::addFileToStorage($ownerFileName, $this->OWNER_THUMBNAIL_IMAGE_PATH, $ownerThumbnailImgPath, "s3");

                                    //Deleting Local Files
                                    File::delete($ownerOriginalImgPath, $ownerThumbnailImgPath);
                                    $ownerInsert['photo'] = $ownerFileName;
                                }
                                else{
                                    $ownerInsert['photo'] = NULL;
                                }
                            }
                            else
                            {
                                 $ownerInsert['photo'] = NULL;
                            }
                             $ownerSave = $this->objOwner->insertUpdate($ownerInsert);
                        }
                    }
                    $outputArray['status'] = 1;
                    $outputArray['message'] =  trans('apimessages.business_added_success');
                    $outputArray['data'] =  array();
                    $outputArray['data']['id'] = $buisinessDetails->id;
                    $outputArray['data']['business_slug'] = $buisinessDetails->business_slug;
                    $statusCode = 200;
                }
                else
                {
                    $outputArray['status'] = 0;
                    $outputArray['message'] = trans('apimessages.business_id_not_found');
                    $statusCode = 200;
                }
            }
            else
            {
                DB::rollback();
                $this->log->error('API something went wrong while save business', array('login_user_id' => $user->id));
                $outputArray['status'] = 0;
                $outputArray['message'] = trans('apimessages.default_error_msg');
                $statusCode = 200;
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while save business', array('login_user_id' =>  Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Agent Save User
     */
    public function agentSaveUser(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $outputArray = [];
        $requestData = $request->all();
        try
        {
            DB::beginTransaction();
            $userData = [];
            $userRequestData = array_map('trim', $requestData);
            if(isset($requestData['id']) && $requestData['id'] > 0)
            {
                $validator = Validator::make($userRequestData, [
                    'name' => ['required', 'max:100'],
                    'phone' => 'required|digits:10',
                    'email' => 'email'
                ]);
                if ($validator->fails())
                {
                    DB::rollback();
                    $this->log->error('API validation failed while agent save user', array('login_user_id' => $user->id));
                    $outputArray['status'] = 0;
                    $outputArray['message'] = $validator->messages()->all()[0];
                    $statusCode = 200;
                    return response()->json($outputArray, $statusCode);
                }
                $userData['id'] = $userRequestData['id'];
                $userData['phone'] = $userRequestData['phone'];
                $userData['country_code'] = $userRequestData['country_code'];
                $userData['name'] = $userRequestData['name'];
                $userData['email'] = $userRequestData['email'];
                if(isset($userRequestData['password']) && !empty($userRequestData['password']))
                {
                    $userData['password'] = bcrypt($userRequestData['password']);
                }
            }
            else
            {
                $validator = Validator::make($userRequestData, [
                    'name' => ['required', 'max:100'],
                    'phone' => 'required|digits:10|unique:users,phone',
                    'email' => 'email',
                    'password' =>'required|min:8|max:20',
                ]);
                if ($validator->fails())
                {
                    DB::rollback();
                    $outputArray['status'] = 0;
                    $outputArray['message'] = $validator->messages()->all()[0];
                    $statusCode = 200;
                    return response()->json($outputArray, $statusCode);
                }
                $userData['phone'] = $userRequestData['phone'];
                $userData['country_code'] = $userRequestData['country_code'];
                $userData['name'] = $userRequestData['name'];
                $userData['email'] = $userRequestData['email'];
                $userData['password'] = bcrypt($userRequestData['password']);
            }
            $response = $this->objUser->insertUpdate($userData);
            $agentUserId = (isset($userData['id']) && $userData['id'] > 0) ? $userData['id'] : $response->id;
            if($response)
            {
                DB::commit();
                $this->log->info('API agent save user successfully', array('login_user_id' => $user->id, 'user_id' => $agentUserId));
                $outputArray['status'] = 1;
                $outputArray['message'] =  trans('apimessages.user_data_update_successfully');
                $outputArray['data'] =  array();
                $outputArray['data']['id'] = (isset($userData['id']) && $userData['id'] > 0) ? $userData['id'] : $response->id;
                $statusCode = 200;
            }
            else
            {
                DB::rollback();
                $this->log->error('API something went wrong while agent save user', array('login_user_id' => $user->id));
                $outputArray['status'] = 0;
                $outputArray['message'] = trans('apimessages.default_error_msg');
                $statusCode = 200;
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while agent save user', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Send Add Member OTP
     */
    public function sendAddMemberOTP(Request $request)
    {
        $headerData = $request->header('Platform');
        $user = JWTAuth::parseToken()->authenticate();
        try
        {
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'country_code' => 'required'
            ]);
            if ($validator->fails())
            {
                DB::rollback();
                $this->log->error('API validation failed while send add member OTP', array('login_user_id' => $user->id));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray, $statusCode);
            }
            else
            {
                $phoneNumber = $request->phone;
                $userData = User::where('phone', $request->phone)->where('country_code',$request->country_code)->first();
                if(!$userData)
                {
//                  If user not exists
                    $agentOtp = UserAgentOTP::firstOrCreate(['agent_id' => $user->id, 'phone' => $request->phone]);
                    $agentOtp->otp = Helpers::genrateOTP();
                    $response = Helpers::sendMessage($request->phone,"RYUVA Club - ".$agentOtp->otp." is the OTP for transaction to create new member or register business for member");
                    if($response['status']) {
                        $agentOtp->save();
                        $outputArray['status'] = 1;
                        $outputArray['message'] = trans('apimessages.otp_send_successfully');
                        $outputArray['data'] = new \stdClass();
                        $statusCode = 200;
                    } else {
                        $this->log->error('API something went wrong while send add member OTP', array('login_user_id' => $user->id));
                        $outputArray['status'] = 0;
                        $outputArray['message'] = $response['message'];
                        $outputArray['data'] = new \stdClass();
                        $statusCode = 200;
                    }
                    return response()->json($outputArray, $statusCode);
                }
                else
                {
                    if($userData->singlebusiness) {
                        $outputArray['status'] = 0;
                        $outputArray['message'] = trans('apimessages.user_with_already_one_business');
                        $statusCode = 200;
                        $outputArray['data'] = array();
                        $outputArray['data']['id'] = $userData->id;
                        $outputArray['data']['name'] = $userData->name;
                        $outputArray['data']['email'] = $userData->email;
                        $outputArray['data']['phone'] = $userData->phone;
                        $outputArray['data']['dob'] = $userData->dob;
                        $outputArray['data']['gender'] = $userData->gender;
                    } else {
                        $agentOtp = UserAgentOTP::firstOrCreate(['agent_id' => $user->id, 'phone' => $request->phone]);
                        $agentOtp->otp = Helpers::genrateOTP();
                        $response = Helpers::sendMessage($request->phone,"RYUVA Club - ". $agentOtp->otp." is the OTP for transaction to create new member or register business for member");
                        if($response['status']) {
                            $agentOtp->save();
                            $outputArray['status'] = 1;
                            $outputArray['message'] = trans('apimessages.otp_send_successfully');
                            $statusCode = 200;
                            $outputArray['data'] = array();
                            $outputArray['data']['id'] = $userData->id;
                            $outputArray['data']['name'] = $userData->name;
                            $outputArray['data']['email'] = $userData->email;
                            $outputArray['data']['phone'] = $userData->phone;
                            $outputArray['data']['profile_pic'] = (!empty($userData->profile_pic) && Storage::size($this->USER_THUMBNAIL_IMAGE_PATH .$userData->profile_pic) > 0) ? Storage::url($this->USER_THUMBNAIL_IMAGE_PATH .$userData->profile_pic) : url($this->catgoryTempImage);
                            $outputArray['data']['dob'] = $userData->dob;
                            $outputArray['data']['gender'] = $userData->gender;
                        } else {
                            $this->log->error('API something went wrong while send add member OTP', array('login_user_id' => $user->id));
                            $outputArray['status'] = 0;
                            $outputArray['message'] = $response['message'];
                            $outputArray['data'] = new \stdClass();
                            $statusCode = 200;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while send add member OTP', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }


    public function verifyAgentOTP(Request $request)
    {
        $headerData = $request->header('Platform');
        $user = JWTAuth::parseToken()->authenticate();
        $requestData = Input::all();

        try
        {
            $validator = Validator::make($request->all(), [
                'otp' => 'required',
                'phone' => 'required',
            ]);
            if ($validator->fails())
            {
                DB::rollback();
                $this->log->error('API validation failed while save business', array('login_user_id' => $user->id));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray, $statusCode);
            }
            else
            {
                $phoneNumber = $request->phone;
                $otp = $request->otp;
                $userData = UserAgentOTP::where('phone', $requestData['phone'])->where('agent_id', $user->id)->where('otp', $requestData['otp'])->first();
                if($userData)
                {
                    $otp_sent = Carbon::parse($userData->updated_at);
                    $now = Carbon::now();
                    $diff = $otp_sent->diffInMinutes($now);
                    if($diff > 5) {
                        $statusCode = 200;
                        $outputArray['status'] = 0;
                        $outputArray['message'] = trans('apimessages.otp_expired');
                        $outputArray['data'] = new \stdClass();
                    } else {
                        $userData = User::where('phone', $request->phone)->where('country_code',$request->country_code)->first();
                        if(!$userData)
                        {
                            $outputArray['status'] = 1;
                            $outputArray['message'] = trans('apimessages.new_user');
                            $outputArray['data'] = new \stdClass();
                            $statusCode = 200;
                            $agentUserOTPDelete = UserAgentOTP::where('agent_id', $user->id)->where('phone', $requestData['phone'])->where('otp', $requestData['otp'])->delete();
                            return response()->json($outputArray, $statusCode);
                        }
                        else
                        {
                            if($userData->singlebusiness) {
                                $outputArray['status'] = 0;
                                $outputArray['message'] = trans('apimessages.user_with_already_one_business');
                                $statusCode = 200;
                                $outputArray['data'] = array();
                                $outputArray['data']['id'] = $userData->id;
                                $outputArray['data']['name'] = $userData->name;
                                $outputArray['data']['email'] = $userData->email;
                                $outputArray['data']['phone'] = $userData->phone;
                                $outputArray['data']['dob'] = $userData->dob;
                                $outputArray['data']['gender'] = $userData->gender;
                            } else {
                                $outputArray['status'] = 1;
                                $outputArray['message'] = trans('apimessages.user_verified_success');
                                $statusCode = 200;
                                $outputArray['data'] = array();
                                $outputArray['data']['id'] = $userData->id;
                                $outputArray['data']['name'] = $userData->name;
                                $outputArray['data']['email'] = $userData->email;
                                $outputArray['data']['phone'] = $userData->phone;
                                $outputArray['data']['dob'] = $userData->dob;
                                $outputArray['data']['gender'] = $userData->gender;
                                $agentUserOTPDelete = UserAgentOTP::where('agent_id', $user->id)->where('phone', $requestData['phone'])->where('otp', $requestData['otp'])->delete();
                            }
                        }
                    }
                }
                else
                {
                    $this->log->error('API something went wrong while verify agent OTP', array('login_user_id' => $user->id));
                    $statusCode = 200;
                    $outputArray = ['status' => 0, 'message' => trans('apimessages.invalid_otp')];
                    $outputArray['data'] = new \stdClass();
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while verify agent OTP', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }

        return response()->json($outputArray, $statusCode);
    }

    /**
     * Get Near By Businesses
     */
    public function getNearByBusinesses(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 0;
        $outputArray = [];
        try
        {
            $filters = [];
            $filters['approved'] = 1;
            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                if(isset($request->page) && $request->page != 0)
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
            }
            else
            {
                if(isset($request->page) && $request->page != 0)
                {
                    $offset = Helpers::getOffset($pageNo);
                    $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
            }
            if(isset($request->sortBy) && !empty($request->sortBy) && $request->sortBy == 'ratings')
            {
                $filters['sortBy'] = 'ratings';
                $getBusinessListingData = $this->objBusiness->getBusinessesByRating($filters);
            }
            elseif (isset($request->sortBy) && $request->sortBy == 'nearMe' && isset($request->radius) && !empty ($request->radius) && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
            {
                $filters['sortBy'] = 'nearMe';
                $filters['radius'] = $request->radius;
                $filters['latitude'] = $request->latitude;
                $filters['longitude'] = $request->longitude;
                $getBusinessListingData = $this->objBusiness->getBusinessesByNearMe($filters);
                while($getBusinessListingData->count() < 15) {
                    $filters['radius'] = $filters['radius']*2;
                    $getBusinessListingData = $this->objBusiness->getBusinessesByNearMe($filters);
                }
            }
            else
            {
                $filters['orderBy'] = 'promoted';
                $getBusinessListingData = $this->objBusiness->getAll($filters);
            }
            if($getBusinessListingData && count($getBusinessListingData) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.nearby_me_business_fetched_successfully');
                $statusCode = 200;
                $outputArray['data'] = array();
                $i = 0;
                foreach ($getBusinessListingData as $key => $value)
                {
                    $outputArray['data'][$i]['id'] = $value->id;
                    $outputArray['data'][$i]['name'] = $value->name;
                    $outputArray['data'][$i]['business_slug'] = (isset($value->business_slug) && !empty($value->business_slug)) ? $value->business_slug : '';
                    $outputArray['data'][$i]['categories'] = array();
                    if(!empty($value->category_id) && $value->category_id != '')
                    {
                        $categoryIdsArray = (explode(',', $value->category_id));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    // if(!empty($categoryData->cat_logo))
                                    // {
                                    //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                    // }
                                    $catLogoPath = (($categoryData->cat_logo != '')) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));


                                    $outputArray['data'][$i]['categories'][$j]['category_logo'] = $catLogoPath;

                                    $outputArray['data'][$i]['categories'][$j]['parent_category_id'] = $categoryData->parent;
                                    $j++;
                                }
                            }
                        }
                    }
                    $parentCatArray = [];
                    $outputArray['data'][$i]['parent_categories'] = array();
                    if (!empty($value->parent_category) && $value->parent_category != '')
                    {
                        $parentcategoryIdsArray = (explode(',', $value->parent_category));
                        if (count($parentcategoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach ($parentcategoryIdsArray as $pIdKey => $pIdValue)
                            {
                                $parentcategoryData = Category::find($pIdValue);
                                if (count($parentcategoryData) > 0 && !empty($parentcategoryData))
                                {
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_id'] = $parentcategoryData->id;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_name'] = $parentcategoryData->name;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_slug'] = (!empty($parentcategoryData->category_slug)) ? $parentcategoryData->category_slug : '';
                                    $catLogoPath = (($parentcategoryData->cat_logo != '')) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH') . $parentcategoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                                    $outputArray['data'][$i]['parent_categories'][$j]['category_logo'] = $catLogoPath;

                                    $parentCatArray[] =  $parentcategoryData->name;
                                    $j++;
                                }
                            }
                        }
                    }

                    $outputArray['data'][$i]['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                    $outputArray['data'][$i]['categories_name_list'] = $outputArray['data'][$i]['parent_category_name'];


                    $outputArray['data'][$i]['owners'] = '';

                        if($value->owners && count($value->owners) > 0)
                        {
                            $owners = [];
                            foreach($value->owners as $owner)
                            {
                                $owners[] = $owner->full_name;
                            }
                            if(!empty($owners))
                            {
                                $outputArray['data'][$i]['owners'] = implode(', ',$owners);
                            }
                        }

                    $outputArray['data'][$i]['descriptions'] = $value->description;
                    $outputArray['data'][$i]['phone'] = $value->phone;
                    $outputArray['data'][$i]['country_code'] = $value->country_code;
                    $outputArray['data'][$i]['mobile'] = $value->mobile;
                    $outputArray['data'][$i]['email_id'] = (!empty($value->email_id)) ? $value->email_id : '';

                    $outputArray['data'][$i]['address'] = $value->address;
                    $outputArray['data'][$i]['street_address'] = $value->street_address;
                    $outputArray['data'][$i]['locality'] = $value->locality;
                    $outputArray['data'][$i]['country'] = $value->country;
                    $outputArray['data'][$i]['state'] = $value->state;
                    $outputArray['data'][$i]['city'] = $value->city;
                    $outputArray['data'][$i]['taluka'] = $value->taluka;
                    $outputArray['data'][$i]['district'] = $value->district;
                    $outputArray['data'][$i]['pincode'] = $value->pincode;
                    $outputArray['data'][$i]['latitude'] = (!empty($value->latitude)) ? $value->latitude : 0;
                    $outputArray['data'][$i]['longitude'] = (!empty($value->longitude)) ? $value->longitude : 0;


                    $outputArray['data'][$i]['website_url'] = (!empty($value->website_url)) ? $value->website_url : '';
                    $outputArray['data'][$i]['membership_type'] = $value->membership_type;
                    if($value->membership_type == 2)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                    }
                    elseif($value->membership_type == 1)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                    }
                    else
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                    }

                    // if(isset($value->businessImagesById) && !empty($value->businessImagesById->image_name))
                    // {
                    //     // $img_thumb_path = $this->BUSINESS_THUMBNAIL_IMAGE_PATH.$value->businessImagesById->image_name;
                    //     // $img_thumb_url = (!empty($img_thumb_path) && file_exists($img_thumb_path)) ? $img_thumb_path : '';

                    // }
                    // else
                    // {
                    //     $outputArray['data'][$i]['business_image'] = url($this->catgoryTempImage);
                    // }

                    $imgThumbUrl = ((isset($value->businessImagesById) && !empty($value->businessImagesById->image_name))) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $outputArray['data'][$i]['business_image'] = $imgThumbUrl;

                    $businessLogoThumbImgPath = ((isset($value->business_logo) && !empty($value->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) : $imgThumbUrl;
                    $outputArray['data'][$i]['logo_thumbnail'] = $businessLogoThumbImgPath;


                    $i++;
                }
                $this->log->info('API getNearByBusinesses get successfully', array('login_user_id' => $user->id));
            }
            else
            {
                $this->log->info('API getNearByBusinesses no records found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getNearByBusinesses', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Get Business Ratings
     */
    public function getBusinessRatings(Request $request)
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
        $user = JWTAuth::parseToken()->authenticate();
        $businessId = (isset($request->business_id) && $request->business_id > 0) ? $request->business_id : 0;
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        try
        {
            $filters = [];
            $filters['updated_at'] = 'updated_at';
            $totalBusinessesRatingData = BusinessRatings::where('business_id', $businessId)->get();
            $totalBusinessesRatingCount = count($totalBusinessesRatingData);
            $businessListing = '';
            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                $offset = Helpers::getWebOffset($pageNo);
                if(!empty($businessId))
                {
                    $filters['business_id'] = $businessId;
                    $filters['offset'] = $offset;
                    $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    $businessListing = $this->objBusinessRatings->getAll($filters);
                }
            }
            elseif(!empty($headerData) && $headerData == Config::get('constant.MOBILE_PLATFORM'))
            {
                $offset = Helpers::getOffset($pageNo);
                if(!empty($businessId))
                {
                    $filters['business_id'] = $businessId;
                    $filters['offset'] = $offset;
                    $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                    $businessListing = $this->objBusinessRatings->getAll($filters);
                }
            }
            if($businessListing && count($businessListing) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.business_ratings_fetched_successfully');
                $statusCode = 200;

                if($headerData == Config::get('constant.WEBSITE_PLATFORM'))
                {
                    if($businessListing->count() < Config::get('constant.WEBSITE_RECORD_PER_PAGE'))
                    {
                        $outputArray['loadMore'] = 0;
                    } else{
                        $offset = Helpers::getWebOffset($pageNo+1);
                        $filters = [];
                        $filters['business_id'] = $businessId;
                        $filters['offset'] = $offset;
                        $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');

                        $businessRatingCount = $this->objBusinessRatings->getAll($filters);
                        $outputArray['loadMore'] = (count($businessRatingCount) > 0) ? 1 : 0 ;
                    }
                }
                else
                {
                    if($businessListing->count() < Config::get('constant.API_RECORD_PER_PAGE'))
                    {
                        $outputArray['loadMore'] = 0;
                    } else{
                        $offset = Helpers::getOffset($pageNo+1);
                        $filters = [];
                        $filters['business_id'] = $businessId;
                        $filters['offset'] = $offset;
                        $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                        $businessRatingCount =  $this->objBusinessRatings->getAll($filters);
                        $outputArray['loadMore'] = (count($businessRatingCount) > 0) ? 1 : 0 ;
                    }
                }

                $outputArray['data'] = array();
                $outputArray['data']['avg_rating'] = round($totalBusinessesRatingData->avg('rating'), 1);
                $userRating = $totalBusinessesRatingData->where('user_id', $user->id)->where('business_id', $businessId)->pluck('rating')->first();
                $outputArray['data']['user_rating'] = (isset($userRating) && !empty($userRating)) ? intval($userRating) : '';
                $outputArray['data']['start_5_rating'] = $totalBusinessesRatingData->where('rating', '=', '5.0')->count();
                $outputArray['data']['start_4_rating'] = $totalBusinessesRatingData->where('rating', '=', '4.0')->count();
                $outputArray['data']['start_3_rating'] = $totalBusinessesRatingData->where('rating', '=', '3.0')->count();
                $outputArray['data']['start_2_rating'] = $totalBusinessesRatingData->where('rating', '=', '2.0')->count();
                $outputArray['data']['start_1_rating'] = $totalBusinessesRatingData->where('rating', '=', '1.0')->count();
                $outputArray['data']['total'] = (isset($totalBusinessesRatingCount) && !empty($totalBusinessesRatingCount)) ? $totalBusinessesRatingCount : 0;

                $outputArray['data']['reviews'] = array();
                $l = 0;
                foreach($businessListing as $ratingKey => $ratingValue)
                {
                    $outputArray['data']['reviews'][$l]['id'] = $ratingValue->id;
                    $outputArray['data']['reviews'][$l]['rating'] = $ratingValue->rating;
                    $outputArray['data']['reviews'][$l]['name'] = (isset($ratingValue->getUsersData) && !empty($ratingValue->getUsersData->name)) ? $ratingValue->getUsersData->name : '';

                    $outputArray['data']['reviews'][$l]['timestamp'] = (!empty($ratingValue->updated_at)) ? strtotime($ratingValue->updated_at)*1000 : '';
                    $outputArray['data']['reviews'][$l]['review'] = $ratingValue->comment;

                    // if(isset($ratingValue->getUsersData) && !empty($ratingValue->getUsersData->profile_pic))
                    // {
                    //     // $imgThumbPath = $this->USER_THUMBNAIL_IMAGE_PATH.$ratingValue->getUsersData->profile_pic;
                    //     // $imgThumbUrl = (!empty($imgThumbPath) && file_exists($imgThumbPath)) ? $imgThumbPath : '';
                    // }

                    $imgThumbUrl = ((isset($ratingValue->getUsersData) && !empty($ratingValue->getUsersData->profile_pic)) && Storage::size(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$ratingValue->getUsersData->profile_pic) > 0) ? Storage::url(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$ratingValue->getUsersData->profile_pic) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $outputArray['data']['reviews'][$l]['image_url'] = $imgThumbUrl;
                    $outputArray['data']['reviews'][$l]['user_business_id'] = (isset($ratingValue->getUsersData->singlebusiness) && $ratingValue->getUsersData->singlebusiness->id != '')? (string)$ratingValue->getUsersData->singlebusiness->id : '';
                    $l++;
                }
                $this->log->info('API getBusinessRatings get successfully', array('login_user_id' => $user->id));
            }
            else
            {
                $this->log->info('API getBusinessRatings no records found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getBusinessRatings', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = 400;
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Add Business Rating
     */
    public function addBusinessRating(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $outputArray = [];
        $data = [];
        $requestData = array_map('trim',$request->all());
        try
        {
            DB::beginTransaction();
            $validator = Validator::make($requestData,
                [
                    'user_id' => 'required',
                    'business_id' => 'required',
                    'rating' => 'required'
                ]
            );
            if ($validator->fails())
            {
                DB::rollback();
                $this->log->error('API validation failed while addBusinessRating', array('login_user_id' => $user->id));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray,$statusCode);
            }
            else
            {
                $data['user_id'] = $requestData['user_id'];
                $data['business_id'] = $requestData['business_id'];
                $data['rating'] = $requestData['rating'];
                $data['comment'] = (isset($requestData['comment']) && !empty($requestData['comment'])) ? $requestData['comment'] : NULL;
                $getRatingRecord = Helpers::getSameUserBusinessData($data['user_id'], $data['business_id']);

                if(count($getRatingRecord) > 0)
                {
                    $data['id'] = $getRatingRecord->id;
                    $message =  trans('apimessages.business_rating_updated_successfully');
                }
                else{
                    $message =  trans('apimessages.business_rating_added_successfully');
                }
                $response = $this->objBusinessRatings->insertUpdate($data);

                if($response)
                {
                    $businessDetail = Business::find($requestData['business_id']);
                    if(isset($businessDetail->user))
                    {
                        //Send push notification to Business User
                        $notificationData = [];
                        $notificationData['title'] = 'Business Rated';
                        $notificationData['message'] = 'Dear '.$businessDetail->user->name.',  Your business just got new review.  Find out what they said.';
                        $notificationData['type'] = '4';
                        $notificationData['business_id'] = $businessDetail->id;
                        $notificationData['business_name'] = $businessDetail->name;
                        Helpers::sendPushNotification($businessDetail->user_id, $notificationData);

                        // notification list
                        $notificationListArray = [];
                        $notificationListArray['user_id'] = $businessDetail->user_id;
                        $notificationListArray['business_id'] = $businessDetail->id;
                        $notificationListArray['title'] = 'Business Rated';
                        $notificationListArray['message'] =  'Dear '.$businessDetail->user->name.',  Your business just got new review.  Find out what they said.';
                        $notificationListArray['type'] = '4';
                        $notificationListArray['business_name'] = $businessDetail->name;
                        $notificationListArray['user_name'] = $businessDetail->user->name;
                        $notificationListArray['activity_user_id'] = $requestData['user_id'];


                        NotificationList::create($notificationListArray);

                    }

                    DB::commit();
                    $this->log->info('API addBusinessRating save successfully', array('login_user_id' => $user->id));
                    $outputArray['status'] = 1;
                    $outputArray['message'] = $message;
                    $statusCode = 200;
                }
                else
                {
                    DB::rollback();
                    $this->log->error('API something went wrong while addBusinessRating', array('login_user_id' => $user->id));
                    $outputArray['status'] = 0;
                    $outputArray['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 400;
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            $this->log->error('API something went wrong while addBusinessRating', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Get Service Details
     */
    public function getServiceDetails(Request $request)
    {
        $outputArray = [];
        $serviceId = (isset($request->service_id) && $request->service_id > 0) ? $request->service_id : 0;
        try
        {
            $serviceDetails = Service::find($serviceId);
            if($serviceDetails && count($serviceDetails) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.service_details_fetched_successfully');
                $statusCode = 200;
                $outputArray['data'] = array();

                $outputArray['data']['business_id'] = (isset($serviceDetails->serviceBusiness) && !empty($serviceDetails->serviceBusiness->id)) ? $serviceDetails->serviceBusiness->id : 0;
                $outputArray['data']['business_name'] = (isset($serviceDetails->serviceBusiness) && !empty($serviceDetails->serviceBusiness->name)) ? $serviceDetails->serviceBusiness->name : '';

                $outputArray['data']['name'] = $serviceDetails->name;
                $outputArray['data']['descriptions'] = (isset($serviceDetails->description) && !empty($serviceDetails->description)) ? $serviceDetails->description : '';
                $outputArray['data']['metatags'] = (isset($serviceDetails->metatags) && !empty($serviceDetails->metatags)) ? $serviceDetails->metatags : '';
                $outputArray['data']['cost'] = (isset($serviceDetails->cost) && !empty($serviceDetails->cost)) ? $serviceDetails->cost : '';
                // if(!empty($serviceDetails->logo))
                // {
                //     // $imgOriginalPath = $this->SERVICE_ORIGINAL_IMAGE_PATH.$serviceDetails->logo;
                //     // $imgOriginalUrl = (!empty($imgOriginalPath) && file_exists($imgOriginalPath)) ? url($imgOriginalPath) : '';
                // }
                $imgOriginalUrl = (($serviceDetails->logo != '') && Storage::size(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$serviceDetails->logo) > 0) ? Storage::url(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$serviceDetails->logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                $outputArray['data']['logo'] = $imgOriginalUrl;
            }
            else
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Get Product Details
     */
    public function getProductDetails(Request $request)
    {
        $outputArray = [];
        $productId = (isset($request->product_id) && $request->product_id > 0) ? $request->product_id : 0;
        try
        {
            $productDetails = Product::find($productId);
            if($productDetails && count($productDetails) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.product_details_fetched_successfully');
                $statusCode = 200;
                $outputArray['data'] = array();

                $outputArray['data']['business_id'] = (isset($productDetails->productBusiness) && !empty($productDetails->productBusiness->id)) ? $productDetails->productBusiness->id : 0;
                $outputArray['data']['business_name'] = (isset($productDetails->productBusiness) && !empty($productDetails->productBusiness->name)) ? $productDetails->productBusiness->name : '';

                $outputArray['data']['name'] = $productDetails->name;
                $outputArray['data']['descriptions'] = (isset($productDetails->description) && !empty($productDetails->description)) ? $productDetails->description : '';
                $outputArray['data']['metatags'] = (isset($productDetails->metatags) && !empty($productDetails->metatags)) ? $productDetails->metatags : '';
                $outputArray['data']['cost'] = (isset($productDetails->cost) && !empty($productDetails->cost)) ? $productDetails->cost : '';
                $outputArray['data']['product_images'] = array();
                $i = 0;
                if(isset($productDetails->productImages) && count($productDetails->productImages) > 0)
                {
                    foreach ($productDetails->productImages as $key => $value)
                    {
                        if(!empty($value->image_name))
                        {
                            //$imgOriginalPath = $this->PRODUCT_ORIGINAL_IMAGE_PATH.$value->image_name;
                            $outputArray['data']['product_images'][$i] = (Storage::size(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$value->image_name) > 0) ? Storage::url(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$value->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                            $i++;
                        }
                    }
                }
                else
                {
                    $outputArray['data']['product_images'][$i] = url(Config::get('constant.DEFAULT_IMAGE'));
                }
            }
            else
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Get Business Detail
     */
    public function getBusinessDetail(Request $request)
    {
        $headerData = $request->header('Platform');
        $businessId = $request->business_id;
        $businessSlug = (isset($request->business_slug) && !empty($request->business_slug)) ? $request->business_slug : '';
        $user = JWTAuth::parseToken()->authenticate();
        $outputArray = [];
        try
        {
            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
               $getBusinessDetails = Business::where('business_slug', $businessSlug)->first();
               $businessId = $getBusinessDetails->id;
            }
            else
            {
                $getBusinessDetails = Business::find($businessId);
            }

            if($getBusinessDetails && count($getBusinessDetails)>0)
            {
                //Send push notification to Business User
                if($getBusinessDetails->user_id != $user->id) {
                    if(isset($getBusinessDetails->user) && count($getBusinessDetails->user) > 0)
                    {
                        $notificationData = [];
                        $notificationData['title'] = 'Business Visited';
                        if((isset($user->singlebusiness->name)) && $user->singlebusiness->name != '')
                        {
                            $notificationData['message'] = ($user->gender != 2) ? 'Dear '.$getBusinessDetails->user->name.',  Your business just got viewed by '.$user->name.' '.$user->phone.' from '.$user->singlebusiness->name : 'Dear '.$getBusinessDetails->user->name.',  Your business just got viewed by '.$user->name.' from '.$user->singlebusiness->name;
                        }
                        else
                        {
                            $notificationData['message'] = ($user->gender != 2) ? 'Dear '.$getBusinessDetails->user->name.',  Your business just got viewed by '.$user->name.' '.$user->phone : 'Dear '.$getBusinessDetails->user->name.',  Your business just got viewed by '.$user->name;
                        }

                        $notificationData['type'] = '3';
                        $notificationData['business_id'] = $getBusinessDetails->id;
                        $notificationData['business_name'] = $getBusinessDetails->name;
                        $notificationData['phone'] = ($user->gender != 2) ? $user->phone : "";
                        $notificationData['country_code'] = $user->country_code;
                        $notificationData['user_business_id'] = (isset($user->singlebusiness->id))? $user->singlebusiness->id : '';
                        $notificationData['user_business_name'] = (isset($user->singlebusiness->name)) ? $user->singlebusiness->name : '';
                        Helpers::sendPushNotification($getBusinessDetails->user_id, $notificationData);

                        // list notification list
                        $notificationListArray = [];
                        $notificationListArray['user_id'] = $getBusinessDetails->user_id;
                        $notificationListArray['business_id'] = $getBusinessDetails->id;
                        $notificationListArray['title'] = 'Business Visited';
                        $notificationListArray['message'] = ($user->gender != 2) ? 'Dear '.$getBusinessDetails->user->name.',  Your business just got viewed by '.$user->name.' '.$user->phone : 'Dear '.$getBusinessDetails->user->name.',  Your business just got viewed by '.$user->name;
                        $notificationListArray['type'] = '3';
                        $notificationListArray['business_name'] = $getBusinessDetails->name;
                        $notificationListArray['user_name'] = $getBusinessDetails->user->name;
                        $notificationListArray['activity_user_id'] = $user->id;

                        NotificationList::create($notificationListArray);


                    }

                }

                $loginUserId = $user->id;
                if($loginUserId != $getBusinessDetails->user_id)
                {
                    $getBusinessDetails->visits = $getBusinessDetails->visits + 1;
                    $getBusinessDetails->save();
                }
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.business_detail_get_successfully');
                $statusCode = 200;
                $outputArray['data'] = array();

                $outputArray['data']['user_id'] = (isset($getBusinessDetails->user) && !empty($getBusinessDetails->user) && !empty($getBusinessDetails->user->id)) ? $getBusinessDetails->user->id : '';
                $outputArray['data']['user_name'] = (isset($getBusinessDetails->user) && !empty($getBusinessDetails->user) && !empty($getBusinessDetails->user->name)) ? $getBusinessDetails->user->name : '';
                $outputArray['data']['suggested_categories'] = $getBusinessDetails->suggested_categories;
                $outputArray['data']['created_by_agent'] = (isset($getBusinessDetails->businessCreatedBy)) ? $getBusinessDetails->businessCreatedBy->agent_approved : 0;

                $outputArray['data']['category_hierarchy'] = array();
                if($getBusinessDetails->category_id && !empty($getBusinessDetails->category_id))
                {
                    $explodeCategories = explode(',', $getBusinessDetails->category_id);
                    foreach($explodeCategories as $categoryKey => $categoryValue)
                    {
                        if(!empty($categoryValue) && $categoryValue > 0)
                        {
                            $categories = Helpers::getCategoryReverseHierarchy($categoryValue);
                            if($categories && count($categories) > 0)
                            {
                                $outputArray['data']['category_hierarchy'][] = array_reverse($categories);
                            }
                        }
                    }
                }

                $parentCatArray = [];
                $outputArray['data']['parent_categories'] = array();
                if($getBusinessDetails->parent_category && !empty($getBusinessDetails->parent_category))
                {
                    $parentcategoryIdsArray = (explode(',', $getBusinessDetails->parent_category));
                    if (count($parentcategoryIdsArray) > 0)
                    {
                        $mainArray = [];
                        foreach ($parentcategoryIdsArray as $pIdKey => $pIdValue)
                        {
                            $parentcategoryData = Category::find($pIdValue);
                            if (count($parentcategoryData) > 0 && !empty($parentcategoryData))
                            {
                                $listArray = [];

                                $listArray['id'] = $parentcategoryData->id;
                                $listArray['name'] = $parentcategoryData->name;
                                $listArray['logo'] = (($parentcategoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH') . $parentcategoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH') . $parentcategoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                $parentCatArray[] =  $parentcategoryData->name;
                                $mainArray[] = $listArray;

                            }
                        }

                        $outputArray['data']['parent_categories'] = $mainArray;
                    }
                }

                $outputArray['data']['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                $outputArray['data']['categories_name_list'] = $outputArray['data']['parent_category_name'];
                $outputArray['data']['id'] = $getBusinessDetails->id;
                $outputArray['data']['name'] = $getBusinessDetails->name;
                $outputArray['data']['business_slug'] = (!empty($getBusinessDetails->business_slug)) ? $getBusinessDetails->business_slug : '';

                $businessLogoThumbImgPath = ((isset($getBusinessDetails->business_logo) && !empty($getBusinessDetails->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$getBusinessDetails->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$getBusinessDetails->business_logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                $outputArray['data']['business_logo'] = $businessLogoThumbImgPath;

                $outputArray['data']['default_image'] = url(Config::get('constant.RYEC_DEFAULT_BANNER_IMAGE'));
                $outputArray['data']['business_images'] = array();

                if(isset($getBusinessDetails->businessImages) && count($getBusinessDetails->businessImages) > 0)
                {
                    $g = 0;
                    foreach($getBusinessDetails->businessImages as $businessImgKey => $businessImgValue)
                    {
                        if(!empty($businessImgValue->image_name))
                        {
                            $imgThumbUrl = (($businessImgValue->image_name != '') && Storage::size(Config::get('constant.BUSINESS_ORIGINAL_IMAGE_PATH').$businessImgValue->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_ORIGINAL_IMAGE_PATH').$businessImgValue->image_name) : '';

                            if(!empty($imgThumbUrl))
                            {
                                $outputArray['data']['business_images'][$g]['id']= $businessImgValue->id;
                                $outputArray['data']['business_images'][$g]['image_name']= $imgThumbUrl;
                                $g++;
                            }
                        }
                    }
                }

                $outputArray['data']['full_address'] = $getBusinessDetails->address;
                $outputArray['data']['address'] = '';
                $address = [];
                $addressImplode = '';
                if($getBusinessDetails->street_address != ''){
                    $address[] = $getBusinessDetails->street_address;
                }
                if($getBusinessDetails->locality != '')
                {
                    $address[] = $getBusinessDetails->locality;
                }
                if($getBusinessDetails->city != '')
                {
                    $address[] = $getBusinessDetails->city;
                }
                if($getBusinessDetails->state != '')
                {
                    $address[] = $getBusinessDetails->state;
                }
                if(!empty($address))
                {
                    $addressImplode = implode(', ',$address);
                }
                $outputArray['data']['address'] = $addressImplode;
                if($getBusinessDetails->pincode != '' && $outputArray['data']['address'] != '')
                {
                    $outputArray['data']['address'] = $addressImplode.' - '.$getBusinessDetails->pincode;
                }

                $outputArray['data']['street_address'] = $getBusinessDetails->street_address;
                $outputArray['data']['locality'] = $getBusinessDetails->locality;
                $outputArray['data']['country'] = $getBusinessDetails->country;
                $outputArray['data']['state'] = $getBusinessDetails->state;
                $outputArray['data']['city'] = $getBusinessDetails->city;
                $outputArray['data']['taluka'] = $getBusinessDetails->taluka;
                $outputArray['data']['district'] = $getBusinessDetails->district;
                $outputArray['data']['pincode'] = $getBusinessDetails->pincode;
                $outputArray['data']['phone'] = $getBusinessDetails->phone;
                $outputArray['data']['country_code'] = $getBusinessDetails->country_code;
                $outputArray['data']['mobile'] = $getBusinessDetails->mobile;
                //$outputArray['data']['country'] = (isset($getBusinessDetails->business_address) && !empty($getBusinessDetails->business_address->country) ) ? $getBusinessDetails->business_address->country : '';
                $outputArray['data']['email'] = $getBusinessDetails->email_id;
                $outputArray['data']['latitude'] = (!empty($getBusinessDetails->latitude)) ? $getBusinessDetails->latitude : 0;
                $outputArray['data']['longitude'] = (!empty($getBusinessDetails->longitude)) ? $getBusinessDetails->longitude : 0;
                $outputArray['data']['website'] = (!empty($getBusinessDetails->website_url)) ? $getBusinessDetails->website_url : '';

                $outputArray['data']['membership_type'] = $getBusinessDetails->membership_type;
                if($getBusinessDetails->membership_type == 2)
                {
                    $outputArray['data']['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                }
                elseif($getBusinessDetails->membership_type == 1)
                {
                    $outputArray['data']['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                }
                else
                {
                    $outputArray['data']['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                }

                $outputArray['data']['year_of_establishment'] = (!empty($getBusinessDetails->establishment_year)) ? (string)$getBusinessDetails->establishment_year : '';
                $outputArray['data']['descriptions'] = (!empty($getBusinessDetails->description)) ? $getBusinessDetails->description : '';
                $outputArray['data']['approved'] = $getBusinessDetails->approved;
                if(isset($getBusinessDetails->businessWorkingHours) && !empty($getBusinessDetails->businessWorkingHours))
                {
                    $getTiming = Helpers::getCurrentDataTiming($getBusinessDetails->businessWorkingHours);
                    $outputArray['data']['current_open_status'] = $getTiming['current_open_status'];
                    $outputArray['data']['timings'] = $getTiming['timings'];
                    $outputArray['data']['hoursOperation'] = Helpers::getBusinessWorkingDayHours($getBusinessDetails->businessWorkingHours);
                    $outputArray['data']['timezone'] = (isset($getBusinessDetails->businessWorkingHours->timezone) && !empty($getBusinessDetails->businessWorkingHours->timezone)) ? $getBusinessDetails->businessWorkingHours->timezone : '';
                }
                else
                {
                    $outputArray['data']['current_open_status'] = trans('labels.closedtoday');
                    $outputArray['data']['timings'] = '';
                    $outputArray['data']['hoursOperation'] = [];
                    $outputArray['data']['timezone'] = '';
                }
                // For  meta tags
                $outputArray['data']['metatags'] = (isset($getBusinessDetails->metatags) && !empty($getBusinessDetails->metatags)) ? $getBusinessDetails->metatags : '' ;
                // For Owners
                $outputArray['data']['owners'] = array();
                if(isset($getBusinessDetails->owners) && count($getBusinessDetails->owners) > 0)
                {
                    $i = 0;
                    foreach ($getBusinessDetails->owners as $ownerKey => $ownerValue)
                    {
                        $outputArray['data']['owners'][$i]['id'] = $ownerValue->id;
                        $outputArray['data']['owners'][$i]['name'] = $ownerValue->full_name;

                        if($loginUserId == $getBusinessDetails->user_id)
                        {
                            $outputArray['data']['owners'][$i]['email'] = (!empty($ownerValue->email_id)) ? $ownerValue->email_id : '';
                            $outputArray['data']['owners'][$i]['country_code'] = (!empty($ownerValue->country_code)) ? $ownerValue->country_code : '';
                            $outputArray['data']['owners'][$i]['phone'] = (!empty($ownerValue->mobile)) ? $ownerValue->mobile : '';
                        }
                        else
                        {
                            $outputArray['data']['owners'][$i]['email'] = '';
                            $outputArray['data']['owners'][$i]['country_code'] = '';
                            $outputArray['data']['owners'][$i]['phone'] = '';
                            if($ownerValue->public_access == 1)
                            {
                                $outputArray['data']['owners'][$i]['email'] = (!empty($ownerValue->email_id)) ? $ownerValue->email_id : '';
                                $outputArray['data']['owners'][$i]['country_code'] = (!empty($ownerValue->country_code)) ? $ownerValue->country_code : '';
                                $outputArray['data']['owners'][$i]['phone'] = (!empty($ownerValue->mobile)) ? $ownerValue->mobile : '';
                            }
                        }


                        $outputArray['data']['owners'][$i]['image_url'] = (($ownerValue->photo != '') && Storage::size(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$ownerValue->photo) > 0) ? Storage::url(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$ownerValue->photo) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $i++;
                    }
                }

                // For Social Profiles
                $outputArray['data']['social_profiles']['facebook_url'] = (!empty($getBusinessDetails->facebook_url)) ? $getBusinessDetails->facebook_url : '';
                $outputArray['data']['social_profiles']['twitter_url'] = (!empty($getBusinessDetails->twitter_url)) ? $getBusinessDetails->twitter_url : '';
                $outputArray['data']['social_profiles']['linkedin_url'] = (!empty($getBusinessDetails->linkedin_url)) ? $getBusinessDetails->linkedin_url : '';
                $outputArray['data']['social_profiles']['instagram_url'] = (!empty($getBusinessDetails->instagram_url)) ? $getBusinessDetails->instagram_url : '';

                // For Products
                $outputArray['data']['products'] = array();
                if(isset($getBusinessDetails->products) && count($getBusinessDetails->products) > 0)
                {
                    $j = 0;
                    foreach ($getBusinessDetails->products as $productKey => $productValue)
                    {
                        $outputArray['data']['products'][$j]['id'] = $productValue->id;
                        $outputArray['data']['products'][$j]['name'] = $productValue->name;
                        $imgThumbUrl = '';

                        $imgThumbUrl = ((isset($productValue->productImage) && !empty($productValue->productImage) && !empty($productValue->productImage->image_name)) && Storage::size(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$productValue->productImage->image_name) > 0) ? Storage::url(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$productValue->productImage->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $outputArray['data']['products'][$j]['image_url'] = $imgThumbUrl;
                        $j++;
                    }
                }
                // For Services
                $outputArray['data']['services'] = array();
                if(isset($getBusinessDetails->services) && count($getBusinessDetails->services) > 0)
                {
                    $j = 0;
                    foreach ($getBusinessDetails->services as $serviceKey => $serviceValue)
                    {
                        $outputArray['data']['services'][$j]['id'] = $serviceValue->id;
                        $outputArray['data']['services'][$j]['name'] = $serviceValue->name;
                        $imgThumbUrl = '';

                        $imgThumbUrl = ((isset($serviceValue->logo) && !empty($serviceValue->logo)) && Storage::size(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$serviceValue->logo) > 0) ? Storage::url(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$serviceValue->logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $outputArray['data']['services'][$j]['image_url'] = $imgThumbUrl;
                        $j++;
                    }
                }

                //For Business Activities
                $outputArray['data']['business_activities'] = array();
                if(isset($getBusinessDetails->businessActivities) && count($getBusinessDetails->businessActivities) > 0)
                {
                    $k = 0;
                    foreach ($getBusinessDetails->businessActivities as $activityKey => $activityValue)
                    {
                        if(!empty($activityValue->activity_title))
                        {
                            $outputArray['data']['business_activities'][$k]['id'] = $activityValue->id;
                            $outputArray['data']['business_activities'][$k]['business_id'] = $activityValue->business_id;
                            $outputArray['data']['business_activities'][$k]['activity_title'] = $activityValue->activity_title;
                            $k++;
                        }
                    }
                }

                //For rating
                if(isset($getBusinessDetails->getBusinessRatings) && count($getBusinessDetails->getBusinessRatings) > 0)
                {
                    $l = 0;
                    $outputArray['data']['rating']['avg_rating'] = round($getBusinessDetails->getBusinessRatings->avg('rating'), 1);

                    $userRating = $getBusinessDetails->getBusinessRatings->where('user_id', $loginUserId)->where('business_id', $businessId)->pluck('rating')->first();

                    $outputArray['data']['rating']['user_rating'] = (isset($userRating) && !empty($userRating)) ? intval($userRating) : '';
                    $outputArray['data']['rating']['start_5_rating'] = $getBusinessDetails->getBusinessRatings->where('rating', '=', '5.0')->count();
                    $outputArray['data']['rating']['start_4_rating'] = $getBusinessDetails->getBusinessRatings->where('rating', '=', '4.0')->count();
                    $outputArray['data']['rating']['start_3_rating'] = $getBusinessDetails->getBusinessRatings->where('rating', '=', '3.0')->count();
                    $outputArray['data']['rating']['start_2_rating'] = $getBusinessDetails->getBusinessRatings->where('rating', '=', '2.0')->count();
                    $outputArray['data']['rating']['start_1_rating'] = $getBusinessDetails->getBusinessRatings->where('rating', '=', '1.0')->count();
                    $outputArray['data']['rating']['total'] = $getBusinessDetails->getBusinessRatings->count('rating');

                    $outputArray['data']['rating']['reviews'] = array();
                    $businessRatingDetails = $getBusinessDetails->getBusinessRatings()->orderBy('updated_at', 'DESC')->limit(Config::get('constant.BUSINESS_DETAILS_RATINGS_LIMIT'))->get();
                    foreach ($businessRatingDetails as $ratingKey => $ratingValue)
                    {
                        $outputArray['data']['rating']['reviews'][$l]['id'] = $ratingValue->id;
                        $outputArray['data']['rating']['reviews'][$l]['rating'] = $ratingValue->rating;
                        $outputArray['data']['rating']['reviews'][$l]['name'] = (isset($ratingValue->getUsersData) && !empty($ratingValue->getUsersData->name)) ? $ratingValue->getUsersData->name : '';

                        $outputArray['data']['rating']['reviews'][$l]['timestamp'] = (!empty($ratingValue->updated_at)) ? strtotime($ratingValue->updated_at)*1000 : '';
                        $outputArray['data']['rating']['reviews'][$l]['review'] = $ratingValue->comment;

                        $imgThumbUrl = '';
                        $imgThumbUrl = ((isset($ratingValue->getUsersData) && !empty($ratingValue->getUsersData->profile_pic)) && Storage::size(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$ratingValue->getUsersData->profile_pic) > 0) ? Storage::url(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$ratingValue->getUsersData->profile_pic) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $outputArray['data']['rating']['reviews'][$l]['image_url'] = $imgThumbUrl;
                        $outputArray['data']['rating']['reviews'][$l]['user_business_id'] = (isset($ratingValue->getUsersData->singlebusiness) && $ratingValue->getUsersData->singlebusiness->id != '')? (string)$ratingValue->getUsersData->singlebusiness->id : '';
                        $l++;
                    }
                }
                else
                {
                    $outputArray['data']['rating'] = new stdClass();
                }
            }
            else
            {
                $this->log->info('API getBusinessDetail no records found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->info('API getBusinessDetail no records found', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
       return response()->json($outputArray, $statusCode);
    }

    /**
     * Get Populuar Businesses
     */
    public function getPopularBusinesses(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        $outputArray = [];
        try
        {
            $filters = [];
            //$filters['approved'] = 1;
            $businessesCount = Business::orderBy('visits', 'DESC')->count();
            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                if(isset($request->limit) && !empty($request->limit))
                {
                    $filters['take'] = $request->limit;
                    $filters['skip'] = 0;
                }
                elseif(isset($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
                elseif(isset($request->limit) && !empty($request->limit) && isset($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = $request->limit;
                    $filters['skip'] = $offset;
                }
                if(isset($request->sortBy) && !empty($request->sortBy))
                {
                    if($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                    elseif($request->sortBy == 'AtoZ')
                    {
                        $filters['sortBy'] = 'AtoZ';
                    }
                    elseif($request->sortBy == 'ZtoA')
                    {
                        $filters['sortBy'] = 'ZtoA';
                    }
                    elseif($request->sortBy == 'nearMe' && isset($request->radius) && !empty ($request->radius) && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = $request->sortBy;
                        $filters['radius'] = $request->radius;
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;
                    }
                    elseif($request->sortBy == 'relevance' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = 'relevance';
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;
                    }
                }
            }
            if(isset($request->sortBy) && !empty($request->sortBy) && $request->sortBy == 'ratings')
            {
                $filters['sortBy'] = 'ratings';
                $recentlyAddedBusiness = $this->objBusiness->getBusinessesByRating($filters);
            }
            else
            {
                if(!isset($request->sortBy) && empty($request->sortBy))
                {
                    $filters['sortBy'] = 'promoted';
                }
                $recentlyAddedBusiness = $this->objBusiness->getAll($filters);
            }

            if($recentlyAddedBusiness && count($recentlyAddedBusiness) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.popular_business_fetched_successfully');
                $statusCode = 200;
                $outputArray['businessesTotalCount'] = (isset($businessesCount) && $businessesCount > 0) ? $businessesCount : 0;

                $outputArray['data'] = array();
                $i = 0;
                foreach ($recentlyAddedBusiness as $key => $value)
                {
                    $outputArray['data'][$i]['id'] = $value->id;
                    $outputArray['data'][$i]['name'] = $value->name;
                    $outputArray['data'][$i]['business_slug'] = (isset($value->business_slug) && !empty($value->business_slug)) ? $value->business_slug : '';
                    $outputArray['data'][$i]['user_name'] = (isset($value->user) && !empty($value->user) && !empty($value->user->name)) ? $value->user->name : '';

                    $outputArray['data'][$i]['owners'] = '';

                    if($value->owners && count($value->owners) > 0)
                    {
                        $owners = [];
                        foreach($value->owners as $owner)
                        {
                            $owners[] = $owner->full_name;
                        }
                        if(!empty($owners))
                        {
                            $outputArray['data'][$i]['owners'] = implode(', ',$owners);
                        }
                    }

                    $outputArray['data'][$i]['categories'] = array();
                    if(!empty($value->category_id) && $value->category_id != '')
                    {
                        $categoryIdsArray = (explode(',', $value->category_id));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    // if(!empty($categoryData->cat_logo))
                                    // {
                                    //    // $catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                    // }
                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                    $outputArray['data'][$i]['categories'][$j]['category_logo'] = $catLogoPath;

                                    $outputArray['data'][$i]['categories'][$j]['parent_category_id'] = $categoryData->parent;
                                    $j++;
                                }
                            }
                        }
                    }
                    $parentCatArray = [];
                    $outputArray['data'][$i]['parent_categories'] = array();
                    if(!empty($value->parent_category) && $value->parent_category != '')
                    {
                        $categoryIdsArray = (explode(',', $value->parent_category));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    // if(!empty($categoryData->cat_logo))
                                    // {
                                    //    // $catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                    // }
                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_logo'] = $catLogoPath;
                                    $parentCatArray[] =  $categoryData->name;

                                    $j++;
                                }
                            }
                        }
                    }

                    $outputArray['data'][$i]['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                    $outputArray['data'][$i]['categories_name_list'] = $outputArray['data'][$i]['parent_category_name'];
                    $outputArray['data'][$i]['descriptions'] = $value->description;
                    $outputArray['data'][$i]['phone'] = $value->phone;
                    $outputArray['data'][$i]['country_code'] = $value->country_code;
                    $outputArray['data'][$i]['mobile'] = $value->mobile;
                    $outputArray['data'][$i]['latitude'] = (!empty($value->latitude)) ? $value->latitude : '';
                    $outputArray['data'][$i]['longitude'] = (!empty($value->longitude)) ? $value->longitude : '';
                    $outputArray['data'][$i]['address'] = (!empty($value->address)) ? $value->address : '';
                    $outputArray['data'][$i]['street_address'] = $value->street_address;
                    $outputArray['data'][$i]['locality'] = $value->locality;
                    $outputArray['data'][$i]['country'] = $value->country;
                    $outputArray['data'][$i]['state'] = $value->state;
                    $outputArray['data'][$i]['city'] = $value->city;
                    $outputArray['data'][$i]['taluka'] = $value->taluka;
                    $outputArray['data'][$i]['district'] = $value->district;
                    $outputArray['data'][$i]['pincode'] = $value->pincode;
                    $outputArray['data'][$i]['email_id'] = (!empty($value->email_id)) ? $value->email_id : '';
                    $outputArray['data'][$i]['website_url'] = (!empty($value->website_url)) ? $value->website_url : '';
                    $outputArray['data'][$i]['membership_type'] = $value->membership_type;
                    if($value->membership_type == 2)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                    }
                    elseif($value->membership_type == 1)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                    }
                    else
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                    }
                    // if(isset($value->businessImagesById) && !empty($value->businessImagesById->image_name))
                    // {

                    //     // $img_thumb_path = $this->BUSINESS_THUMBNAIL_IMAGE_PATH.$value->businessImagesById->image_name;
                    //     // $img_thumb_url = (!empty($img_thumb_path) && file_exists($img_thumb_path)) ? $img_thumb_path : '';
                    //     // $outputArray['data'][$i]['business_image'] = (!empty($img_thumb_url)) ? url($img_thumb_url) : url($this->catgoryTempImage);

                    // }
                    // else
                    // {
                    //     $outputArray['data'][$i]['business_image'] = url($this->catgoryTempImage);
                    // }

                    $imgThumbUrl = ((isset($value->businessImagesById) && !empty($value->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $outputArray['data'][$i]['business_image'] = $imgThumbUrl;

                    $businessLogoThumbImgPath = ((isset($value->business_logo) && !empty($value->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) : $imgThumbUrl;
                    $outputArray['data'][$i]['logo_thumbnail'] = $businessLogoThumbImgPath;
                    $i++;
                }
            }
            else
            {
                $this->log->info('API getPopularBusinesses no records found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getPopularBusinesses', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Get Promoted Businesses
     */
    public function getRecentlyAddedBusinessListing(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        $outputArray = [];
        try
        {
            $filters = [];
            $filters['approved'] = 1;
            $businessesCount = Business::orderBy('id', 'DESC')->count();

            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                if(isset($request->limit) && !empty($request->limit))
                {
                    $filters['take'] = $request->limit;
                    $filters['skip'] = 0;
                }
                elseif(isset($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = Config::get('constant.WEBSITE_RECENTLY_ADDED_BUSINESS_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
                elseif(isset($request->limit) && !empty($request->limit) && isset($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = $request->limit;
                    $filters['skip'] = $offset;
                }
            }
            elseif(!empty($headerData) && $headerData == Config::get('constant.MOBILE_PLATFORM'))
            {
                if(isset($request->limit) && !empty($request->limit))
                {
                    $filters['take'] = $request->limit;
                    $filters['skip'] = 0;
                }
                elseif(isset($request->page))
                {
                    $offset = Helpers::getOffset($pageNo);
                    $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
                elseif(isset($request->limit) && !empty($request->limit) && isset($request->page))
                {
                    $offset = Helpers::getOffset($pageNo);
                    $filters['take'] = $request->limit;
                    $filters['skip'] = $offset;
                }
            }
            if(isset($request->sortBy) && !empty($request->sortBy))
            {
                if($request->sortBy == 'popular')
                {
                    $filters['sortBy'] = 'popular';
                }
                elseif($request->sortBy == 'AtoZ')
                {
                    $filters['sortBy'] = 'AtoZ';
                }
                elseif($request->sortBy == 'ZtoA')
                {
                    $filters['sortBy'] = 'ZtoA';
                }
                elseif($request->sortBy == 'nearMe' && isset($request->radius) && !empty ($request->radius) && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                {
                    $filters['sortBy'] = $request->sortBy;
                    $filters['radius'] = $request->radius;
                    $filters['latitude'] = $request->latitude;
                    $filters['longitude'] = $request->longitude;

                    // $businessNearMeData = Business::where('approved', $filters['approved']);
                    $businessNearMeData = Business::whereNull('deleted_at');
                    $businessNearMeData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');
                    $businessNearMeData->having('distance', '<', $filters['radius']);

                    $businessNearMeData->orderBy('distance', 'DESC');
                    $businessesCount = $businessNearMeData->count();
                }
                elseif($request->sortBy == 'relevance' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                {
                    $filters['sortBy'] = 'relevance';
                    $filters['latitude'] = $request->latitude;
                    $filters['longitude'] = $request->longitude;

                    //$businessNearMeData = Business::where('approved', $filters['approved']);
                    $businessNearMeData = Business::whereNull('deleted_at');
                    $businessNearMeData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');

                    $businessNearMeData->orderBy('membership_type', 'DESC')->orderBy('distance', 'DESC');
                    $businessesCount = $businessNearMeData->count();
                }
            }

            if(isset($request->sortBy) && !empty($request->sortBy) && $request->sortBy == 'ratings')
            {
                $filters['sortBy'] = 'ratings';
                $recentlyAddedBusiness = $this->objBusiness->getBusinessesByRating($filters);
            }
            else
            {
                if(!isset($request->sortBy) && empty($request->sortBy))
                {
                    $filters['sortBy'] = 'promoted';
                }
                $recentlyAddedBusiness = $this->objBusiness->getAll($filters);
            }

            if($recentlyAddedBusiness && count($recentlyAddedBusiness) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.recently_added_business_fetched_successfully');
                $statusCode = 200;

                $outputArray['businessesTotalCount'] = (isset($businessesCount) && $businessesCount > 0) ? $businessesCount : 0;

                $outputArray['data'] = array();
                $i = 0;
                foreach ($recentlyAddedBusiness as $key => $value)
                {
                    $outputArray['data'][$i]['id'] = $value->id;
                    $outputArray['data'][$i]['name'] = $value->name;
                    $outputArray['data'][$i]['business_slug'] = (isset($value->business_slug) && !empty($value->business_slug)) ? $value->business_slug : '';
                    $outputArray['data'][$i]['user_name'] = (isset($value->user) && !empty($value->user) && !empty($value->user->name)) ? $value->user->name : '';

                    $outputArray['data'][$i]['owners'] = '';

                    if($value->owners && count($value->owners) > 0)
                    {
                        $owners = [];
                        foreach($value->owners as $owner)
                        {
                            $owners[] = $owner->full_name;
                        }
                        if(!empty($owners))
                        {
                            $outputArray['data'][$i]['owners'] = implode(', ',$owners);
                        }
                    }
                    $outputArray['data'][$i]['categories'] = array();
                    if(!empty($value->category_id) && $value->category_id != '')
                    {
                        $categoryIdsArray = (explode(',', $value->category_id));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    // if(!empty($categoryData->cat_logo))
                                    // {
                                    //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                    // }
                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                                    $outputArray['data'][$i]['categories'][$j]['category_logo'] = $catLogoPath;

                                    $outputArray['data'][$i]['categories'][$j]['parent_category_id'] = $categoryData->parent;
                                    $j++;
                                }
                            }
                        }
                    }
                    $parentCatArray = [];
                    $outputArray['data'][$i]['parent_categories'] = array();
                    if(!empty($value->parent_category) && $value->parent_category != '')
                    {
                        $categoryIdsArray = (explode(',', $value->parent_category));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    // if(!empty($categoryData->cat_logo))
                                    // {
                                    //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                    // }

                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                                    $outputArray['data'][$i]['parent_categories'][$j]['category_logo'] = $catLogoPath;
                                    $parentCatArray[] =  $categoryData->name;
                                    $j++;
                                }
                            }
                        }
                    }

                    $outputArray['data'][$i]['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                    $outputArray['data'][$i]['categories_name_list'] = $outputArray['data'][$i]['parent_category_name'];
                    $outputArray['data'][$i]['descriptions'] = $value->description;
                    $outputArray['data'][$i]['phone'] = $value->phone;
                    $outputArray['data'][$i]['country_code'] = $value->country_code;
                    $outputArray['data'][$i]['mobile'] = $value->mobile;
                    $outputArray['data'][$i]['latitude'] = (!empty($value->latitude)) ? $value->latitude : '';
                    $outputArray['data'][$i]['longitude'] = (!empty($value->longitude)) ? $value->longitude : '';
                    $outputArray['data'][$i]['address'] = (!empty($value->address)) ? $value->address : '';
                    $outputArray['data'][$i]['street_address'] = (!empty($value->street_address)) ? $value->street_address : '';
                    $outputArray['data'][$i]['locality'] = (!empty($value->locality)) ? $value->locality : '';
                    $outputArray['data'][$i]['country'] = (!empty($value->country)) ? $value->country : '';
                    $outputArray['data'][$i]['state'] = (!empty($value->state)) ? $value->state : '';
                    $outputArray['data'][$i]['city'] = (!empty($value->city)) ? $value->city : '';
                    $outputArray['data'][$i]['taluka'] = (!empty($value->taluka)) ? $value->taluka : '';
                    $outputArray['data'][$i]['district'] = (!empty($value->district)) ? $value->district : '';
                    $outputArray['data'][$i]['pincode'] = (!empty($value->pincode)) ? $value->pincode : '';
                    $outputArray['data'][$i]['email_id'] = (!empty($value->email_id)) ? $value->email_id : '';
                    $outputArray['data'][$i]['website_url'] = (!empty($value->website_url)) ? $value->website_url : '';
                    $outputArray['data'][$i]['membership_type'] = $value->membership_type;
                    if($value->membership_type == 2)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                    }
                    elseif($value->membership_type == 1)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                    }
                    else
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                    }
                    // if(isset($value->businessImagesById) && !empty($value->businessImagesById->image_name))
                    // {
                    //     // $img_thumb_path = $this->BUSINESS_THUMBNAIL_IMAGE_PATH.$value->businessImagesById->image_name;
                    //     // $img_thumb_url = (!empty($img_thumb_path) && file_exists($img_thumb_path)) ? $img_thumb_path : '';
                    //     // $outputArray['data'][$i]['business_image'] = (!empty($img_thumb_url)) ? url($img_thumb_url) : url($this->catgoryTempImage);


                    // }
                    // else
                    // {
                    //     $outputArray['data'][$i]['business_image'] = url($this->catgoryTempImage);
                    // }


                    $imgThumbUrl = ((isset($value->businessImagesById) && !empty($value->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $outputArray['data'][$i]['business_image'] = $imgThumbUrl;

                    $businessLogoThumbImgPath = ((isset($value->business_logo) && !empty($value->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) : $imgThumbUrl;
                    $outputArray['data'][$i]['logo_thumbnail'] = $businessLogoThumbImgPath;

                    $i++;
                }
            }
            else
            {
                $this->log->info('API getRecentlyAddedBusinessListing no records found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getRecentlyAddedBusinessListing', array('login_user_id' => $user->id,  'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

     /**
     * Get recently added business listing
     */
    public function getPromotedBusinesses(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        $outputArray = [];
        try
        {
            $filters = [];
            //$filters['approved'] = 1;
            $filters['promoted'] = 1;
            $businessesCount = Business::where('promoted', $filters['promoted'])->orderBy('id', 'DESC')->count();
            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                if(isset($request->limit) && !empty($request->limit))
                {
                    $filters['take'] = $request->limit;
                    $filters['skip'] = 0;
                }
                elseif(isset($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
                elseif(isset($request->limit) && !empty($request->limit) && isset($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = $request->limit;
                    $filters['skip'] = $offset;
                }

                if(isset($request->sortBy) && !empty($request->sortBy))
                {
                    if($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                    elseif($request->sortBy == 'AtoZ')
                    {
                        $filters['sortBy'] = 'AtoZ';
                    }
                    elseif($request->sortBy == 'ZtoA')
                    {
                        $filters['sortBy'] = 'ZtoA';
                    }
                    elseif($request->sortBy == 'nearMe' && isset($request->radius) && !empty ($request->radius) && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = $request->sortBy;
                        $filters['radius'] = $request->radius;
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;

                        //$businessNearMeData = Business::where('approved', $filters['approved']);
                        $businessNearMeData = Business::whereNull('deleted_at');
                        $businessNearMeData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');
                        $businessNearMeData->having('distance', '<', $filters['radius']);

                        $businessNearMeData->orderBy('distance', 'DESC');
                        $businessesCount = $businessNearMeData->get();
                    }
                    elseif($request->sortBy == 'relevance' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = $request->sortBy;
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;

                        //$businessNearMeData = Business::where('approved', $filters['approved']);
                        $businessNearMeData = Business::whereNull('deleted_at');
                        $businessNearMeData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');

                        $businessNearMeData->orderBy('membership_type','DESC')->orderBy('distance', 'DESC');
                        $businessesCount = $businessNearMeData->get();
                    }
                }
            }
            if(isset($request->sortBy) && !empty($request->sortBy) && $request->sortBy == 'ratings')
            {
                $filters['sortBy'] = 'ratings';
                $promotedBusiness = $this->objBusiness->getBusinessesByRating($filters);
            }
            else
            {
                $promotedBusiness = $this->objBusiness->getAll($filters);
            }
            if($promotedBusiness && count($promotedBusiness) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.promoted_business_fetched_successfully');
                $statusCode = 200;

                //$searchfilters['approved'] = 1;
                $searchfilters['promoted'] = 1;
                $totalPromotedBusinesses =  $this->objBusiness->getAll($searchfilters);

                $outputArray['businessesTotalCount'] = (isset($totalPromotedBusinesses) && count($totalPromotedBusinesses) > 0) ? count($totalPromotedBusinesses) : 0;

                $outputArray['data'] = array();
                $i = 0;
                foreach ($promotedBusiness as $key => $value)
                {
                    $outputArray['data'][$i]['id'] = $value->id;
                    $outputArray['data'][$i]['name'] = $value->name;
                    $outputArray['data'][$i]['business_slug'] = (isset($value->business_slug) && !empty($value->business_slug)) ? $value->business_slug : '';
                    $outputArray['data'][$i]['user_name'] = (isset($value->user) && !empty($value->user) && !empty($value->user->name)) ? $value->user->name : '';

                    $outputArray['data'][$i]['owners'] = '';

                    if($value->owners && count($value->owners) > 0)
                    {
                        $owners = [];
                        foreach($value->owners as $owner)
                        {
                            $owners[] = $owner->full_name;
                        }
                        if(!empty($owners))
                        {
                            $outputArray['data'][$i]['owners'] = implode(', ',$owners);
                        }
                    }

                    $outputArray['data'][$i]['categories'] = array();
                    if(!empty($value->category_id) && $value->category_id != '')
                    {
                        $categoryIdsArray = (explode(',', $value->category_id));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    // if(!empty($categoryData->cat_logo))
                                    // {
                                    //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                    // }

                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                    $outputArray['data'][$i]['categories'][$j]['category_logo'] = $catLogoPath;

                                    $outputArray['data'][$i]['categories'][$j]['parent_category_id'] = $categoryData->parent;
                                    $j++;
                                }
                            }
                        }
                    }
                    $parentCatArray = [];
                    $outputArray['data'][$i]['parent_categories'] = array();
                    if(!empty($value->parent_category) && $value->parent_category != '')
                    {
                        $categoryIdsArray = (explode(',', $value->parent_category));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_logo'] = $catLogoPath;
                                    $parentCatArray[] =  $categoryData->name;
                                    $j++;
                                }
                            }
                        }
                    }
                    $outputArray['data'][$i]['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                    $outputArray['data'][$i]['categories_name_list'] = $outputArray['data'][$i]['parent_category_name'];
                    $outputArray['data'][$i]['descriptions'] = $value->description;
                    $outputArray['data'][$i]['phone'] = $value->phone;
                    $outputArray['data'][$i]['country_code'] = $value->country_code;
                    $outputArray['data'][$i]['mobile'] = $value->mobile;
                    $outputArray['data'][$i]['latitude'] = (!empty($value->latitude)) ? $value->latitude : '';
                    $outputArray['data'][$i]['longitude'] = (!empty($value->longitude)) ? $value->longitude : '';
                    $outputArray['data'][$i]['address'] = (!empty($value->address)) ? $value->address : '';
                    $outputArray['data'][$i]['street_address'] = (!empty($value->street_address)) ? $value->street_address : '';
                    $outputArray['data'][$i]['locality'] = (!empty($value->locality)) ? $value->locality : '';
                    $outputArray['data'][$i]['country'] = (!empty($value->country)) ? $value->country : '';
                    $outputArray['data'][$i]['state'] = (!empty($value->state)) ? $value->state : '';
                    $outputArray['data'][$i]['city'] = (!empty($value->city)) ? $value->city : '';
                    $outputArray['data'][$i]['taluka'] = (!empty($value->taluka)) ? $value->taluka : '';
                    $outputArray['data'][$i]['district'] = (!empty($value->district)) ? $value->district : '';
                    $outputArray['data'][$i]['pincode'] = (!empty($value->pincode)) ? $value->pincode : '';
                    $outputArray['data'][$i]['email_id'] = (!empty($value->email_id)) ? $value->email_id : '';
                    $outputArray['data'][$i]['website_url'] = (!empty($value->website_url)) ? $value->website_url : '';
                    $outputArray['data'][$i]['membership_type'] = $value->membership_type;
                    if($value->membership_type == 2)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                    }
                    elseif($value->membership_type == 1)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                    }
                    else
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                    }
                    // if(isset($value->businessImagesById) && !empty($value->businessImagesById->image_name))
                    // {
                    //     // $img_thumb_path = $this->BUSINESS_THUMBNAIL_IMAGE_PATH.$value->businessImagesById->image_name;
                    //     // $img_thumb_url = (!empty($img_thumb_path) && file_exists($img_thumb_path)) ? $img_thumb_path : '';
                    //     // $outputArray['data'][$i]['business_image'] = (!empty($img_thumb_url)) ? url($img_thumb_url) : url($this->catgoryTempImage);
                    // }
                    // else
                    // {
                    //     $outputArray['data'][$i]['business_image'] = url($this->catgoryTempImage);
                    // }

                    $imgThumbUrl = ((isset($value->businessImagesById) && !empty($value->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $outputArray['data'][$i]['business_image'] = $imgThumbUrl;

                    $businessLogoThumbImgPath = ((isset($value->business_logo) && !empty($value->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) : $imgThumbUrl;
                    $outputArray['data'][$i]['logo_thumbnail'] = $businessLogoThumbImgPath;

                    $i++;
                }
            }
            else
            {
                $this->log->info('API getPromotedBusinesses no records found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getPromotedBusinesses', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Get recently added business listing
     */
    public function getPremiumBusinesses(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = $request->header('Platform');
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        $outputArray = [];
        try
        {
            $filters = [];
            $filters['approved'] = 1;
            $filters['membership_type'] = 1;
            $filters['membership_premium_lifetime_type'] = 1;
            $filters['sortBy'] = 'membership_type';
            //$businessesCount = Business::where('approved', $filters['approved'])->where('membership_type','<>',0)->orderBy('id', 'DESC')->count();
            $businessesCount = Business::where('membership_type','<>',0)->orderBy('id', 'DESC')->count();
            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                if(isset($request->limit) && !empty($request->limit))
                {
                    $filters['take'] = $request->limit;
                    $filters['skip'] = 0;
                }
                elseif(isset($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    $filters['skip'] = $offset;
                }
                elseif(isset($request->limit) && !empty($request->limit) && isset($request->page))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['take'] = $request->limit;
                    $filters['skip'] = $offset;
                }

                if(isset($request->sortBy) && !empty($request->sortBy))
                {
                    if($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                    elseif($request->sortBy == 'AtoZ')
                    {
                        $filters['sortBy'] = 'AtoZ';
                    }
                    elseif($request->sortBy == 'ZtoA')
                    {
                        $filters['sortBy'] = 'ZtoA';
                    }
                    elseif (isset($request->sortBy) && $request->sortBy == 'relevance' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = 'relevance';
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;
                    }
                    elseif($request->sortBy == 'nearMe' && isset($request->radius) && !empty ($request->radius) && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = $request->sortBy;
                        $filters['radius'] = $request->radius;
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;

                        //$businessNearMeData = Business::where('approved', $filters['approved']);
                        $businessNearMeData = Business::whereNull('deleted_at');
                        $businessNearMeData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');
                        $businessNearMeData->having('distance', '<', $filters['radius']);

                        $businessNearMeData->orderBy('distance', 'ASC');
                        $businessesCount = $businessNearMeData->get();
                    }

                }
            }
            elseif (!empty($headerData) && $headerData == Config::get('constant.MOBILE_PLATFORM'))
            {
                if(isset($request->sortBy) && !empty($request->sortBy))
                {
                    if($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                    elseif($request->sortBy == 'AtoZ')
                    {
                        $filters['sortBy'] = 'AtoZ';
                    }
                    elseif($request->sortBy == 'ZtoA')
                    {
                        $filters['sortBy'] = 'ZtoA';
                    }
                    elseif (isset($request->sortBy) && $request->sortBy == 'relevance' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = 'relevance';
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;
                    }
                    elseif($request->sortBy == 'nearMe' && isset($request->radius) && !empty ($request->radius) && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = $request->sortBy;
                        $filters['radius'] = $request->radius;
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;

                        //$businessNearMeData = Business::where('approved', $filters['approved']);
                        $businessNearMeData = Business::whereNull('deleted_at');
                        $businessNearMeData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');
                        $businessNearMeData->having('distance', '<', $filters['radius']);

                        $businessNearMeData->orderBy('distance', 'ASC');
                        $businessesCount = $businessNearMeData->get();
                    }

                }
            }

            if(isset($request->sortBy) && !empty($request->sortBy) && $request->sortBy == 'ratings')
            {
                $filters['sortBy'] = 'ratings';
                $premiumBusiness = $this->objBusiness->getBusinessesByRating($filters);
            }
            else
            {
                $premiumBusiness = $this->objBusiness->getAll($filters);
            }
            if($premiumBusiness && count($premiumBusiness) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.premium_business_fetched_successfully');
                $statusCode = 200;

                //$searchfilters['approved'] = 1;
                $searchfilters['membership_type'] = 1;
                $searchfilters['membership_premium_lifetime_type'] = 1;
                $totalPremiumBusinesses =  $this->objBusiness->getAll($searchfilters);

                $outputArray['businessesTotalCount'] = (isset($totalPremiumBusinesses) && count($totalPremiumBusinesses) > 0) ? count($totalPremiumBusinesses) : 0;

                $outputArray['data'] = array();
                $i = 0;
                foreach ($premiumBusiness as $key => $value)
                {
                    $outputArray['data'][$i]['id'] = $value->id;
                    $outputArray['data'][$i]['name'] = $value->name;
                    $outputArray['data'][$i]['business_slug'] = (isset($value->business_slug) && !empty($value->business_slug)) ? $value->business_slug : '';
                    $outputArray['data'][$i]['user_name'] = (isset($value->user) && !empty($value->user) && !empty($value->user->name)) ? $value->user->name : '';

                    $outputArray['data'][$i]['owners'] = '';

                    if($value->owners && count($value->owners) > 0)
                    {
                        $owners = [];
                        foreach($value->owners as $owner)
                        {
                            $owners[] = $owner->full_name;
                        }
                        if(!empty($owners))
                        {
                            $outputArray['data'][$i]['owners'] = implode(', ',$owners);
                        }
                    }

                    $outputArray['data'][$i]['categories'] = array();
                    if(!empty($value->category_id) && $value->category_id != '')
                    {
                        $categoryIdsArray = (explode(',', $value->category_id));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    // if(!empty($categoryData->cat_logo))
                                    // {
                                    //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                    // }

                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                    $outputArray['data'][$i]['categories'][$j]['category_logo'] = $catLogoPath;

                                    $outputArray['data'][$i]['categories'][$j]['parent_category_id'] = $categoryData->parent;
                                    $j++;
                                }
                            }
                        }
                    }
                    $parentCatArray = [];
                    $outputArray['data'][$i]['parent_categories'] = array();
                    if(!empty($value->parent_category) && $value->parent_category != '')
                    {
                        $categoryIdsArray = (explode(',', $value->parent_category));
                        if(count($categoryIdsArray) > 0)
                        {
                            $j = 0;
                            foreach($categoryIdsArray as $cIdKey => $cIdValue)
                            {
                                $categoryData = Category::find($cIdValue);
                                if(count($categoryData) > 0 && !empty($categoryData))
                                {
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_id'] = $categoryData->id;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_name'] = $categoryData->name;
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                    // if(!empty($categoryData->cat_logo))
                                    // {
                                    //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                    // }

                                    $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                    $outputArray['data'][$i]['parent_categories'][$j]['category_logo'] = $catLogoPath;
                                    $parentCatArray[] =  $categoryData->name;
                                    $j++;
                                }
                            }
                        }
                    }
                    $outputArray['data'][$i]['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                    $outputArray['data'][$i]['categories_name_list'] = $outputArray['data'][$i]['parent_category_name'];
                    $outputArray['data'][$i]['descriptions'] = $value->description;
                    $outputArray['data'][$i]['phone'] = $value->phone;
                    $outputArray['data'][$i]['country_code'] = $value->country_code;
                    $outputArray['data'][$i]['mobile'] = $value->mobile;
                    $outputArray['data'][$i]['latitude'] = (!empty($value->latitude)) ? $value->latitude : '';
                    $outputArray['data'][$i]['longitude'] = (!empty($value->longitude)) ? $value->longitude : '';
                    $outputArray['data'][$i]['address'] = (!empty($value->address)) ? $value->address : '';
                    $outputArray['data'][$i]['street_address'] = (!empty($value->street_address)) ? $value->street_address : '';
                    $outputArray['data'][$i]['locality'] = (!empty($value->locality)) ? $value->locality : '';
                    $outputArray['data'][$i]['country'] = (!empty($value->country)) ? $value->country : '';
                    $outputArray['data'][$i]['state'] = (!empty($value->state)) ? $value->state : '';
                    $outputArray['data'][$i]['city'] = (!empty($value->city)) ? $value->city : '';
                    $outputArray['data'][$i]['taluka'] = (!empty($value->taluka)) ? $value->taluka : '';
                    $outputArray['data'][$i]['district'] = (!empty($value->district)) ? $value->district : '';
                    $outputArray['data'][$i]['pincode'] = (!empty($value->pincode)) ? $value->pincode : '';
                    $outputArray['data'][$i]['email_id'] = (!empty($value->email_id)) ? $value->email_id : '';
                    $outputArray['data'][$i]['website_url'] = (!empty($value->website_url)) ? $value->website_url : '';
                    $outputArray['data'][$i]['membership_type'] = $value->membership_type;
                     if($value->membership_type == 2)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                    }
                    elseif($value->membership_type == 1)
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                    }
                    else
                    {
                        $outputArray['data'][$i]['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                    }
                    // if(isset($value->businessImagesById) && !empty($value->businessImagesById->image_name))
                    // {
                    //     // $img_thumb_path = $this->BUSINESS_THUMBNAIL_IMAGE_PATH.$value->businessImagesById->image_name;
                    //     // $img_thumb_url = (!empty($img_thumb_path) && file_exists($img_thumb_path)) ? $img_thumb_path : '';
                    //     // $outputArray['data'][$i]['business_image'] = (!empty($img_thumb_url)) ? url($img_thumb_url) : url($this->catgoryTempImage);
                    // }
                    // else
                    // {
                    //     $outputArray['data'][$i]['business_image'] = url($this->catgoryTempImage);
                    // }

                    $imgThumbUrl = ((isset($value->businessImagesById) && !empty($value->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $outputArray['data'][$i]['business_image'] = $imgThumbUrl;

                    $businessLogoThumbImgPath = ((isset($value->business_logo) && !empty($value->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$value->business_logo) : $imgThumbUrl;
                    $outputArray['data'][$i]['logo_thumbnail'] = $businessLogoThumbImgPath;
                    $i++;
                }
            }
            else
            {
                $this->log->info('API getPremiumBusinesses no records found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getPremiumBusinesses', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Get Business Listing By Category Id
     */
    public function getBusinessListingByCatId(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $headerData = (!empty($request->header('Platform'))) ? $request->header('Platform') : '';
        try
        {
            $categoryId = (isset($request->category_id) && !empty($request->category_id)) ? $request->category_id : 0;
            $categorySlug = (isset($request->category_slug) && !empty($request->category_slug)) ? $request->category_slug : '';
            $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;

            $filters = [];
            if(!empty($headerData) && $headerData == Config::get('constant.MOBILE_PLATFORM'))
            {
                $categoryDetails = Category::find($categoryId);
                $offset = Helpers::getOffset($pageNo);
                $filters['approved'] = 1;
                $filters['offset'] = $offset;
                $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                if(isset($request->sortBy) && !empty($request->sortBy))
                {
                    if($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                    elseif($request->sortBy == 'ratings')
                    {
                        $filters['sortBy'] = 'ratings';
                    }
                    elseif($request->sortBy == 'AtoZ')
                    {
                        $filters['sortBy'] = 'AtoZ';
                    }
                    elseif($request->sortBy == 'ZtoA')
                    {
                        $filters['sortBy'] = 'ZtoA';
                    }
                    elseif (isset($request->sortBy) && $request->sortBy == 'relevance' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = 'relevance';
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;
                    }
                    elseif (isset($request->sortBy) && $request->sortBy == 'nearMe' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = 'nearMe';
                        if(isset($request->radius) && $request->radius != ''){
                            $filters['radius'] = $request->radius;
                        }
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;
                    }
                    else
                    {
                        $filters['sortBy'] = 'membership_type';
                    }
                }
                else
                {
                    $filters['sortBy'] = 'membership_type';
                }
            }
            elseif(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                $categoryDetails = Category::where('category_slug', $categorySlug)->first();
                $offset = Helpers::getWebOffset($pageNo);
                $filters['approved'] = 1;
                $filters['offset'] = $offset;
                $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                $categoryId = ($categoryDetails && count($categoryDetails) > 0) ? $categoryDetails->id : 0;
                if(isset($request->sortBy) && !empty($request->sortBy))
                {
                    if($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                    elseif($request->sortBy == 'ratings')
                    {
                        $filters['sortBy'] = 'ratings';
                    }
                    elseif($request->sortBy == 'AtoZ')
                    {
                        $filters['sortBy'] = 'AtoZ';
                    }
                    elseif($request->sortBy == 'ZtoA')
                    {
                        $filters['sortBy'] = 'ZtoA';
                    }
                    elseif (isset($request->sortBy) && $request->sortBy == 'nearMe' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                    {
                        $filters['sortBy'] = 'nearMe';
                        if(isset($request->radius) && $request->radius != ''){
                            $filters['radius'] = $request->radius;
                        }
                        $filters['latitude'] = $request->latitude;
                        $filters['longitude'] = $request->longitude;
                    }
                    else
                    {
                        $filters['sortBy'] = 'membership_type';
                    }
                }
                else
                {
                    $filters['sortBy'] = 'membership_type';
                }
            }
            else
            {
                $categoryDetails = Category::find($categoryId);
                $offset = Helpers::getOffset($pageNo);
                $filters['approved'] = 1;
                $filters['offset'] = $offset;
                $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                if(isset($request->sortBy) && !empty($request->sortBy))
                {
                    if($request->sortBy == 'popular')
                    {
                        $filters['sortBy'] = 'popular';
                    }
                    elseif($request->sortBy == 'ratings')
                    {
                        $filters['sortBy'] = 'ratings';
                    }
                    else
                    {
                        $filters['sortBy'] = 'membership_type';
                    }
                }
                else
                {
                    $filters['sortBy'] = 'membership_type';
                }
            }

            $getAllSubCategoriesByParent = Helpers::getCategorySubHierarchy($categoryId);

            if(isset($request->all) && $request->all == 1)
            {


                $whereStr = '';
                $whereArr = [];

                $whereArr[] = "FIND_IN_SET(".$categoryId.", category_id)";

                if(!empty($getAllSubCategoriesByParent))
                {
                    foreach ($getAllSubCategoriesByParent as $id) {
                        $whereArr[]  = "FIND_IN_SET(".$id.", category_id)";
                    }
                }

                if (!empty($whereArr))
                {
                    $whereStr = implode(' OR ', $whereArr);
                }

                $businessesCount = Business::whereRaw($whereStr)->orderBy('id', 'DESC')->count();

                $businessListing = $this->objBusiness->getBusinessListingByCategoryId($categoryId,$filters,$getAllSubCategoriesByParent);
            }
            else
            {
                $businessesCount = Business::where('approved', 1)->whereRaw("FIND_IN_SET(".$categoryId.",category_id)")->orderBy('id', 'DESC')->count();

                $businessListing = $this->objBusiness->getBusinessListingByCategoryId($categoryId,$filters);

            }


            if($categoryDetails)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.business_fetched_successfully');
                $statusCode = 200;
                $outputArray['data'] = array();
                $outputArray['data']['category_id'] = $categoryDetails->id;
                $outputArray['data']['category_name'] = $categoryDetails->name;
                $outputArray['data']['category_slug'] = (!empty($categoryDetails->category_slug)) ? $categoryDetails->category_slug : '';
                // if(!empty($categoryDetails->cat_logo))
                // {
                //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryDetails->cat_logo;

                // }
                $catLogoPath = (($categoryDetails->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryDetails->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryDetails->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                $outputArray['data']['category_logo'] = $catLogoPath;

                $outputArray['data']['businesses'] = array();
                if($businessListing && count($businessListing) > 0)
                {
                    $outputArray['businessesTotalCount'] = (isset($businessesCount) && $businessesCount > 0) ? $businessesCount : 0;
                    if($headerData == Config::get('constant.WEBSITE_PLATFORM'))
                    {
                        if($businessListing->count() < Config::get('constant.WEBSITE_RECORD_PER_PAGE'))
                        {
                            $outputArray['loadMore'] = 0;
                        } else {
                            $offset = Helpers::getOffset($pageNo+1);
                            $filters = [];
                            $filters['offset'] = $offset;
                            $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                            if(isset($request->sortBy) && !empty($request->sortBy) && $request->sortBy == 'popular')
                            {
                                $filters['sortBy'] = 'popular';
                            }

                            if(isset($request->all) && $request->all == 1)
                            {
                                $businessCount = $this->objBusiness->getBusinessListingByCategoryId($categoryId,$filters,$getAllSubCategoriesByParent);
                                $outputArray['loadMore'] = (count($businessCount) > 0) ? 1 : 0 ;
                            }
                            else
                            {
                                $businessCount = $this->objBusiness->getBusinessListingByCategoryId($categoryId, $filters);
                                $outputArray['loadMore'] = (count($businessCount) > 0) ? 1 : 0 ;
                            }
                        }
                    }
                    else
                    {
                        if($businessListing->count() < Config::get('constant.API_RECORD_PER_PAGE'))
                        {
                            $outputArray['loadMore'] = 0;
                        } else{
                            $offset = Helpers::getOffset($pageNo+1);
                            $filters = [];
                            $filters['offset'] = $offset;
                            $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                            if(isset($request->sortBy) && !empty($request->sortBy) && $request->sortBy == 'popular')
                            {
                                $filters['sortBy'] = 'popular';
                            }
                            if(isset($request->all) && $request->all == 1)
                            {
                                $businessCount = $this->objBusiness->getBusinessListingByCategoryId($categoryId, $offset,$getAllSubCategoriesByParent);
                                $outputArray['loadMore'] = (count($businessCount) > 0) ? 1 : 0 ;
                            }
                            else
                            {
                                $businessCount = $this->objBusiness->getBusinessListingByCategoryId($categoryId, $offset);
                                $outputArray['loadMore'] = (count($businessCount) > 0) ? 1 : 0 ;
                            }

                        }
                    }

                    $i = 0;
                    foreach ($businessListing->where('approved' , 1) as $businessKey => $businessValue)
                    //foreach ($businessListing as $businessKey => $businessValue)
                    {
                        $outputArray['data']['businesses'][$i]['user_id'] = (isset($businessValue->user) && count($businessValue->user) > 0 && !empty($businessValue->user->id)) ? $businessValue->user->id : '';
                        $outputArray['data']['businesses'][$i]['user_name'] = (isset($businessValue->user) && count($businessValue->user) > 0 && !empty($businessValue->user->name)) ? $businessValue->user->name : '';

                        $outputArray['data']['businesses'][$i]['owners'] = '';

                        if($businessValue->owners && count($businessValue->owners) > 0)
                        {
                            $owners = [];
                            foreach($businessValue->owners as $owner)
                            {
                                $owners[] = $owner->full_name;
                            }
                            if(!empty($owners))
                            {
                                $outputArray['data']['businesses'][$i]['owners'] = implode(', ',$owners);
                            }
                        }


                        $outputArray['data']['businesses'][$i]['created_by'] = (isset($businessValue->owners) && count($businessValue->owners) > 0 && !empty($businessValue->businessCreatedBy->name)) ? $businessValue->businessCreatedBy->name : '';

                        $outputArray['data']['businesses'][$i]['id'] = $businessValue->id;
                        $outputArray['data']['businesses'][$i]['name'] = $businessValue->name;
                        $outputArray['data']['businesses'][$i]['business_slug'] = (isset($businessValue->business_slug) && !empty($businessValue->business_slug)) ? $businessValue->business_slug : '';
                        $outputArray['data']['businesses'][$i]['email_id'] = $businessValue->email_id;
                        $outputArray['data']['businesses'][$i]['phone'] = $businessValue->phone;
                        $outputArray['data']['businesses'][$i]['country_code'] = $businessValue->country_code;
                        $outputArray['data']['businesses'][$i]['mobile'] = $businessValue->mobile;
                        $outputArray['data']['businesses'][$i]['descriptions'] = $businessValue->description;
                        // if(isset($businessValue->businessImagesById) && !empty($businessValue->businessImagesById) && !empty($businessValue->businessImagesById->image_name))
                        // {
                        //     // $img_thumb_path = $this->BUSINESS_THUMBNAIL_IMAGE_PATH.$businessValue->businessImagesById->image_name;
                        //     // $img_thumb_url = (!empty($img_thumb_path) && file_exists($img_thumb_path)) ? $img_thumb_path : '';
                        //     // $outputArray['data']['businesses'][$i]['business_image'] = (!empty($img_thumb_url)) ? url($img_thumb_url) : url($this->catgoryTempImage);


                        // }
                        // else
                        // {
                        //     $outputArray['data']['businesses'][$i]['business_image'] = url($this->catgoryTempImage);
                        // }

                        $imgThumbUrl = ((isset($businessValue->businessImagesById) && !empty($businessValue->businessImagesById) && !empty($businessValue->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$businessValue->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$businessValue->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $outputArray['data']['businesses'][$i]['business_image'] = $imgThumbUrl;

                        $businessLogoThumbImgPath = ((isset($businessValue->business_logo) && !empty($businessValue->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$businessValue->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$businessValue->business_logo) : $imgThumbUrl;
                        $outputArray['data']['businesses'][$i]['logo_thumbnail'] = $businessLogoThumbImgPath;

                        $outputArray['data']['businesses'][$i]['address'] = $businessValue->address;
                        $outputArray['data']['businesses'][$i]['street_address'] = $businessValue->street_address;
                        $outputArray['data']['businesses'][$i]['locality'] = $businessValue->locality;
                        $outputArray['data']['businesses'][$i]['country'] = $businessValue->country;
                        $outputArray['data']['businesses'][$i]['state'] = $businessValue->state;
                        $outputArray['data']['businesses'][$i]['city'] = $businessValue->city;
                        $outputArray['data']['businesses'][$i]['taluka'] = $businessValue->taluka;
                        $outputArray['data']['businesses'][$i]['district'] = $businessValue->district;
                        $outputArray['data']['businesses'][$i]['pincode'] = $businessValue->pincode;

                        $outputArray['data']['businesses'][$i]['latitude'] = (!empty($businessValue->latitude)) ? $businessValue->latitude : 0;
                        $outputArray['data']['businesses'][$i]['longitude'] = (!empty($businessValue->longitude)) ? $businessValue->longitude : 0;
                        $outputArray['data']['businesses'][$i]['website_url'] = $businessValue->website_url;
                        $outputArray['data']['businesses'][$i]['membership_type'] = $businessValue->membership_type;
                         if($businessValue->membership_type == 2)
                        {
                            $outputArray['data']['businesses'][$i]['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                        }
                        elseif($businessValue->membership_type == 1)
                        {
                            $outputArray['data']['businesses'][$i]['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                        }
                        else
                        {
                            $outputArray['data']['businesses'][$i]['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                        }


                        $outputArray['data']['businesses'][$i]['categories'] = array();

                        if(!empty($businessValue->category_id) && $businessValue->category_id != '')
                        {
                            $categoryIdsArray = (explode(',', $businessValue->category_id));
                            if(count($categoryIdsArray) > 0)
                            {
                                $j = 0;
                                foreach($categoryIdsArray as $cIdKey => $cIdValue)
                                {
                                    $categoryData = Category::find($cIdValue);
                                    if(count($categoryData) > 0 && !empty($categoryData))
                                    {
                                        $outputArray['data']['businesses'][$i]['categories'][$j]['category_id'] = $categoryData->id;
                                        $outputArray['data']['businesses'][$i]['categories'][$j]['category_name'] = $categoryData->name;
                                        $outputArray['data']['businesses'][$i]['categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                        // if(!empty($categoryData->cat_logo))
                                        // {
                                        //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                        // }
                                        $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                        $outputArray['data']['businesses'][$i]['categories'][$j]['category_logo'] = $catLogoPath;

                                        $outputArray['data']['businesses'][$i]['categories'][$j]['parent_category_id'] = $categoryData->parent;
                                        $j++;
                                    }
                                }
                            }
                        }
                        $parentCatArray = [];
                        $outputArray['data']['businesses'][$i]['parent_categories'] = array();

                        if(!empty($businessValue->parent_category) && $businessValue->parent_category != '')
                        {
                            $categoryIdsArray = (explode(',', $businessValue->parent_category));
                            if(count($categoryIdsArray) > 0)
                            {
                                $j = 0;
                                foreach($categoryIdsArray as $cIdKey => $cIdValue)
                                {
                                    $categoryData = Category::find($cIdValue);
                                    if(count($categoryData) > 0 && !empty($categoryData))
                                    {
                                        $outputArray['data']['businesses'][$i]['parent_categories'][$j]['category_id'] = $categoryData->id;
                                        $outputArray['data']['businesses'][$i]['parent_categories'][$j]['category_name'] = $categoryData->name;
                                        $outputArray['data']['businesses'][$i]['parent_categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';
                                        // if(!empty($categoryData->cat_logo))
                                        // {
                                        //     //$catLogoPath = $this->categoryLogoThumbImagePath.$categoryData->cat_logo;

                                        // }
                                        $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                        $outputArray['data']['businesses'][$i]['parent_categories'][$j]['category_logo'] = $catLogoPath;
                                        $parentCatArray[] =  $categoryData->name;
                                        $j++;
                                    }
                                }
                            }
                        }
                        $outputArray['data']['businesses'][$i]['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                        $outputArray['data']['businesses'][$i]['categories_name_list'] = $outputArray['data']['businesses'][$i]['parent_category_name'];

                        if(isset($request->all) && $request->all == 1)
                        {
                            $data = $businessValue->category_hierarchy;
                            $categoryHierarchyExplode = explode('|',$data);
                            if(!empty($categoryHierarchyExplode))
                            {
                                $catName = [];
                                foreach($categoryHierarchyExplode as $explode)
                                {
                                    $ex = explode(',',$explode);
                                    if(in_array($categoryId,$ex))
                                    {
                                        $catInfo = Category::find(end($ex));
                                        if(count($catInfo) > 0)
                                        {
                                            $catName[] = $catInfo->name;
                                        }
                                    }
                                }
                                if(!empty($catName))
                                {
                                    $outputArray['data']['businesses'][$i]['categories_name_list'] = implode(', ',$catName);
                                }
                            }
                        }


                        $i++;
                    }
                }
            }
            else
            {
                $this->log->info('API getBusinessListingByCatId no records found', array('login_user_id' => $user->id));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $outputArray['loadMore'] = 0;
                $statusCode = 200;
                $outputArray['data'] = new \stdClass();
            }

            return response()->json($outputArray, $statusCode);
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getBusinessListingByCatId', array('login_user_id' => $user->id));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = 400;
            return response()->json($outputArray, $statusCode);
        }
    }

    /**
     * Add Business
     */
    public function addBusiness(Request $request)
    {
        $userId = Auth::id();
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try
        {
            $validator = Validator::make($request->all(), [
                'mobile' => 'required|min:6|max:13',
                'name' => 'required',
                'address' => 'required',
                'country_code' => 'required'
            ]);
            if ($validator->fails())
            {
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
            }
            else
            {
                $requestData['user_id'] = $userId;
                $businessName = trim($requestData['name']);
//                $businessSlug = (!empty($businessName) &&  $businessName != '') ? Helpers::getSlug($businessName) : NULL;
//                $requestData['business_slug'] = $businessSlug;
                $businessSlug = SlugService::createSlug(Business::class, 'business_slug', $businessName);
                $requestData['business_slug'] = (isset($businessSlug) && !empty($businessSlug)) ? $businessSlug : NULL;
                $requestData['street_address'] = $requestData['address'];
                $requestData['address'] = '';

                $categoryDetail = Category::where('name','General')->first();
                if($categoryDetail && count($categoryDetail) > 0)
                {
                    $requestData['category_id'] = $categoryDetail->id;
                    $requestData['parent_category'] = $categoryDetail->id;
                    $requestData['category_hierarchy'] = $categoryDetail->id;
                }

                $checkExist = $this->objBusiness->where('user_id',$userId)->first();

                if(count($checkExist) > 0)
                {
                    $this->log->error('API something went wrong while add business', array('login_user_id' => $userId));
                    $responseData['status'] = 0;
                    $responseData['message'] = 'Business already exist';
                    $statusCode = 200;
                    return response()->json($responseData, $statusCode);
                }
                else
                {
                    $response = $this->objBusiness->insertUpdate($requestData);
                }



                if((isset($requestData['latitude']) && $requestData['latitude'] != '') && (isset($requestData['longitude']) && $requestData['longitude'] != ''))
                {
                    $business_address_attributes = Helpers::getAddressAttributes($requestData['latitude'], $requestData['longitude']);
                    $business_address_attributes['business_id'] = $response->id;
                    $businessObject = Business::find($response->id);
                    if (!$businessObject->business_address) {
                        $businessObject->business_address()->create($business_address_attributes);
                    } else {
                        $businessObject->business_address()->update($business_address_attributes);
                    }
                }


                Cache::forget('membersForApproval');
                Cache::forget('businessesData');
                if($response)
                {
                    $userData = User::find($userId);
                    if($userData)
                    {
                        if($userData->country_code == '+91')
                        {
                            $smsResponse = Helpers::sendMessage($userData->phone, "Dear ".$userData->name.", Welcome to Rajput Yuva Entrepreneur Club, We received your business profile. Our team will review and get in touch with you.");

                        }
                        $ownerInsert = [];
                        $ownerInsert['id'] = 0;
                        $ownerInsert['business_id'] = $response->id;
                        $ownerInsert['full_name'] = $userData->name;
                        $ownerInsert['gender'] = $userData->gender;
                        $ownerInsert['dob'] = $userData->dob;
                        $ownerInsert['email_id'] = $userData->email;
                        if($userData->profile_pic && !empty($userData->profile_pic))
                        {
                            $imageArray = Helpers::getProfileExtraFields($userData);
                            if($imageArray && !empty($imageArray['profile_pic_original']) && !empty($imageArray['profile_pic_original']))
                            {
                                $userOriginalImgPath = $imageArray['profile_pic_original'];
                                $userThumbnailImgPath = $imageArray['profile_pic_thumbnail'];

                                $userOriginalImageInfo = pathinfo($userOriginalImgPath);
                                $userThumbnailImageInfo = pathinfo($userThumbnailImgPath);

                                $ownereExtension = $userOriginalImageInfo['extension'];
                                $ownerFileName = 'owner_'.uniqid().'.'.$ownereExtension;

                                $ownerOriginalImgPath = public_path($this->OWNER_ORIGINAL_IMAGE_PATH.$ownerFileName);
                                $ownerThumbnailImgPath = public_path($this->OWNER_THUMBNAIL_IMAGE_PATH.$ownerFileName);

                                File::copy($userOriginalImgPath, $ownerOriginalImgPath);
                                File::copy($userThumbnailImgPath, $ownerThumbnailImgPath);

                                //Uploading on AWS
                                $originalOwnerImage = Helpers::addFileToStorage($ownerFileName, $this->OWNER_ORIGINAL_IMAGE_PATH, $ownerOriginalImgPath, "s3");
                                $thumbOwnerImage = Helpers::addFileToStorage($ownerFileName, $this->OWNER_THUMBNAIL_IMAGE_PATH, $ownerThumbnailImgPath, "s3");

                                //Deleting Local Files
                                File::delete($ownerOriginalImgPath, $ownerThumbnailImgPath);
                                $ownerInsert['photo'] = $ownerFileName;
                            }
                            else{
                                $ownerInsert['photo'] = NULL;
                            }
                        }
                        else
                        {
                             $ownerInsert['photo'] = NULL;
                        }
                         $ownerSave = $this->objOwner->insertUpdate($ownerInsert);
                    }

                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.business_added_success');
                    $responseData['data'] = ['business_id' => $response->id, 'business_name' => $response->name, 'business_slag' => $response->business_slug];
                    $statusCode = 200;
                    $this->log->info('API addBusiness save successfully', array('login_user_id' => $userId));
                }
                else
                {
                    $this->log->error('API something went wrong while add business', array('login_user_id' => $userId));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while add business', array('login_user_id' => $userId, 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

     /**
     * Get Timezone
     */
    public function getTimezone(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try
        {
            $listArray = [];

            $timezone = Helpers::getTimezone();

            if(!empty($timezone))
            {
                $listArray = [];
                foreach($timezone as $zone)
                {
                    $listArray[] = $zone;
                }
                $responseData['status'] = 1;
                $responseData['message'] =  'Success';
                $responseData['data'] =  $listArray;

                $statusCode = 200;
            }
            else
            {
                $responseData['status'] = 0;
                $responseData['message'] = trans('apimessages.invalid_owner_id');
                $responseData['data'] = [];
                $statusCode = 200;
            }

        } catch (Exception $e) {
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /*
    ** get SearchAutocomplete
    */
    public function getSearchAutocomplete(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try
        {
            $listArray = [];

            if(isset($requestData['searchText']) && $requestData['searchText'] != '')
            {
                $tagArray = [];
                $metatagslist = $this->objMetatag->getAll(array('searchText'=>$requestData['searchText']));
                if(count($metatagslist) > 0)
                {
                    foreach ($metatagslist as $key => $value) {
                        $listArray = [];
                        $listArray['value'] = $value->tag;
                        $tagArray[] = $listArray;
                    }
                }

                $categoryArray = [];
                $categoryList = $this->objCategory->getAll(array('searchText'=>$requestData['searchText']));
                if(count($categoryList) > 0)
                {
                    foreach ($categoryList as $key => $value) {

                        $categoryHasBusiness = $this->objCategory->categoryHasBusinesses($value->id);

                        if(!empty($categoryHasBusiness)  && count($categoryHasBusiness))
                        {
                            $listArray = [];
                            $listArray['value'] = $value->name;
                            $categoryArray[] = $listArray;
                        }
                    }
                }
                $this->log->info('User Add tag', array('admin_user_id' =>  Auth::id()));
                $responseData['status'] = 1;
                $responseData['message'] =  trans('apimessages.get_tags_successfully');;
                $responseData['data'] = array_merge($categoryArray,$tagArray);

                $statusCode = 200;
            }
            else
            {
                $this->log->error('Admin something went user Add tag', array('admin_user_id' =>  Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = trans('apimessages.norecordsfound');
                $responseData['data'] = [];
                $statusCode = 200;
            }

        } catch (Exception $e) {
            $this->log->error('Admin something went user Add tag', array('admin_user_id' =>  Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

     /**
     * Get Business Listing By Category Id
     */
    public function getSearchBusinesses(Request $request)
    {

        $user = JWTAuth::parseToken()->authenticate();
        $headerData = (!empty($request->header('Platform'))) ? $request->header('Platform') : '';
        $searchCity = '';
        try
        {

                $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;

                $filters = [];
                if(!empty($headerData) && $headerData == Config::get('constant.MOBILE_PLATFORM'))
                {
                    $offset = Helpers::getOffset($pageNo);
                    $filters['approved'] = 1;
                    $filters['offset'] = $offset;
                    $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                    if(isset($request->searchText) && $request->searchText != '')
                    {
                        $filters['searchText'] = $request->searchText;
                    }

                    if(isset($request->city) && $request->city != ''){
                        $filters['city'] = $request->city;
                        $searchCity = $request->city;
                    }

                    if(isset($request->sortBy) && !empty($request->sortBy))
                    {
                        if($request->sortBy == 'popular')
                        {
                            $filters['sortBy'] = 'popular';
                        }
                        elseif($request->sortBy == 'ratings')
                        {
                            $filters['sortBy'] = 'ratings';
                        }
                        elseif($request->sortBy == 'AtoZ')
                        {
                            $filters['sortBy'] = 'AtoZ';
                        }
                        elseif($request->sortBy == 'ZtoA')
                        {
                            $filters['sortBy'] = 'ZtoA';
                        }
                        elseif (isset($request->sortBy) && $request->sortBy == 'relevance' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                        {
                            $filters['sortBy'] = 'relevance';
                            $filters['latitude'] = $request->latitude;
                            $filters['longitude'] = $request->longitude;
                        }
                        elseif (isset($request->sortBy) && $request->sortBy == 'nearMe' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                        {
                            $filters['sortBy'] = 'nearMe';
                            if(isset($request->radius) && $request->radius != ''){
                                $filters['radius'] = $request->radius;
                            }
                            $filters['latitude'] = $request->latitude;
                            $filters['longitude'] = $request->longitude;
                        }
                        else
                        {
                            $filters['sortBy'] = 'membership_type';
                        }
                    }
                    else
                    {
                        $filters['sortBy'] = 'membership_type';
                    }
                }
                elseif(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
                {
                    $take =(isset($request->take) && $request->take != '') ? $request->take : Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    $offset = Helpers::getOffset($pageNo,$take);
                    $filters['approved'] = 1;
                    $filters['offset'] = $offset;
                    $filters['take'] = $take;
                    if(isset($request->searchText) && $request->searchText != '')
                    {
                        $filters['searchText'] = $request->searchText;
                    }

                    if(isset($request->city) && $request->city != ''){
                        $filters['city'] = $request->city;
                        $searchCity = $request->city;
                    }
                    if(isset($request->sortBy) && !empty($request->sortBy))
                    {
                        if($request->sortBy == 'popular')
                        {
                            $filters['sortBy'] = 'popular';
                        }
                        elseif($request->sortBy == 'ratings')
                        {
                            $filters['sortBy'] = 'ratings';
                        }
                        elseif($request->sortBy == 'AtoZ')
                        {
                            $filters['sortBy'] = 'AtoZ';
                        }
                        elseif($request->sortBy == 'ZtoA')
                        {
                            $filters['sortBy'] = 'ZtoA';
                        }
                        elseif (isset($request->sortBy) && $request->sortBy == 'nearMe' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                        {
                            $filters['sortBy'] = 'nearMe';
                            if(isset($request->radius) && $request->radius != ''){
                                $filters['radius'] = $request->radius;
                            }
                            $filters['latitude'] = $request->latitude;
                            $filters['longitude'] = $request->longitude;
                        }
                        else
                        {
                            $filters['sortBy'] = 'membership_type';
                        }
                    }
                    else
                    {
                        $filters['sortBy'] = 'membership_type';
                    }

                }
                else
                {
                    $offset = Helpers::getOffset($pageNo);
                    $filters['approved'] = 1;
                    $filters['offset'] = $offset;
                    $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                    if(isset($request->sortBy) && !empty($request->sortBy))
                    {
                        if($request->sortBy == 'popular')
                        {
                            $filters['sortBy'] = 'popular';
                        }
                        elseif($request->sortBy == 'ratings')
                        {
                            $filters['sortBy'] = 'ratings';
                        }
                        else
                        {
                            $filters['sortBy'] = 'membership_type';
                        }
                    }
                    else
                    {
                        $filters['sortBy'] = 'membership_type';
                    }
                }

                $businessListing = $this->objBusiness->getAll($filters);

                $searchFilters = [];
                if(isset($filters['searchText']) && $filters['searchText'] != '')
                {
                    $searchFilters['searchText'] = $filters['searchText'];
                }

                $searchFilters['approved'] = 1;
                if(isset($filters['city']) && $filters['city'] != ''){
                    $searchFilters['city'] = $filters['city'];
                }

                $businessAllListing = $this->objBusiness->getAll($searchFilters);
                $businessesCount = $businessAllListing->count();
                $pushNotification = [];
                // if($businessesCount > 0)
                // {
                //     foreach($businessAllListing as $business)
                //     {
                //         $pushNotification[] = $business->user_id;
                //     }
                // }
                // if(!empty($pushNotification))
                // {
                //     $userIds = implode(',',array_unique($pushNotification));
                //     Notification::create([
                //         'notification_type' => 'membersearchbusiness',
                //         'user_id' => $userIds,
                //         'chennel_type' => 'push',
                //         'message' => "Dear [FULL_NAME], Your business [BUSIENSS_NAME] got appear." ,
                //         'search_by' => $user->id,
                //         'search_text' => (isset($request->searchText) && $request->searchText != '') ? $request->searchText : '' ,
                //         'city' => (isset($request->city) && $request->city != '') ? $request->city : '' ,
                //     ]);
                // }

                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.business_fetched_successfully');
                $statusCode = 200;

                $outputArray['data'] = array();
                $outputArray['data']['businesses'] = array();

                if($businessListing && count($businessListing) > 0)
                {
                    TempSearchTerm::create(['search_term' => $request->searchText, 'city' => $searchCity]);

                    $outputArray['businessesTotalCount'] = (isset($businessesCount) && $businessesCount > 0) ? $businessesCount : 0;
                    if($headerData == Config::get('constant.WEBSITE_PLATFORM'))
                    {
                        $take = (isset($request->take) && $request->take > 0) ? $request->take : Config::get('constant.WEBSITE_RECORD_PER_PAGE');

                        if($businessListing->count() < $take)
                        {
                            $outputArray['loadMore'] = 0;
                        } else {
                            $perPageCnt = $request->page * $take;
                            if($businessesCount > $perPageCnt)
                            {
                                $outputArray['loadMore'] = 1;
                            }
                            else
                            {
                                $outputArray['loadMore'] = 0;
                            }
                        }
                    }
                    else
                    {
                        if($businessListing->count() < Config::get('constant.API_RECORD_PER_PAGE'))
                        {
                            $outputArray['loadMore'] = 0;
                        } else{

                            $take = (isset($request->take) && $request->take > 0) ? $request->take : Config::get('constant.API_RECORD_PER_PAGE');

                            if($businessListing->count() < $take)
                            {
                                $outputArray['loadMore'] = 0;
                            } else {
                                $perPageCnt = $request->page * $take;
                                if($businessesCount > $perPageCnt)
                                {
                                    $outputArray['loadMore'] = 1;
                                }
                                else
                                {
                                    $outputArray['loadMore'] = 0;
                                }
                            }
                        }
                    }

                    $i = 0;
                    foreach ($businessListing->where('approved' , 1) as $businessKey => $businessValue)
                    //foreach ($businessListing as $businessKey => $businessValue)
                    {
                        $outputArray['data']['businesses'][$i]['user_id'] = (isset($businessValue->user) && count($businessValue->user) > 0 && !empty($businessValue->user->id)) ? $businessValue->user->id : '';
                        $outputArray['data']['businesses'][$i]['user_name'] = (isset($businessValue->user) && count($businessValue->user) > 0 && !empty($businessValue->user->name)) ? $businessValue->user->name : '';

                        $outputArray['data']['businesses'][$i]['owners'] = '';

                        if($businessValue->owners && count($businessValue->owners) > 0)
                        {
                            $owners = [];
                            foreach($businessValue->owners as $owner)
                            {
                                $owners[] = $owner->full_name;
                            }
                            if(!empty($owners))
                            {
                                $outputArray['data']['businesses'][$i]['owners'] = implode(', ',$owners);
                            }
                        }

                        $outputArray['data']['businesses'][$i]['id'] = $businessValue->id;
                        $outputArray['data']['businesses'][$i]['name'] = $businessValue->name;
                        $outputArray['data']['businesses'][$i]['business_slug'] = (isset($businessValue->business_slug) && !empty($businessValue->business_slug)) ? $businessValue->business_slug : '';
                        $outputArray['data']['businesses'][$i]['email_id'] = $businessValue->email_id;
                        $outputArray['data']['businesses'][$i]['phone'] = $businessValue->phone;
                        $outputArray['data']['businesses'][$i]['country_code'] = $businessValue->country_code;
                        $outputArray['data']['businesses'][$i]['mobile'] = $businessValue->mobile;
                        $outputArray['data']['businesses'][$i]['descriptions'] = $businessValue->description;

                        $imgThumbUrl = ((isset($businessValue->businessImagesById) && !empty($businessValue->businessImagesById) && !empty($businessValue->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$businessValue->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$businessValue->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $outputArray['data']['businesses'][$i]['business_image'] = $imgThumbUrl;

                        $businessLogoThumbImgPath = ((isset($businessValue->business_logo) && !empty($businessValue->business_logo)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$businessValue->business_logo) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$businessValue->business_logo) : $imgThumbUrl;
                        $outputArray['data']['businesses'][$i]['logo_thumbnail'] = $businessLogoThumbImgPath;

                        $outputArray['data']['businesses'][$i]['address'] = $businessValue->address;
                        $outputArray['data']['businesses'][$i]['street_address'] = $businessValue->street_address;
                        $outputArray['data']['businesses'][$i]['locality'] = $businessValue->locality;
                        $outputArray['data']['businesses'][$i]['country'] = $businessValue->country;
                        $outputArray['data']['businesses'][$i]['state'] = $businessValue->state;
                        $outputArray['data']['businesses'][$i]['city'] = $businessValue->city;
                        $outputArray['data']['businesses'][$i]['taluka'] = $businessValue->taluka;
                        $outputArray['data']['businesses'][$i]['district'] = $businessValue->district;
                        $outputArray['data']['businesses'][$i]['pincode'] = $businessValue->pincode;

                        $outputArray['data']['businesses'][$i]['latitude'] = (!empty($businessValue->latitude)) ? $businessValue->latitude : 0;
                        $outputArray['data']['businesses'][$i]['longitude'] = (!empty($businessValue->longitude)) ? $businessValue->longitude : 0;
                        $outputArray['data']['businesses'][$i]['website_url'] = $businessValue->website_url;
                        $outputArray['data']['businesses'][$i]['membership_type'] = $businessValue->membership_type;
                        if($businessValue->membership_type == 2)
                        {
                            $outputArray['data']['businesses'][$i]['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                        }
                        elseif($businessValue->membership_type == 1)
                        {
                            $outputArray['data']['businesses'][$i]['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                        }
                        else
                        {
                            $outputArray['data']['businesses'][$i]['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                        }


                        $outputArray['data']['businesses'][$i]['categories'] = array();

                        if(!empty($businessValue->category_id) && $businessValue->category_id != '')
                        {
                            $categoryIdsArray = (explode(',', $businessValue->category_id));
                            if(count($categoryIdsArray) > 0)
                            {
                                $j = 0;
                                foreach($categoryIdsArray as $cIdKey => $cIdValue)
                                {
                                    $categoryData = Category::find($cIdValue);
                                    if(count($categoryData) > 0 && !empty($categoryData))
                                    {
                                        $outputArray['data']['businesses'][$i]['categories'][$j]['category_id'] = $categoryData->id;
                                        $outputArray['data']['businesses'][$i]['categories'][$j]['category_name'] = $categoryData->name;
                                        $outputArray['data']['businesses'][$i]['categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';

                                        $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                                        $outputArray['data']['businesses'][$i]['categories'][$j]['category_logo'] = $catLogoPath;

                                        $outputArray['data']['businesses'][$i]['categories'][$j]['parent_category_id'] = $categoryData->parent;
                                        $j++;
                                    }
                                }
                            }
                        }

                        $parentCatArray = [];

                        $outputArray['data']['businesses'][$i]['parent_categories'] = array();

                        if(!empty($businessValue->parent_category) && $businessValue->parent_category != '')
                        {
                            $categoryIdsArray = (explode(',', $businessValue->parent_category));
                            if(count($categoryIdsArray) > 0)
                            {
                                $j = 0;
                                foreach($categoryIdsArray as $cIdKey => $cIdValue)
                                {
                                    $categoryData = Category::find($cIdValue);
                                    if(count($categoryData) > 0 && !empty($categoryData))
                                    {
                                        $outputArray['data']['businesses'][$i]['parent_categories'][$j]['category_id'] = $categoryData->id;
                                        $outputArray['data']['businesses'][$i]['parent_categories'][$j]['category_name'] = $categoryData->name;
                                        $outputArray['data']['businesses'][$i]['parent_categories'][$j]['category_slug'] = (!empty($categoryData->category_slug)) ? $categoryData->category_slug : '';

                                        $catLogoPath = (($categoryData->cat_logo != '') && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$categoryData->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                                        $outputArray['data']['businesses'][$i]['parent_categories'][$j]['category_logo'] = $catLogoPath;
                                        $parentCatArray[] =  $categoryData->name;
                                        $j++;
                                    }
                                }
                            }
                        }

                        $outputArray['data']['businesses'][$i]['parent_category_name'] = (!empty($parentCatArray)) ? implode(', ',$parentCatArray) : '';
                        $outputArray['data']['businesses'][$i]['categories_name_list'] = $outputArray['data']['businesses'][$i]['parent_category_name'];

                        $i++;
                    }
                }
                else
                {
                    $this->log->info('API getBusinessListingByCatId no records found', array('login_user_id' => $user->id));
                    $outputArray['status'] = 1;
                    $outputArray['message'] = trans('apimessages.norecordsfound');
                    $outputArray['loadMore'] = 0;
                    $statusCode = 200;
                    $outputArray['data'] = new \stdClass();
                }

                return response()->json($outputArray, $statusCode);

        } catch (Exception $e) {
            $this->log->error('API something went wrong while getBusinessListingByCatId', array('login_user_id' => $user->id));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = 400;
            return response()->json($outputArray, $statusCode);
        }
    }

    public function getBusinessApproved(Request $request)
    {

        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try
        {
            $validator = Validator::make($request->all(), [
                'business_id' => 'required'
            ]);
            if ($validator->fails())
            {
                $this->log->error('API something went wrong while get business is approved or not', array('login_user_id' => Auth::id(),'business_id' => $requestData['business_id'], 'error' => $e->getMessage()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
            }
            else
            {
                $response = $this->objBusiness->find($requestData['business_id']);

                if($response)
                {
                    $mainArray = [];

                    $mainArray['isApproved'] = $response->approved;
                    $mainArray['membership_type'] = $response->membership_type;
                    if($response->membership_type == 2)
                    {
                        $mainArray['membership_type'] = 1;
                        $mainArray['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                    }
                    elseif($response->membership_type == 1)
                    {
                       $mainArray['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                    }
                    else
                    {
                        $mainArray['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                    }

                    $this->log->info('API get business approved or not', array('login_user_id' => Auth::id(),'business_id' => $requestData['business_id']));
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.default_success_msg');
                    $responseData['data'] =  $mainArray;
                    $statusCode = 200;

                }
                else
                {
                    $this->log->info('API Invalid business id', array('login_user_id' => Auth::id(),'business_id' => $requestData['business_id']));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.invalid_business_id');
                    $responseData['data'] =  [];
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while get business is approved or not', array('login_user_id' => Auth::id(),'business_id' => $requestData['business_id'], 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    public function sendMembershipRequest(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $validator = Validator::make($requestData, [
                'subscription_plans_id' => 'required',
            ]);
            if ($validator->fails())
            {
                $this->log->error('API something went wrong while send membership plan request', array('login_user_id' => Auth::id(),'subscription_plans_id' => $requestData['subscription_plans_id']));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray, $statusCode);
            }
            else
            {
                MembershipRequest::firstOrCreate(['subscription_plans_id' =>  $requestData['subscription_plans_id'], 'user_id' => $user->id]);
                $this->log->info('API Membership plan request sent successfully', array('login_user_id' =>Auth::id(),'subscription_plans_id' => $requestData['subscription_plans_id']));
                $responseData['status'] = 1;
                $responseData['message'] =  trans('apimessages.membership_plan_sent_successfully');
                $statusCode = 200;
            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while send membership plan request', array('login_user_id' => Auth::id(),'subscription_plans_id' => $requestData['subscription_plans_id'], 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    public function getBusinessNearByMap(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
                'radius' => 'required'
            ]);
            if ($validator->fails())
            {
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
            }
            else
            {
                $filters = [];

                $filters['sortBy'] = 'nearMe';
                $filters['radius'] = $request->radius;
                $filters['latitude'] = $request->latitude;
                $filters['longitude'] = $request->longitude;

                $businessList = $this->objBusiness->getAll($filters);
                if(count($businessList) > 0)
                {
                    $mainArray = [];
                    foreach($businessList as $business)
                    {
                        $listArray = [];
                        $listArray['id'] = $business->id;
                        $listArray['name'] = $business->name;
                        $listArray['latitude'] = $business->latitude;
                        $listArray['longitude'] = $business->longitude;

                        $address = [];
                        $addressImplode = '';
                        if($business->street_address != ''){
                            $address[] = $business->street_address;
                        }
                        if($business->locality != '')
                        {
                            $address[] = $business->locality;
                        }
                        if($business->city != '')
                        {
                            $address[] = $business->city;
                        }
                        if($business->state != '')
                        {
                            $address[] = $business->state;
                        }
                        if(!empty($address))
                        {
                            $addressImplode = implode(', ',$address);
                        }

                        $listArray['address'] = $addressImplode;
                        if($business->pincode != '' && $listArray['address'] != '')
                        {
                            $listArray['address'] = $addressImplode.' - '.$business->pincode;
                        }

                        $mainArray[] = $listArray;
                    }
                        $responseData['status'] = 1;
                        $responseData['message'] =  trans('apimessages.default_success_msg');
                        $responseData['data'] =  $mainArray;
                        $statusCode = 200;

                }
                else
                {
                    $this->log->error('API something went wrong while get BusinessList', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.norecordfound');
                    $responseData['data'] =  [];
                    $statusCode = 200;
                }
            }


        } catch (Exception $e) {
            $this->log->error('API something went wrong while get BusinessList', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
    *  Get getBusinessBranding
    */
    public function getBrandingFileOrText(Request $request)
    {
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try
        {
            $mainArray = [];
            $brandingDetail = Branding::first();
            if(count($brandingDetail) > 0)
            {
                $type = '';
                $mainArray['id'] = $brandingDetail->id;
                $mainArray['name'] = ($brandingDetail->type == 1) ? url('images/branding_image.png') : $brandingDetail->name;
                if($brandingDetail->type == 1)
                {
                    $type = 'image';
                }
                elseif($brandingDetail->type == 2)
                {
                    $type = 'video';
                    $videoId = '';
                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $brandingDetail->name, $match))
                    {
                        $videoId = $match[1];
                    }
                    $mainArray['videoId'] = $videoId;
                }
                else
                {
                    $type = 'text';
                }
                $mainArray['type'] = $type;
                $this->log->info('API getBusinessBranding successfully', array('login_user_id' => Auth::id()));
                $responseData['status'] = 1;
                $responseData['message'] = trans('apimessages.default_success_msg');
                $responseData['data'] =  $mainArray;
                $statusCode = 200;
            }
            else
            {
                $this->log->info('API get business branding not found', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = trans('apimessages.norecordsfound');
                $responseData['data'] =  $mainArray;
                $statusCode = 200;
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getBusinessBranding', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }
}
