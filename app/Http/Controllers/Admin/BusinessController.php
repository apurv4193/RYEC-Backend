<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Requests\BusinessRequest;
use App\Http\Requests\BusinessAddRequest;
use App\Http\Requests\BusinessContactRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\User;
use App\Owners;
use App\UserMetaData;
use App\Business;
use App\AgentUser;
use App\BusinessAddressAttributes;
use App\BusinessImage;
use App\BusinessWorkingHours;
use App\BusinessActivities;
use App\UserRole;
use App\Category;
use App\Metatag;
use App\NotificationList;
use App\Http\Controllers\Controller;
use Crypt;
use Image;
use File;
use DB;
use Cache;
use Helpers;
use Illuminate\Contracts\Encryption\DecryptException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Cviebrock\EloquentSluggable\Services\SlugService;

class BusinessController extends Controller
{                   
    public function __construct()
    {
        $this->middleware('auth');
        $this->objUser = new User();
        $this->objOwners = new Owners();
        $this->objUserMetaData = new UserMetaData();
        $this->objUserRole = new UserRole();
        $this->objCategory = new Category();
        $this->objBusiness = new Business();
        $this->objBusinessWorkingHours = new BusinessWorkingHours();
        $this->objBusinessActivities = new BusinessActivities();
        $this->objBusinessAddressAttributes = new BusinessAddressAttributes();
        $this->objBusinessImage = new BusinessImage();
        $this->BUSINESS_ORIGINAL_IMAGE_PATH = Config::get('constant.BUSINESS_ORIGINAL_IMAGE_PATH');
        $this->BUSINESS_THUMBNAIL_IMAGE_PATH = Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH');
        $this->BUSINESS_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_WIDTH');
        $this->BUSINESS_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_HEIGHT');
        
        $this->USER_ORIGINAL_IMAGE_PATH = Config::get('constant.USER_ORIGINAL_IMAGE_PATH');
        $this->USER_THUMBNAIL_IMAGE_PATH = Config::get('constant.USER_THUMBNAIL_IMAGE_PATH');
        $this->USER_PROFILE_PIC_WIDTH = Config::get('constant.USER_PROFILE_PIC_WIDTH');
        $this->USER_PROFILE_PIC_HEIGHT = Config::get('constant.USER_PROFILE_PIC_HEIGHT');
        
        $this->OWNER_ORIGINAL_IMAGE_PATH = Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH');
        $this->OWNER_THUMBNAIL_IMAGE_PATH = Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH');
        $this->OWNER_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.OWNER_THUMBNAIL_IMAGE_WIDTH');
        $this->OWNER_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.OWNER_THUMBNAIL_IMAGE_HEIGHT');
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('business-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
        
        $this->catgoryTempImage = Config::get('constant.CATEGORY_TEMP_PATH');        
    }

    public function index($userId)
    {   
        try 
        {
            $userId = Crypt::decrypt($userId);
            $userDetail = User::find($userId); 
            $this->log->info('Admin business listing page', array('admin_user_id' => Auth::id(), 'user_id' => $userId));
            return view('Admin.ListBusiness', compact('businessList','userId','userDetail'));
        } catch (DecryptException $e){
            $this->log->error('Admin something went wrong while business listing page', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId, 'error' => $e->getMessage()));
            return view('errors.404');
        }        
    }

