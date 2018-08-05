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
use App\UserMetaData;
use App\UserRole;
use App\Category;
use App\BusinessRatings;
use App\Product;
use App\ProductImage;
use App\Service;
use Crypt;
use Response;
use Carbon\Carbon;
use Mail;
use Session;
use Validator;
use JWTAuth;
use JWTAuthException;
use Cache;
use \stdClass;
use Storage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class OwnerController extends Controller
{
    public function __construct()
    {
        $this->objBusiness = new Business();
        $this->objBusinessImage = new BusinessImage();
        $this->objUser = new User();
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
        
        $this->PRODUCT_ORIGINAL_IMAGE_PATH = Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH');
        $this->PRODUCT_THUMBNAIL_IMAGE_PATH = Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH');
        
        $this->BUSINESS_ORIGINAL_IMAGE_PATH = Config::get('constant.BUSINESS_ORIGINAL_IMAGE_PATH');
        $this->BUSINESS_THUMBNAIL_IMAGE_PATH = Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH');
        
        $this->SERVICE_ORIGINAL_IMAGE_PATH = Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH');
        $this->SERVICE_THUMBNAIL_IMAGE_PATH = Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH');
        
        $this->USER_ORIGINAL_IMAGE_PATH = Config::get('constant.USER_ORIGINAL_IMAGE_PATH');
        $this->USER_THUMBNAIL_IMAGE_PATH = Config::get('constant.USER_THUMBNAIL_IMAGE_PATH');
        $this->USER_PROFILE_PIC_WIDTH = Config::get('constant.USER_PROFILE_PIC_WIDTH');
        $this->USER_PROFILE_PIC_HEIGHT = Config::get('constant.USER_PROFILE_PIC_HEIGHT');
        
        $this->categoryLogoOriginalImagePath = Config::get('constant.CATEGORY_LOGO_ORIGINAL_IMAGE_PATH');
        $this->categoryLogoThumbImagePath = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH');
        $this->categoryLogoThumbImageHeight = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_HEIGHT');
        $this->categoryLogoThumbImageWidth = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_WIDTH');
       
        $this->categoryBannerImagePath = Config::get('constant.CATEGORY_BANNER_ORIGINAL_IMAGE_PATH');
        
        $this->OWNER_ORIGINAL_IMAGE_PATH = Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH');
        $this->OWNER_THUMBNAIL_IMAGE_PATH = Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH');
        $this->OWNER_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.OWNER_THUMBNAIL_IMAGE_WIDTH');
        $this->OWNER_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.OWNER_THUMBNAIL_IMAGE_HEIGHT');

        $this->catgoryTempImage = Config::get('constant.CATEGORY_TEMP_PATH');
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('owner-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }
    

    /**
     * Get OwnerInfo
     */
    public function getOwnerInfo(Request $request)
    {
        $outputArray = [];
        $requestData = $request->all();
        $user = JWTAuth::parseToken()->authenticate();
        
        try 
        {
            $getOwnerDetails = Owners::find($requestData['owner_id']);
            if($getOwnerDetails && count($getOwnerDetails) > 0)
            {
                $loginUserId = $user->id;

                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.owner_info_fetched_successfully');
                $statusCode = 200;
                $outputArray['data'] = array();
                $outputArray['data']['id'] = $getOwnerDetails->id;
                $outputArray['data']['full_name'] = $getOwnerDetails->full_name;
               //$outputArray['data']['public_access'] = $getOwnerDetails->public_access;
                

                if((isset($getOwnerDetails->getBusinessData->user_id)) && $loginUserId == $getOwnerDetails->getBusinessData->user_id)
                {
                    $outputArray['data']['email_id'] = $getOwnerDetails->email_id;
                    $outputArray['data']['country_code'] = $getOwnerDetails->country_code;
                    $outputArray['data']['mobile'] = $getOwnerDetails->mobile;
                }
                else
                {
                    $outputArray['data']['email_id'] = '';
                    $outputArray['data']['country_code'] = '';
                    $outputArray['data']['mobile'] = '';
                    if($getOwnerDetails->public_access == 1)
                    {   
                        $outputArray['data']['email_id'] = $getOwnerDetails->email_id;
                        $outputArray['data']['country_code'] = $getOwnerDetails->country_code;
                        $outputArray['data']['mobile'] = $getOwnerDetails->mobile;
                    }   
                }
               
                
                $outputArray['data']['dob'] = $getOwnerDetails->dob;
                $outputArray['data']['age'] = date_diff(date_create($getOwnerDetails->dob), date_create('today'))->y;
                // if(isset($getOwnerDetails->photo) && !empty($getOwnerDetails->photo))
                // {
                //     $ownerImgThumbPath = $this->OWNER_THUMBNAIL_IMAGE_PATH.$getOwnerDetails->photo;
                //     $ownerImgOriginalPath = $this->OWNER_ORIGINAL_IMAGE_PATH.$getOwnerDetails->photo;
                // }
                $ownerThumbImgPath = ((isset($getOwnerDetails->photo) && !empty($getOwnerDetails->photo)) && Storage::size(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$getOwnerDetails->photo) > 0) ? Storage::url(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$getOwnerDetails->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                $ownerOriginalImgPath = ((isset($getOwnerDetails->photo) && !empty($getOwnerDetails->photo)) && Storage::size(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$getOwnerDetails->photo) > 0) ? Storage::url(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$getOwnerDetails->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                $outputArray['data']['photo_thumbnail'] = $ownerThumbImgPath;
                $outputArray['data']['photo_original'] = $ownerOriginalImgPath;
                $outputArray['data']['gender'] = $getOwnerDetails->gender;
                $outputArray['data']['father_name'] = $getOwnerDetails->father_name;
                $outputArray['data']['native_village'] = $getOwnerDetails->native_village;
                $outputArray['data']['maternal_home'] = $getOwnerDetails->maternal_home;
                $outputArray['data']['kul_gotra'] = $getOwnerDetails->kul_gotra;
                $outputArray['data']['designation'] = $getOwnerDetails->designation;
                
                $outputArray['data']['children'] = array();
                if(isset($getOwnerDetails->ownerChildren) && count($getOwnerDetails->ownerChildren) > 0)
                {
                    $i = 0;
                    foreach ($getOwnerDetails->ownerChildren as $ownerChildKey => $ownerChildvalue) 
                    {
                        if(!empty($ownerChildvalue->children_name))
                        {
                            $outputArray['data']['children'][$i]['id'] = $ownerChildvalue->id;
                            $outputArray['data']['children'][$i]['children_name'] = $ownerChildvalue->children_name;
                            $i++;
                        }
                    }
                }
                $outputArray['data']['social_activities'] = array();
                if(isset($getOwnerDetails->ownerSocialActivities) && count($getOwnerDetails->ownerSocialActivities) > 0)
                {
                    $i = 0;
                    foreach ($getOwnerDetails->ownerSocialActivities as $ownerSocialKey => $ownerSocialvalue) 
                    {
                        if(!empty($ownerSocialvalue->activity_title))
                        {
                            $outputArray['data']['social_activities'][$i]['id'] = $ownerSocialvalue->id;
                            $outputArray['data']['social_activities'][$i]['activity_title'] = $ownerSocialvalue->activity_title;
                            $i++;
                        }
                    }
                }

                $outputArray['data']['social_profiles']['facebook_url'] = $getOwnerDetails->facebook_url;
                $outputArray['data']['social_profiles']['twitter_url'] = $getOwnerDetails->twitter_url;
                $outputArray['data']['social_profiles']['linkedin_url'] = $getOwnerDetails->linkedin_url;
                $outputArray['data']['social_profiles']['instagram_url'] = $getOwnerDetails->instagram_url;
               
            }
            else
            {
                $this->log->info('API getOwnerInfo no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = new \stdClass();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getOwnerInfo', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
     * Add Owner
     */
    public function addOwner(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try 
        {
            $validator = Validator::make($request->all(), [
                'full_name' =>  ['required', 'max:100', 'regex:/^[a-zA-Z\ ]+$/'],
                'country_code' => 'required',
                'mobile' => 'required|min:6|max:13',
                'email_id' => 'required|email',
                'business_id' => 'required',
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while addOwner', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                if (Input::file('photo')) 
                {  
                    $photo = Input::file('photo'); 

                    if (!empty($photo) && count($photo) > 0) 
                    {
                        $fileName = 'owner_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                        
                        $pathOriginal = public_path($this->OWNER_ORIGINAL_IMAGE_PATH . $fileName);
                        $pathThumb = public_path($this->OWNER_THUMBNAIL_IMAGE_PATH . $fileName);

                        Image::make($photo->getRealPath())->save($pathOriginal);
                        Image::make($photo->getRealPath())->resize($this->OWNER_THUMBNAIL_IMAGE_WIDTH, $this->OWNER_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);
                        
                        $requestData['photo'] = $fileName;

                        //Uploading on AWS
                        $originalImage = Helpers::addFileToStorage($fileName, $this->OWNER_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                        $thumbImage = Helpers::addFileToStorage($fileName, $this->OWNER_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                        //Deleting Local Files
                        \File::delete($this->OWNER_ORIGINAL_IMAGE_PATH . $fileName);
                        \File::delete($this->OWNER_THUMBNAIL_IMAGE_PATH . $fileName);
                    }
                }

                if(isset($requestData['social_profiles']))
                {
                    $social_profiles = json_decode($requestData['social_profiles']);

                    if(!empty($social_profiles))
                    {
                        $requestData['facebook_url'] = $social_profiles->facebook_url;
                        $requestData['twitter_url'] = $social_profiles->twitter_url;
                        $requestData['linkedin_url'] = $social_profiles->linkedin_url;
                        $requestData['instagram_url'] = $social_profiles->instagram_url;
                    }
                }

                $response = $this->objOwner->insertUpdate($requestData);

                if($response)
                {
                    if(isset($requestData['children']))
                    {
                        $childrenArray = json_decode($requestData['children']);
                        if(!empty($childrenArray))
                        {   
                            foreach($childrenArray as $key=>$children)
                            {
                                $childrenListArray = [];
                                $childrenListArray['id'] = $children->id;
                                $childrenListArray['owner_id'] = $response->id;
                                $childrenListArray['children_name'] = $children->children_name;
                                $this->objOwnerChildren->insertUpdate($childrenListArray);
                            }
                        }
                    }
                    
                    if(isset($requestData['social_activities']))
                    {
                        $activityArray = json_decode($requestData['social_activities']);
                        if(!empty($activityArray))
                        {
                            foreach($activityArray as $key=>$activity)
                            {
                                $activityListArray = [];
                                $activityListArray['id'] = $activity->id;
                                $activityListArray['owner_id'] = $response->id;
                                $activityListArray['activity_title'] = $activity->activity_title;
                                $this->objOwnerSocialActivity->insertUpdate($activityListArray);
                            }
                        } 
                    }
                    
                    $ownerData = $this->objOwner->find($response->id);

                    $listArray = [];

                    $listArray['id'] = $ownerData->id;
                    $listArray['full_name'] = $ownerData->full_name;
                    $listArray['email_id'] = $ownerData->email_id;
                    $listArray['country_code'] = $ownerData->country_code;
                    $listArray['mobile'] = $ownerData->mobile;
                    $listArray['designation'] = $ownerData->designation;
                    $listArray['dob'] = $ownerData->dob;
                    $listArray['age'] = date_diff(date_create($ownerData->dob), date_create('today'))->y;
                    

                    $ownerThumbImgPath = ((isset($ownerData->photo) && !empty($ownerData->photo)) && Storage::size(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$ownerData->photo) > 0) ? Storage::url(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$ownerData->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                    $ownerOriginalImgPath = ((isset($ownerData->photo) && !empty($ownerData->photo)) && Storage::size(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$ownerData->photo) > 0) ? Storage::url(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$ownerData->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                    $listArray['photo_thumbnail'] = $ownerThumbImgPath;
                    $listArray['photo_original'] =  $ownerOriginalImgPath;

                    $listArray['gender'] = $ownerData->gender;
                    $listArray['father_name'] = $ownerData->father_name;
                    $listArray['native_village'] = $ownerData->native_village;
                    $listArray['maternal_home'] = $ownerData->maternal_home;
                    $listArray['kul_gotra'] = $ownerData->kul_gotra;
                    
                    $listArray['children'] = array();
                    if(isset($ownerData->ownerChildren) && count($ownerData->ownerChildren) > 0)
                    {
                        foreach ($ownerData->ownerChildren as $ownerChildKey => $ownerChildvalue) 
                        {
                            if(!empty($ownerChildvalue->children_name))
                            {
                                $listArray['children'][$ownerChildKey]['id'] = $ownerChildvalue->id;
                                $listArray['children'][$ownerChildKey]['children_name'] = $ownerChildvalue->children_name;
                            }
                        }
                    }

                    $listArray['social_activities'] = array();
                    if(isset($ownerData->ownerSocialActivities) && count($ownerData->ownerSocialActivities) > 0)
                    {
                        foreach ($ownerData->ownerSocialActivities as $ownerSocialKey => $ownerSocialvalue) 
                        {
                            if(!empty($ownerSocialvalue->activity_title))
                            {
                                $listArray['social_activities'][$ownerSocialKey]['id'] = $ownerSocialvalue->id;
                                $listArray['social_activities'][$ownerSocialKey]['activity_title'] = $ownerSocialvalue->activity_title;
                            }
                        }
                    }

                    $listArray['social_profiles']['facebook_url'] = $ownerData->facebook_url;
                    $listArray['social_profiles']['twitter_url'] = $ownerData->twitter_url;
                    $listArray['social_profiles']['linkedin_url'] = $ownerData->linkedin_url;
                    $listArray['social_profiles']['instagram_url'] = $ownerData->instagram_url;

                    $this->log->info('API addOwner save successfully', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.owner_added_success');
                    $responseData['data'] = $listArray;
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while addOwner', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $responseData['data'] = [];
                    $statusCode = 200;
                }
            }            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while addOwner', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
     * Edit Owner
     */
    public function editOwner(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try 
        {
            $validator = Validator::make($request->all(), [
                'full_name' =>  ['required', 'max:100', 'regex:/^[a-zA-Z\ ]+$/'],
                'mobile' => 'required|min:6|max:13',
                'email_id' => 'required|email',
                'business_id' => 'required',
                'photo' => 'image|mimes:jpeg,png,jpg|max:5120',
                'id'=> 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while editOwner', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                if (Input::file('photo')) 
                {  
                    $photo = Input::file('photo'); 

                    if (!empty($photo) && count($photo) > 0) 
                    {
                        $fileName = 'owner_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                        
                        $pathOriginal = public_path($this->OWNER_ORIGINAL_IMAGE_PATH . $fileName);
                        $pathThumb = public_path($this->OWNER_THUMBNAIL_IMAGE_PATH . $fileName);

                        Image::make($photo->getRealPath())->save($pathOriginal);
                        Image::make($photo->getRealPath())->resize($this->OWNER_THUMBNAIL_IMAGE_WIDTH, $this->OWNER_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);
                        
                        $requestData['photo'] = $fileName;

                        //Uploading on AWS
                        $originalImage = Helpers::addFileToStorage($fileName, $this->OWNER_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                        $thumbImage = Helpers::addFileToStorage($fileName, $this->OWNER_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                        //Deleting Local Files
                        \File::delete($this->OWNER_ORIGINAL_IMAGE_PATH . $fileName);
                        \File::delete($this->OWNER_THUMBNAIL_IMAGE_PATH . $fileName);
                    }
                }

                if(isset($requestData['social_profiles']))
                {
                    $social_profiles = json_decode($requestData['social_profiles']);

                    if(!empty($social_profiles))
                    {
                        $requestData['facebook_url'] = $social_profiles->facebook_url;
                        $requestData['twitter_url'] = $social_profiles->twitter_url;
                        $requestData['linkedin_url'] = $social_profiles->linkedin_url;
                        $requestData['instagram_url'] = $social_profiles->instagram_url;
                    }
                }   
                
                $response = $this->objOwner->insertUpdate($requestData);

                if($response)
                {
                    if(isset($requestData['children']))
                    {
                        $childrenArray = json_decode($requestData['children']);
                        if(!empty($childrenArray))
                        {   
                            foreach($childrenArray as $key=>$children)
                            {
                                $childrenListArray = [];
                                if($children->operation == 'delete')
                                {
                                    $childrenListArray['id'] = $children->id;
                                    $children = OwnerChildren::find($childrenListArray['id']);
                                    if($children)
                                        $children->delete($childrenListArray['id']);
                                }
                                else
                                {
                                    $childrenListArray['id'] = $children->id;
                                    $childrenListArray['owner_id'] = $requestData['id'];
                                    $childrenListArray['children_name'] = $children->children_name;
                                    $this->objOwnerChildren->insertUpdate($childrenListArray);
                                }
                                
                            }
                        }    
                    }
                    
                    if(isset($requestData['social_activities']))
                    {
                        $activityArray = json_decode($requestData['social_activities']);
                        if(!empty($activityArray))
                        {
                            foreach($activityArray as $key=>$activity)
                            {
                                $activityListArray = [];
                                if($activity->operation == 'delete')
                                {
                                    $activityListArray['id'] = $activity->id;
                                    $activity = OwnerSocialActivity::find($activityListArray['id']);
                                    if($activity)
                                        $activity->delete($activityListArray['id']);
                                    
                                }
                                else
                                {
                                    $activityListArray['id'] = $activity->id;
                                    $activityListArray['owner_id'] = $requestData['id'];
                                    $activityListArray['activity_title'] = $activity->activity_title;
                                    $this->objOwnerSocialActivity->insertUpdate($activityListArray);
                                }
                            }
                        }
                    }
                    
                    $listArray = [];

                    $ownerData = $this->objOwner->find($requestData['id']);
                    $listArray['id'] = $ownerData->id;
                    $listArray['full_name'] = $ownerData->full_name;
                    $listArray['email_id'] = $ownerData->email_id;
                    $listArray['country_code'] = $ownerData->country_code;
                    $listArray['mobile'] = $ownerData->mobile;
                    $listArray['designation'] = $ownerData->designation;
                    $listArray['dob'] = $ownerData->dob;
                    $listArray['age'] = date_diff(date_create($ownerData->dob), date_create('today'))->y;
                    // if(isset($ownerData->photo) && !empty($ownerData->photo))
                    // {
                    //     $ownerThumbImgPath = $this->OWNER_THUMBNAIL_IMAGE_PATH.$ownerData->photo;
                    //     $ownerOriginalImgPath = $this->OWNER_ORIGINAL_IMAGE_PATH.$ownerData->photo;
                    // }

                    $ownerThumbImgPath = ((isset($ownerData->photo) && !empty($ownerData->photo)) && Storage::size(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$ownerData->photo) > 0) ? Storage::url(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$ownerData->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                    $ownerOriginalImgPath = ((isset($ownerData->photo) && !empty($ownerData->photo)) && Storage::size(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$ownerData->photo) > 0) ? Storage::url(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$ownerData->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                    $listArray['photo_thumbnail'] = $ownerThumbImgPath;
                    $listArray['photo_original'] = $ownerOriginalImgPath;

                    $listArray['gender'] = $ownerData->gender;
                    $listArray['father_name'] = $ownerData->father_name;
                    $listArray['native_village'] = $ownerData->native_village;
                    $listArray['maternal_home'] = $ownerData->maternal_home;
                    $listArray['kul_gotra'] = $ownerData->kul_gotra;
                    
                    $listArray['children'] = array();
                    if(isset($ownerData->ownerChildren) && count($ownerData->ownerChildren) > 0)
                    {
                        foreach ($ownerData->ownerChildren as $ownerChildKey => $ownerChildvalue) 
                        {
                            if(!empty($ownerChildvalue->children_name))
                            {
                                $listArray['children'][$ownerChildKey]['id'] = $ownerChildvalue->id;
                                $listArray['children'][$ownerChildKey]['children_name'] = $ownerChildvalue->children_name;
                            }
                        }
                    }

                    $listArray['social_activities'] = array();
                    if(isset($ownerData->ownerSocialActivities) && count($ownerData->ownerSocialActivities) > 0)
                    {   
                        foreach ($ownerData->ownerSocialActivities as $ownerSocialKey => $ownerSocialvalue) 
                        {
                            if(!empty($ownerSocialvalue->activity_title))
                            {
                                $listArray['social_activities'][$ownerSocialKey]['id'] = $ownerSocialvalue->id;
                                $listArray['social_activities'][$ownerSocialKey]['activity_title'] = $ownerSocialvalue->activity_title;
                            }
                        }
                    }
                    
                    $listArray['social_profiles']['facebook_url'] = $ownerData->facebook_url;
                    $listArray['social_profiles']['twitter_url'] = $ownerData->twitter_url;
                    $listArray['social_profiles']['linkedin_url'] = $ownerData->linkedin_url;
                    $listArray['social_profiles']['instagram_url'] = $ownerData->instagram_url;

                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.owner_updated_success');
                    $responseData['data'] = $listArray;
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while editOwner', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $responseData['data'] = [];
                    $statusCode = 200;
                }
            }            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while editOwner', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
     * Get Owners
     */
    public function getOwners(Request $request)
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
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $response = $this->objBusiness->find($requestData['business_id']);

                if($response)
                {
                    $businessOwners = $response->owners;

                    if(count($businessOwners) > 0)
                    {   
                        $mainArray = [];
                        foreach($businessOwners as $owner)
                        {
                            $listArray = [];
                            $listArray['id'] = $owner->id;
                            $listArray['full_name'] = $owner->full_name;
                            $listArray['email_id'] = $owner->email_id;
                            $listArray['country_code'] = $owner->country_code;
                            $listArray['mobile'] = $owner->mobile;
                            $listArray['dob'] = $owner->dob;
                            // if(isset($owner->photo) && !empty($owner->photo))
                            // {
                            //     $ownerThumbImgPath = $this->OWNER_THUMBNAIL_IMAGE_PATH.$owner->photo;
                            //     $ownerOriginalImgPath = $this->OWNER_ORIGINAL_IMAGE_PATH.$owner->photo;
                            // }

                            $ownerThumbImgPath = ((isset($owner->photo) && !empty($owner->photo)) && Storage::size(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$owner->photo) > 0) ? Storage::url(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$owner->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                            $ownerOriginalImgPath = ((isset($owner->photo) && !empty($owner->photo)) && Storage::size(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$owner->photo) > 0) ? Storage::url(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$owner->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                            $listArray['photo_thumbnail'] = $ownerThumbImgPath;
                            $listArray['photo_original'] =  $ownerOriginalImgPath;

                            $listArray['gender'] = $owner->gender;
                            $listArray['father_name'] = $owner->father_name;
                            $listArray['native_village'] = $owner->native_village;
                            $listArray['maternal_home'] = $owner->maternal_home;
                            $listArray['kul_gotra'] = $owner->kul_gotra;
                            
                            $listArray['children'] = array();
                            if(isset($owner->ownerChildren) && count($owner->ownerChildren) > 0)
                            {
                                foreach ($owner->ownerChildren as $ownerChildKey => $ownerChildvalue) 
                                {
                                    if(!empty($ownerChildvalue->children_name))
                                    {
                                        $listArray['children'][$ownerChildKey]['id'] = $ownerChildvalue->id;
                                        $listArray['children'][$ownerChildKey]['children_name'] = $ownerChildvalue->children_name;
                                    }
                                }
                            }

                            $listArray['social_activities'] = array();
                            if(isset($owner->ownerSocialActivities) && count($owner->ownerSocialActivities) > 0)
                            {
                                foreach ($owner->ownerSocialActivities as $ownerSocialKey => $ownerSocialvalue) 
                                {
                                    if(!empty($ownerSocialvalue->activity_title))
                                    {
                                        $listArray['social_activities'][$ownerSocialKey]['id'] = $ownerSocialvalue->id;
                                        $listArray['social_activities'][$ownerSocialKey]['activity_title'] = $ownerSocialvalue->activity_title;
                                    }
                                }
                            }

                            $listArray['social_profiles']['facebook_url'] = $owner->facebook_url;
                            $listArray['social_profiles']['twitter_url'] = $owner->twitter_url;
                            $listArray['social_profiles']['linkedin_url'] = $owner->linkedin_url;
                            $listArray['social_profiles']['instagram_url'] = $owner->instagram_url;

                            $mainArray[] = $listArray;
                        }
                            $responseData['status'] = 1;
                            $responseData['message'] =  trans('apimessages.default_success_msg');
                            $responseData['data'] =  $mainArray;
                            $statusCode = 200;
                    }
                    else
                    {
                        $this->log->info('API getOwners no records found', array('login_user_id' => Auth::id()));
                        $responseData['status'] = 0;
                        $responseData['message'] =  trans('apimessages.norecordsfound');
                        $responseData['data'] =  [];
                        $statusCode = 200;
                    }
                }
                else
                {  
                    $this->log->error('API something went wrong while getOwners', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.invalid_business_id');
                    $responseData['data'] =  [];
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getOwners', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
     * Delete Owner
     */
    public function deleteOwner(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try 
        {
            $validator = Validator::make($request->all(), [
                'owner_id' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while deleteOwner', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $ownerData = $this->objOwner->find($requestData['owner_id']);
                
                if($ownerData)
                {
                    $ownerData->delete();
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.owner_deleted_success');
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while deleteOwner', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.invalid_owner_id');
                    $statusCode = 200;
                }
            }            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while deleteOwner', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
     * Save  Profile Picture
     */
    public function saveProfilePicture(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try 
        {
            $validator = Validator::make($request->all(), [
                'owner_id' => 'required',
                'photo' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while saveProfilePicture', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $ownerId = $requestData['owner_id'];
                $ownerData = $this->objOwner->find($ownerId); 
                if($ownerData)
                {
                    $photo = Input::file('photo');
                    $oldImage = $ownerData->photo;
                    if (!empty($photo) && count($photo) > 0) 
                    {   
                        $fileName = 'owner_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                        $pathOriginal = public_path($this->OWNER_ORIGINAL_IMAGE_PATH . $fileName);
                        $pathThumb = public_path($this->OWNER_THUMBNAIL_IMAGE_PATH . $fileName);
                        $photo->getRealPath(); 

                        Image::make($photo->getRealPath())->save($pathOriginal);
                        Image::make($photo->getRealPath())->resize($this->OWNER_THUMBNAIL_IMAGE_HEIGHT, $this->OWNER_THUMBNAIL_IMAGE_WIDTH)->save($pathThumb);

                        // if profile pic exist then delete
                        
                        if($oldImage != '') 
                        {   
                            $originalImageDelete = Helpers::deleteFileToStorage($oldImage, $this->OWNER_ORIGINAL_IMAGE_PATH, "s3");
                            $thumbImageDelete = Helpers::deleteFileToStorage($oldImage, $this->OWNER_THUMBNAIL_IMAGE_PATH, "s3");
                        }

                        //Uploading on AWS
                        $originalImage = Helpers::addFileToStorage($fileName, $this->OWNER_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                        $thumbImage = Helpers::addFileToStorage($fileName, $this->OWNER_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                        //Deleting Local Files
                        \File::delete($this->OWNER_ORIGINAL_IMAGE_PATH . $fileName);
                        \File::delete($this->OWNER_THUMBNAIL_IMAGE_PATH . $fileName);

                        $response = $this->objOwner->insertUpdate(['id' => $ownerId , 'photo' => $fileName]);

                        if($response)
                        {
                            $ownerData = $this->objOwner->find($ownerId);
                            $listArray = [];

                            $listArray['id'] = $ownerData->id;
                            $listArray['full_name'] = $ownerData->full_name;
                            $listArray['email_id'] = $ownerData->email_id;
                            $listArray['country_code'] = $ownerData->country_code;
                            $listArray['mobile'] = $ownerData->mobile;
                            $listArray['dob'] = $ownerData->dob;
                            // if(isset($ownerData->photo) && !empty($ownerData->photo))
                            // {
                            //     $ownerThumbImgPath = $this->OWNER_THUMBNAIL_IMAGE_PATH.$ownerData->photo;
                            //     $ownerOriginalImgPath = $this->OWNER_ORIGINAL_IMAGE_PATH.$ownerData->photo;
                            // }

                            $ownerImgThumbPath = ((isset($ownerData->photo) && !empty($ownerData->photo)) && Storage::size(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$getOwnerDetails->photo) > 0) ? Storage::url(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$getOwnerDetails->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                            $ownerOriginalImgPath = ((isset($ownerData->photo) && !empty($ownerData->photo)) && Storage::size(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$getOwnerDetails->photo) > 0) ? Storage::url(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$getOwnerDetails->photo) : url(Config::get('constant.DEFAULT_IMAGE'));

                            $listArray['photo_thumbnail'] = $ownerImgThumbPath;
                            $listArray['photo_original'] = $ownerOriginalImgPath;

                            $listArray['gender'] = $ownerData->gender;
                            $listArray['father_name'] = $ownerData->father_name;
                            $listArray['native_village'] = $ownerData->native_village;
                            $listArray['maternal_home'] = $ownerData->maternal_home;
                            $listArray['kul_gotra'] = $ownerData->kul_gotra;
                            
                            $listArray['children'] = array();
                            if(isset($ownerData->ownerChildren) && count($ownerData->ownerChildren) > 0)
                            {
                                foreach ($ownerData->ownerChildren as $ownerChildKey => $ownerChildvalue) 
                                {
                                    if(!empty($ownerChildvalue->children_name))
                                    {
                                        $listArray['children'][$ownerChildKey] = $ownerChildvalue->children_name;
                                    }
                                }
                            }

                            $listArray['social_activities'] = array();
                            if(isset($ownerData->ownerSocialActivities) && count($ownerData->ownerSocialActivities) > 0)
                            {
                                foreach ($ownerData->ownerSocialActivities as $ownerSocialKey => $ownerSocialvalue) 
                                {
                                    if(!empty($ownerSocialvalue->activity_title))
                                    {
                                        $listArray['social_activities'][$ownerSocialKey] = $ownerSocialvalue->activity_title;
                                    }
                                }
                            }

                            $listArray['social_profiles']['facebook_url'] = $ownerData->facebook_url;
                            $listArray['social_profiles']['twitter_url'] = $ownerData->twitter_url;
                            $listArray['social_profiles']['linkedin_url'] = $ownerData->linkedin_url;
                            $listArray['social_profiles']['instagram_url'] = $ownerData->instagram_url;

                            $responseData['status'] = 1;
                            $responseData['message'] =  trans('apimessages.uploaded_successfully');
                            $responseData['data'] =  $listArray;
                            $statusCode = 200;
                        }
                        else
                        {
                            $responseData['status'] = 0;
                            $responseData['message'] = trans('apimessages.default_error_msg');
                            $responseData['data'] =  [];
                            $statusCode = 200;
                        }
                    } 
                }
                else
                {
                    $this->log->error('API something went wrong while saveProfilePicture', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] =  trans('apimessages.invalid_owner_id');
                    $responseData['data'] =  [];
                    $statusCode = 200;
                }
            }            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while saveProfilePicture', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }
}