    public function add($userId)
    {
        try 
        {
            $userId = Crypt::decrypt($userId);
            $userDetail = User::find($userId);
            $parentCategories = $this->objCategory->getAll(array('parent' => '0'));
            
            $this->log->info('Admin business add page', array('admin_user_id' => Auth::id(), 'user_id' => $userId));
            
            return view('Admin.AddBusiness', compact('userId', 'parentCategories', 'userDetail'));
        } catch (DecryptException $e){
            $this->log->error('Admin something went wrong while business add page', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId, 'error' => $e->getMessage()));
            return view('errors.404');
        }        
    }

    public function edit($id)
    {
        try 
        {           
            $id = Crypt::decrypt($id);
            $data = $this->objBusiness->find($id);
            
            $businessImages = (isset($data->businessImages) && !empty($data->businessImages)) ? $data->businessImages->toArray() : [];
            
            $userId = $data->user_id;
            $userDetail = User::find($userId);
            $parentCategories = $this->objCategory->getAll(array('parent' => '0'));
            $agentUsers = AgentUser::get();

            if($data) 
            {
                if(Auth::user()->agent_approved == 0)
                {
                    $this->log->info('Admin business edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId, 'business_id' => $id));
                    return view('Admin.EditBusiness', compact('userId','parentCategories','data','businessImages','userDetail','businessWorkingHoursData','agentUsers'));
                }
                elseif (Auth::user()->agent_approved == 1) {
                    if($data->agent_user == Auth::id())
                    {
                        $this->log->info('Admin business edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId, 'business_id' => $id));
                        return view('Admin.EditBusiness', compact('userId','parentCategories','data','businessImages','userDetail','businessWorkingHoursData','agentUsers'));
                    }
                    else
                    {
                       $this->log->error('Admin something went wrong while business edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId, 'business_id' => $id, 'error' => 'not allow to open business'));
                        return view('errors.404');
                    }
                }
            } 
            else 
            {
                $this->log->error('Admin something went wrong while business edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId, 'business_id' => $id));
                return Redirect::to("admin/users")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e){
            $this->log->error('Admin something went wrong while business edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId, 'business_id' => $id, 'error' => $e->getMessage()));
            return view('errors.404');
        }
    }

    public function save(BusinessAddRequest $request)
    {

        $postData = Input::get();

        $this->validate($request,[
                    'latitude'=>'required',
                    'longitude' => 'required',
                    'category_id' => 'required'
                ],
                [
                    'latitude.required'=>'Please enter proper address to get proper latitude',
                    'longitude.required' => 'Please enter proper address to get proper longitude',
                    'category_id.required' => 'Please select category'
                ]);
        
        if($postData['id'] == 0 || !isset($postData['id']))
        {
            $businessSlug = SlugService::createSlug(Business::class, 'business_slug', $postData['name']);
            $postData['business_slug'] = (isset($businessSlug) && !empty($businessSlug)) ? $businessSlug : NULL;
        } 
        
        if(isset($postData['category_id']) && !empty($postData['category_id']))
        {
            
            $postData['category_id'] = array_filter(array_unique($postData['category_id']));

            $postData['category_id'] = implode(',',$postData['category_id']);
            
            $postData['category_hierarchy'] = Helpers::getCategoryHierarchy($postData['category_id']);

            $postData['parent_category'] = Helpers::getParentCategoryIds($postData['category_id']);
        }

        $promoted =  (!isset($postData['promoted'])) ? 0 : 1;
        $postData['promoted'] = $promoted;
        if(isset($postData['metatags']))
        {
            $explodeTags = explode(',',$postData['metatags']);
            if(count($explodeTags) > 0){
                foreach($explodeTags as $tag)
                {
                    Metatag::firstOrCreate(array('tag' => $tag));
                }
            }
        }
        
        if (Input::file('business_logo')) 
        {  
            $logo = Input::file('business_logo'); 

            if (!empty($logo) && count($logo) > 0) 
            {
                $fileName = 'business_logo_' . uniqid() . '.' . $logo->getClientOriginalExtension();

                $pathOriginal = public_path($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                $pathThumb = public_path($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);

                Image::make($logo->getRealPath())->save($pathOriginal);
                Image::make($logo->getRealPath())->resize($this->BUSINESS_THUMBNAIL_IMAGE_WIDTH, $this->BUSINESS_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);
                
                //Uploading on AWS
                $originalImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                $thumbImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                //Deleting Local Files
                \File::delete($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                \File::delete($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);

                $postData['business_logo'] = $fileName;
            }
        }

        if(Auth::user()->agent_approved == 1)
        {
            $postData['agent_user'] = Auth::id();
        }
        $response = $this->objBusiness->insertUpdate($postData);
        
        if($postData['id'] == 0 && $response)
        {
            $userData = User::find($postData['user_id']); 
            if($userData)
            {
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
                 $ownerSave = $this->objOwners->insertUpdate($ownerInsert);
            }
        }
        
        Cache::forget('membersForApproval');
        Cache::forget('businessesData');

        $businessId = ($postData['id'] == 0) ? $response->id : $postData['id'];

        //Store business Attributes
        if($postData['latitude'] != $postData['hidden_latitude'] && $postData['longitude'] != $postData['hidden_longitude']) {
            $business_address_attributes = Helpers::getAddressAttributes($postData['latitude'], $postData['longitude']);
            $business_address_attributes['business_id'] = $businessId;
            $businessObject = Business::find($businessId);
            if (!$businessObject->business_address) {
                $businessObject->business_address()->create($business_address_attributes);
            } else {
                $businessObject->business_address()->update($business_address_attributes);
            }
        }


        if(isset($postData['id']) && $postData['id'] == 0)
        {
            $this->validate($request,
                ['business_images' => 'required',
                'business_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'],
                ['business_images.*.max' => 'File size must be less than 5 MB']
            );
        }
        else
        {
            $this->validate($request,
                ['business_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'],
                ['business_images.*.max' => 'File size must be less than 5 MB']
            );
        }

        if (Input::file()) 
        {  
            $business_images = Input::file('business_images');

            $imageArray = [];

            if (!empty($business_images) && count($business_images) > 0) 
            {
                foreach($business_images as $business_image)
                {
                    $fileName = 'business_' . uniqid() . '.' . $business_image->getClientOriginalExtension();
                    $pathOriginal = public_path($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                    $pathThumb = public_path($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);

                    Image::make($business_image->getRealPath())->save($pathOriginal);
                    Image::make($business_image->getRealPath())->resize(100, 100)->save($pathThumb);
                    
                    //Uploading on AWS
                    $originalImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                    $thumbImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                    //Deleting Local Files
                    \File::delete($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                    \File::delete($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);
                    
                    $this->log->info('Admin business image original and thumb image deleted successfully', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $businessId, 'imageName' => $fileName));
                    
                    BusinessImage::firstOrCreate(['business_id' => $businessId , 'image_name' => $fileName]);

                }
            }
        }

        if ($response) 
        {
            $this->log->info('Admin business added/updated successfully', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $businessId));
            //insert/update working hours
            $businessWorkingHoursData = Helpers::setWorkingHours($postData);
            $businessWorkingHoursData['business_id'] = $businessId;
            $businessWorkingHoursData['id'] = $postData['working_hours_id'];
            $businessWorkingHoursData['timezone'] = $postData['timezone'];

            $this->objBusinessWorkingHours->insertUpdate($businessWorkingHoursData);
            
            //insert/update activities
            
            if(isset($postData['add_activity_title']) && !empty($postData['add_activity_title']))
            {
                foreach(array_filter($postData['add_activity_title']) as $key=>$activity)
                {
                    $activityArray = [];
                    $activityArray['business_id'] = $businessId;
                    $activityArray['activity_title'] = $activity;
                    $this->objBusinessActivities->insertUpdate($activityArray);
                }
            }

            if(isset($postData['deleted_activities']) && !empty($postData['deleted_activities']))
            {
                foreach($postData['deleted_activities'] as $activity)
                {
                    $data = $this->objBusinessActivities->find($activity);
                    $data->delete();
                }
            }

            if(isset($postData['update_activity_title']) && !empty($postData['update_activity_title']))
            {
                foreach($postData['update_activity_title'] as $key=>$activity)
                {
                    $activityArray = [];
                    $activityArray['business_id'] = $businessId;
                    $activityArray['activity_title'] = $activity;
                    $activityArray['id'] = $postData['update_activity_id'][$key];
                    $this->objBusinessActivities->insertUpdate($activityArray);
                }
            }
            
            return Redirect::to("admin/user/business/".Crypt::encrypt($postData['user_id']))->with('success', trans('labels.businesssuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while adding/updating business', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId, 'business_id' => $businessId));
            return Redirect::to("admin/user/business/".Crypt::encrypt($postData['user_id']))->with('error', trans('labels.businesserrormsg'));
        }
    }

    public function saveContactInfo(BusinessContactRequest $request)
    {
        $postData = Input::get();

        $this->validate($request,[
                    'latitude'=>'required',
                    'longitude' => 'required'
                ],
                [
                    'latitude.required'=>'Please enter proper address to get proper latitude',
                    'longitude.required' => 'Please enter proper address to get proper longitude'
                ]);

        $response = $this->objBusiness->insertUpdate($postData);

        $businessId = ($postData['id'] == 0) ? $response->id : $postData['id'];

        //Store business Attributes
        if($postData['latitude'] != $postData['hidden_latitude'] && $postData['longitude'] != $postData['hidden_longitude']) 
        {
            $geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng=' . $postData['latitude'] . "," . $postData['longitude'] . '&sensor=false&libraries=places');
            $sample_array = ["premise", "street_number", "route", "neighborhood", "sublocality_level_3", "sublocality_level_2", "sublocality_level_1", "locality", "administrative_area_level_2", "administrative_area_level_1", "country", "postal_code"];
            $output = json_decode($geocode);
            $premise = $sublocality_level_1 = $locality = $administrative_area_level_1 = $route = $street_number = $sublocality_level_2 = $sublocality_level_3 = $administrative_area_level_2 = $country = $postal_code = $neighborhood = $address = '';
            $business_address_attributes = [];
            if (!empty($output->results)) {
                for ($j = 0; $j < count($output->results[0]->address_components); $j++) {
                    for ($i = 0; $i < count($sample_array); $i++) {
                        if ($sample_array[$i] == $output->results[0]->address_components[$j]->types[0]) {
                            $set = $sample_array[$i];
                            //Getting value from associative variable premise, country etc all attribute using $$set
                            $$set = $output->results[0]->address_components[$j]->long_name;
                            $business_address_attributes[$set] = $output->results[0]->address_components[$j]->long_name;
                        }
                    }

                }
            }

            $business_address_attributes['business_id'] = $businessId;
            $businessObject = Business::find($businessId);
            if (!$businessObject->business_address) {
                $businessObject->business_address()->create($business_address_attributes);
            } else {
                $businessObject->business_address()->update($business_address_attributes);
            }
        }
        
        Cache::forget('membersForApproval');
        Cache::forget('businessesData');

        if ($response) 
        {
            $this->log->info('Admin business contact info save successfully', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $businessId));
            return Redirect::to("admin/user/business/edit/".Crypt::encrypt($postData['id']))->with('success', trans('labels.contactdetailsuccessmsg'));
        } 
        else 
        {
            $this->log->error('Admin something went wrong while save business contact info', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $businessId));
            return Redirect::to("admin/user/business/".Crypt::encrypt($postData['user_id']))->with('error', trans('labels.businesserrormsg'));
        }
    }

    public function saveWorkingHours()
    {
        $postData = Input::get();

         //insert/update working hours
        $businessWorkingHoursData = Helpers::setWorkingHours($postData);
        $businessWorkingHoursData['business_id'] = $postData['id'];
        $businessWorkingHoursData['id'] = $postData['working_hours_id'];
        $businessWorkingHoursData['timezone'] = $postData['timezone'];

        $response = $this->objBusinessWorkingHours->insertUpdate($businessWorkingHoursData);

        Cache::forget('membersForApproval');
        Cache::forget('businessesData');

        if ($response) 
        {
            $this->log->info('Admin business working hours save successfully', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $postData['id']));
            return Redirect::to("admin/user/business/edit/".Crypt::encrypt($postData['id']))->with('success', trans('labels.workinghourssuccessmsg'));
        } 
        else 
        {
            $this->log->error('Admin something went wrong while save business working hours', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $postData['id']));
            return Redirect::to("admin/user/business/".Crypt::encrypt($postData['user_id']))->with('error', trans('labels.businesserrormsg'));
        }
    }

    public function saveSocialProfiles()
    {
        $postData = Input::get();

        $response = $this->objBusiness->insertUpdate($postData);

        Cache::forget('membersForApproval');
        Cache::forget('businessesData');

        if ($response) 
        {
            $this->log->info('Admin business social profiles save successfully', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $postData['id']));
            return Redirect::to("admin/user/business/edit/".Crypt::encrypt($postData['id']))->with('success', trans('labels.socialprofilesuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while save business social profiles', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $businessId));
            return Redirect::to("admin/user/business/".Crypt::encrypt($postData['user_id']))->with('error', trans('labels.businesserrormsg'));
        }

    }

    public function saveBusinessInfo(BusinessRequest $request)
    {
        $postData = Input::get();
        if(isset($postData['id']) && $postData['id'] == 0)
        {
            $this->validate($request,
                ['business_images' => 'required',
                'business_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'],
                ['business_images.*.max' => 'File size must be less than 5 MB']
            );
        }
        else
        {
            $this->validate($request,
                ['business_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'],
                ['business_images.*.max' => 'File size must be less than 5 MB']
            );
        }

        $promoted =  (!isset($postData['promoted'])) ? 0 : 1;
        $postData['promoted'] = $promoted;

        if (Input::file('business_logo')) 
        {  
            $logo = Input::file('business_logo'); 

            if (!empty($logo) && count($logo) > 0) 
            {
                $fileName = 'business_logo_' . uniqid() . '.' . $logo->getClientOriginalExtension();

                $pathOriginal = public_path($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                $pathThumb = public_path($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);

                Image::make($logo->getRealPath())->save($pathOriginal);
                Image::make($logo->getRealPath())->resize($this->BUSINESS_THUMBNAIL_IMAGE_WIDTH, $this->BUSINESS_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);
                
                if(isset($postData['old_business_logo']) && $postData['old_business_logo'] != '')
                {
                    $originalImageDelete = Helpers::deleteFileToStorage($postData['old_business_logo'], $this->BUSINESS_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($postData['old_business_logo'], $this->BUSINESS_THUMBNAIL_IMAGE_PATH, "s3");
                }
                //Uploading on AWS
                $originalImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                $thumbImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                //Deleting Local Files
                \File::delete($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                \File::delete($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);

                $postData['business_logo'] = $fileName;
            }
        }

        $response = $this->objBusiness->insertUpdate($postData);

        Cache::forget('membersForApproval');
        Cache::forget('businessesData');

        $businessId = ($postData['id'] == 0) ? $response->id : $postData['id'];

        if($postData['membership_type'] == 1 && $postData['membership_type'] != $postData['old_membership_type']) {
            //Send push notification to Business User & Agent
            $businessDetail = Business::find($businessId);
            $notificationData = [];
            $notificationData['title'] = 'Membership Upgrade';
            $notificationData['message'] = 'Dear '.$businessDetail->user->name.',   Congratulations! Your membership has been upgraded to Premium. You will now be able to utilize all premium features.';
            $notificationData['type'] = '9';
            $notificationData['business_id'] = $businessDetail->id;
            $notificationData['business_name'] = $businessDetail->name;
            Helpers::sendPushNotification($businessDetail->user_id, $notificationData);

            // for pushnotification list
            $notificationListArray = [];
            $notificationListArray['user_id'] = $businessDetail->user_id;
            $notificationListArray['business_id'] = $businessDetail->id;
            $notificationListArray['title'] = 'Membership Upgrade';
            $notificationListArray['message'] =  'Dear '.$businessDetail->user->name.',   Congratulations! Your membership has been upgraded to Premium. You will now be able to utilize all premium features.';
            $notificationListArray['type'] = '9';
            $notificationListArray['business_name'] = $businessDetail->name;
            $notificationListArray['user_name'] = $businessDetail->user->name;

            NotificationList::create($notificationListArray);

            if($businessDetail->user_id != $businessDetail->created_by) {
                if($businessDetail->created_by != '' && $businessDetail->created_by > 1)
                {
                    $notificationData['message'] = 'Dear '.$businessDetail->businessCreatedBy->name.',   Congratulations! Your Customer\'s membership has been upgraded to Premium. Your customer will now be able to utilize all premium features.';
                    Helpers::sendPushNotification($businessDetail->created_by, $notificationData);

                    $notificationListArray = [];
                    $notificationListArray['user_id'] = $businessDetail->created_by;
                    $notificationListArray['business_id'] = $businessDetail->id;
                    $notificationListArray['title'] = 'Membership Upgrade';
                    $notificationListArray['message'] =  'Dear '.$businessDetail->businessCreatedBy->name.',   Congratulations! Your Customer\'s membership has been upgraded to Premium. Your customer will now be able to utilize all premium features.';
                    $notificationListArray['type'] = '9';
                    $notificationListArray['business_name'] = $businessDetail->name;
                    $notificationListArray['user_name'] = $businessDetail->businessCreatedBy->name;

                    NotificationList::create($notificationListArray);
                }
                  
            }   
        }

        if (Input::file()) 
        {  
            $business_images = Input::file('business_images');

            $imageArray = [];

            if (!empty($business_images) && count($business_images) > 0) 
            {
                foreach($business_images as $business_image)
                {
                    $fileName = 'business_' . uniqid() . '.' . $business_image->getClientOriginalExtension();
                    $pathOriginal = public_path($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                    $pathThumb = public_path($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);

                    Image::make($business_image->getRealPath())->save($pathOriginal);
                    Image::make($business_image->getRealPath())->resize(100, 100)->save($pathThumb);

                    //Uploading on AWS
                    $originalImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                    $thumbImage = Helpers::addFileToStorage($fileName, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                    //Deleting Local Files
                    \File::delete($this->BUSINESS_ORIGINAL_IMAGE_PATH . $fileName);
                    \File::delete($this->BUSINESS_THUMBNAIL_IMAGE_PATH . $fileName);
                    
                    BusinessImage::firstOrCreate(['business_id' => $businessId , 'image_name' => $fileName]);

                }
            }
        }

        if ($response) 
        {
            $this->log->info('Admin business info save successfully', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $businessId));
            return Redirect::to("admin/user/business/edit/".Crypt::encrypt($postData['id']))->with('success', trans('labels.businesssuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while save business info', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $businessId));
            return Redirect::to("admin/user/business/".Crypt::encrypt($postData['user_id']))->with('error', trans('labels.businesserrormsg'));
        }
    }

    public function saveSocialActivities()
    {
        $postData = Input::get();
        
//      insert/update activities
            
        if(isset($postData['add_activity_title']) && !empty($postData['add_activity_title']))
        {
            foreach(array_filter($postData['add_activity_title']) as $key=>$activity)
            {
                $activityArray = [];
                $activityArray['business_id'] = $postData['id'];
                $activityArray['activity_title'] = $activity;
                $this->objBusinessActivities->insertUpdate($activityArray);
            }
        }

        if(isset($postData['deleted_activities']) && !empty($postData['deleted_activities']))
        {
            foreach($postData['deleted_activities'] as $activity)
            {
                $data = $this->objBusinessActivities->find($activity);
                $data->delete();
            }
        }

        if(isset($postData['update_activity_title']) && !empty($postData['update_activity_title']))
        {
            foreach($postData['update_activity_title'] as $key=>$activity)
            {
                $activityArray = [];
                $activityArray['business_id'] = $postData['id'];
                $activityArray['activity_title'] = $activity;
                $activityArray['id'] = $postData['update_activity_id'][$key];
                $this->objBusinessActivities->insertUpdate($activityArray);
            }
        }

        Cache::forget('membersForApproval');
        Cache::forget('businessesData');

        return Redirect::to("admin/user/business/edit/".Crypt::encrypt($postData['id']))->with('success', trans('labels.socialactivitiessuccessmsg'));
    }

    public function saveCategryHierarchy()
    {
        $postData = Input::get();

        if(isset($postData['category_id']) && !empty($postData['category_id']))
        {

            $postData['category_id'] = array_filter(array_unique($postData['category_id']));

            $postData['category_id'] = implode(',',$postData['category_id']);
            
            $postData['category_hierarchy'] = Helpers::getCategoryHierarchy($postData['category_id']);

            $postData['parent_category'] = Helpers::getParentCategoryIds($postData['category_id']);

        }

        if(isset($postData['metatags']))
        {
            $explodeTags = explode(',',$postData['metatags']);
            if(count($explodeTags) > 0){
                foreach($explodeTags as $tag)
                {
                    Metatag::firstOrCreate(array('tag' => $tag));
                }
            }
        }
        
        $response = $this->objBusiness->insertUpdate($postData);

        Cache::forget('membersForApproval');
        Cache::forget('businessesData');

        if ($response) {
            $this->log->info('Admin business categry hierarchy save', array('admin_user_id' => Auth::id(), 'user_id' => $postData['user_id']));
            return Redirect::to("admin/user/business/edit/".Crypt::encrypt($postData['id']))->with('success', trans('labels.categorysuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while save business categry hierarchy', array('admin_user_id' =>  Auth::id(), 'user_id' => $postData['user_id'], 'business_id' => $postData['id']));
            return Redirect::to("admin/user/business/".Crypt::encrypt($postData['user_id']))->with('error', trans('labels.businesserrormsg'));
        }
    }

    public function delete($id)
    {
        $id = Crypt::decrypt($id);
        $data = $this->objBusiness->find($id);
        $businessImageData = $this->objBusinessImage->getBusinessImagesByBusinessId($id)->toArray();
        
        $response = $data->delete();

        if ($response) 
        {
            if(!empty($businessImageData))
            { 
                foreach ($businessImageData as $businessImage) 
                {
                    $originalImageDelete = Helpers::deleteFileToStorage($businessImage['image_name'], $this->BUSINESS_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($businessImage['image_name'], $this->BUSINESS_THUMBNAIL_IMAGE_PATH, "s3");
                }
            }
            $this->log->info('Admin business delete', array('admin_user_id' => Auth::id(), 'user_id' => $data->user_id, 'business_id' => $id));
            return Redirect::to("admin/user/business/".Crypt::encrypt($data->user_id))->with('success', trans('labels.businessdeletesuccessmsg'));
        }
    }

    public function addCategotyHierarchy()
    {
        $catId = Input::get('catId');
        $categoryHierarchy = array_reverse(Helpers::getCategoryReverseHierarchy($catId));
        return view('Admin.BusinessCategorySelectionTemplate', compact('catId','categoryHierarchy'));
    }

    public function getSubCategotyById()
    {
        $categoryIds = Input::get('categoryIds');
        $level = Input::get('level');
        $categoryArray = [];
        $categoryHierarchy = [];
       
        
        if(!empty(array_filter($categoryIds))) {

            $categoryArray = $this->objCategory->getAll(['parentIn' => $categoryIds]);
            
            if(isset($categoryIds[0]) && $categoryIds[0] != ''){
                $categoryHierarchy = array_reverse(Helpers::getCategoryReverseHierarchy($categoryIds[0]));
            }
        } 
        
        if(isset($categoryArray) && count($categoryArray) > 0){            
            return view('Admin.CategoriesTemplate', compact('categoryArray', 'level','categoryHierarchy'));
        }
    }
    public function getBusinessMetaTags()
    {
        $categoryId = Input::get('categoryId');
        
        $metatags = Category::find($categoryId)->metatags;

       if($metatags != '')
       {
            return explode(',', $metatags);
       }
       else
       {
            return [];
       }
       
    }

    public function setStatusBusinessApproved()
    {
        $businessId = Input::get('businessId');
        $postData['id'] = $businessId;
        $postData['approved'] = 1;
        $this->objBusiness->insertUpdate($postData);

        //Send push notification to Business User
        $businessDetail = Business::find($businessId);
        $notificationData = [];
        $notificationData['title'] = 'Business Approved';
        $notificationData['message'] = 'Dear '.$businessDetail->user->name.',  Good News! Your Business Profile just got approved';
        $notificationData['type'] = '8';
        $notificationData['business_id'] = $businessDetail->id;
        $notificationData['business_name'] = $businessDetail->name;
        Helpers::sendPushNotification($businessDetail->user_id, $notificationData);

        // notification list 
        $notificationListArray = [];
        $notificationListArray['user_id'] = $businessDetail->user_id;
        $notificationListArray['business_id'] = $businessDetail->id;
        $notificationListArray['title'] = 'Business Approved';
        $notificationListArray['message'] =  'Dear '.$businessDetail->user->name.',  Good News! Your Business Profile just got approved';
        $notificationListArray['type'] = '8';
        $notificationListArray['business_name'] = $businessDetail->name;
        $notificationListArray['user_name'] = $businessDetail->user->name;

        NotificationList::create($notificationListArray);

        if($businessDetail->user_id != $businessDetail->created_by) {
            $notificationData['message'] = 'Dear '.$businessDetail->businessCreatedBy->name.',  Good News! Your Customer\'s Business Profile just got approved';
            Helpers::sendPushNotification($businessDetail->created_by, $notificationData); 

            // notification list 
            $notificationListArray = [];
            $notificationListArray['user_id'] = $businessDetail->created_by;
            $notificationListArray['business_id'] = $businessDetail->id;
            $notificationListArray['title'] = 'Business Approved';
            $notificationListArray['message'] =  'Dear '.$businessDetail->businessCreatedBy->name.',  Good News! Your Customer\'s Business Profile just got approved';
            $notificationListArray['type'] = '8';
            $notificationListArray['business_name'] = $businessDetail->name;
            $notificationListArray['user_name'] = $businessDetail->businessCreatedBy->name;

            NotificationList::create($notificationListArray);   
        }

        Cache::forget('membersForApproval');
        return 1;
    }

    public function businessRejected()
    {
        $businessId = Input::get('businessId');
        $data = $this->objBusiness->find($businessId);
        $response = $data->delete();

        Cache::forget('membersForApproval');
        return 1;
    }
    public function removeBusinessImage()
    {
        $businessImageId = Input::get('businessImageId');
        $data = $this->objBusinessImage->find($businessImageId);
        if($data)
        {
            $response = $data->delete();
            $originalImageDelete = Helpers::deleteFileToStorage($data->image_name, $this->BUSINESS_ORIGINAL_IMAGE_PATH, "s3");
            $thumbImageDelete = Helpers::deleteFileToStorage($data->image_name, $this->BUSINESS_THUMBNAIL_IMAGE_PATH, "s3");
        }
        return 1;
    }

    public function getAllPromotedBusinesses()
    {
       if (Cache::has('businessesData')){
            $businessesData = Cache::get('businessesData');
        } else {
            $businessesData = $this->objBusiness->getAll(['approved' => 1,'promoted' => 1]);
            Cache::put('businessesData', $businessesData, 60);
        }
       return view('Admin.ListPromotedBusinesses', compact('businessesData'));
    }
    
    public function updatePromotedBusinesses(Request $request) 
    {
        if(isset($request->businessId) && isset($request->promotedBusiness))
        {
            $response = Business::where('id', $request->businessId)->update(['promoted' => $request->promotedBusiness]);
            Cache::forget('businessesData');
            echo ($response) ? 1 : 0;
        }
        else
        {
            $response = '';
            echo 0;
        }
//      return $response;
    }

    public function getAllBusinesses()
    {   
        $postData = Input::get();

        if(Auth::user()->agent_approved == 1)
        {
           // $businessList = Business::has('user')->where('agent_user', Auth::id())->get();
           if((isset($postData['searchText']) && $postData['searchText'] != '') || (isset($postData['type']) && $postData['type'] != ''))
            {   
                $businessList = $this->objBusiness->businessFilter($postData,$pagination=true);
            }
            else
            {
                $businessList = $this->objBusiness->getAll(['agent_user' => Auth::id()],true);
            }
        }
        else
        {
            $filters = [];
            if((isset($postData['fieldtype']) && $postData['fieldtype'] != '') || (isset($postData['fieldcheck']) && $postData['fieldcheck'] != ''))
            {
                if($postData['fieldtype'] == 'city')
                    $filters['fieldtype'] = $postData['fieldtype'];
                if($postData['fieldtype'] == 'category_id')
                    $filters['fieldtype'] = $postData['fieldtype'];

                if($postData['fieldcheck'] != '')
                    $filters['isNull'] = $postData['fieldcheck'];

                $businessList = $this->objBusiness->businessFilter($filters,$pagination=false,$search=true);
            }
            else
            {
                $businessList = Business::has('user')->get();
            }
        }
         
        $this->log->info('Admin business listing page', array('admin_user_id' => Auth::id()));
        return view('Admin.ListAllBusiness', compact('businessList','postData'));   
    }

    public function getAllBusinessesByCategory($id)
    {
        $categoryId = Crypt::decrypt($id);
        $businessList = Helpers::categoryHasBusinesses($categoryId);
        $this->log->info('Admin business listing page', array('admin_user_id' => Auth::id()));
        return view('Admin.ListAllBusiness', compact('businessList'));   
    }

    public function getPremiumBusinesses()
    {   
        $businessList = Business::has('user')->where('membership_type','<>',0)->get(); 
        $this->log->info('Admin business listing page', array('admin_user_id' => Auth::id()));
        return view('Admin.ListPremiumBusiness', compact('businessList'));   
    }

}
