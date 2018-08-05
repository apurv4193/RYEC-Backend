<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\OwnerRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\Owners;
use App\OwnerChildren;
use App\OwnerSocialActivity;
use App\Business;
use App\UserRole;
use App\Http\Controllers\Controller;
use Crypt;
use Image;
use File;
use Helpers;
use Illuminate\Contracts\Encryption\DecryptException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class OwnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objOwner = new Owners();
        $this->objOwnerChildren = new OwnerChildren();
        $this->objOwnerSocialActivity = new OwnerSocialActivity();
        $this->OWNER_ORIGINAL_IMAGE_PATH = Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH');
        $this->OWNER_THUMBNAIL_IMAGE_PATH = Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH');
        $this->OWNER_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.OWNER_THUMBNAIL_IMAGE_WIDTH');
        $this->OWNER_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.OWNER_THUMBNAIL_IMAGE_HEIGHT');
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('owner-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
       
    }

    public function index($businessId)
    {   
        try 
        {
            $businessId = Crypt::decrypt($businessId);
            $businessDetails = Business::find($businessId);
            $this->log->info('Admin owner listing page', array('admin_user_id' =>  Auth::id(),'business_id' => $businessId));
            return view('Admin.ListOwners', compact('businessDetails','businessId'));
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while owner listing page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function add($businessId)
    {
        try 
        {
            $businessId = Crypt::decrypt($businessId);
            $businessDetails = Business::find($businessId);
            $this->log->info('Admin owner add page', array('admin_user_id' =>  Auth::id(),'business_id' => $businessId));
            return view('Admin.EditOwner',compact('businessId','businessDetails'));
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while owner add page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = $this->objOwner->find($id);
            $businessId = $data->business_id;
            $businessDetails = Business::find($businessId);

            if($data) {
                $this->log->info('Admin owner edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'owner_id' => $id));
                return view('Admin.EditOwner', compact('businessId','data','businessDetails'));
            } else {
                return Redirect::to("admin/user/business/owner/".Crypt::encrypt($businessId))->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while owner edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'owner_id' => $id, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function save(OwnerRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);
        
        // upload owner profile picture
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
                
                $postData['photo'] = $fileName;

                if(isset($postData['old_photo']) && $postData['old_photo'] != '')
                {
                    $originalImageDelete = Helpers::deleteFileToStorage($postData['old_photo'], $this->OWNER_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($postData['old_photo'], $this->OWNER_THUMBNAIL_IMAGE_PATH, "s3");
                }

                //Uploading on AWS
                $originalImage = Helpers::addFileToStorage($fileName, $this->OWNER_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                $thumbImage = Helpers::addFileToStorage($fileName, $this->OWNER_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                //Deleting Local Files
                \File::delete($this->OWNER_ORIGINAL_IMAGE_PATH . $fileName);
                \File::delete($this->OWNER_THUMBNAIL_IMAGE_PATH . $fileName);
            }
        }

        $publicAccess =  (!isset($postData['public_access'])) ? 0 : 1;
        $postData['public_access'] = $publicAccess;

        $response = $this->objOwner->insertUpdate($postData);

        $ownerId = ($postData['id'] == 0) ? $response->id : $postData['id'];

        if ($response) 
        {
            //insert/update children
            if(isset($postData['add_children_name']) && !empty($postData['add_children_name']))
            {
                foreach(array_filter($postData['add_children_name']) as $key=>$children)
                {
                    $childrenArray = [];
                    $childrenArray['owner_id'] = $ownerId;
                    $childrenArray['children_name'] = $children;
                    $this->objOwnerChildren->insertUpdate($childrenArray);
                }
            }

            if(isset($postData['deleted_children']) && !empty($postData['deleted_children']))
            {
                foreach($postData['deleted_children'] as $children)
                {
                    $data = $this->objOwnerChildren->find($children);
                    if($data)
                        $data->delete();
                }
            }

            if(isset($postData['update_children_name']) && !empty($postData['update_children_name']))
            {
                foreach($postData['update_children_name'] as $key=>$children)
                {
                    $childrenArray = [];
                    $childrenArray['owner_id'] = $ownerId;
                    $childrenArray['children_name'] = $children;
                    $childrenArray['id'] = $postData['update_children_id'][$key];
                    $this->objOwnerChildren->insertUpdate($childrenArray);
                }
            }

            // insert/update/delete social activity
            if(isset($postData['add_activity_title']) && !empty($postData['add_activity_title']))
            {
                foreach(array_filter($postData['add_activity_title']) as $key=>$activity)
                {
                    $activityArray = [];
                    $activityArray['owner_id'] = $ownerId;
                    $activityArray['activity_title'] = $activity;
                    $this->objOwnerSocialActivity->insertUpdate($activityArray);
                }
            }

            if(isset($postData['deleted_activities']) && !empty($postData['deleted_activities']))
            {
                foreach($postData['deleted_activities'] as $activity)
                {
                    $data = $this->objOwnerSocialActivity->find($activity);
                    if($data)
                        $data->delete();
                }
            }

            if(isset($postData['update_activity_title']) && !empty($postData['update_activity_title']))
            {
                foreach($postData['update_activity_title'] as $key=>$activity)
                {
                    $activityArray = [];
                    $activityArray['owner_id'] = $ownerId;
                    $activityArray['activity_title'] = $activity;
                    $activityArray['id'] = $postData['update_activity_id'][$key];
                    $this->objOwnerSocialActivity->insertUpdate($activityArray);
                }
            }
            
            $this->log->info('Admin owner added/updated successfully', array('admin_user_id' =>  Auth::id(), 'business_id' =>$postData['business_id'], 'owner_id' => $ownerId));
            return Redirect::to("admin/user/business/owner/".Crypt::encrypt($postData['business_id']))->with('success', trans('labels.ownersuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while adding/updating owner', array('admin_user_id' =>  Auth::id(), 'business_id' =>$postData['business_id'], 'owner_id' => $ownerId));
            return Redirect::to("admin/user/business/owner/".Crypt::encrypt($postData['business_id']))->with('error', trans('labels.ownererrormsg'));
        }
    }

    public function delete($id)
    {
        $id = Crypt::decrypt($id);
        $data = $this->objOwner->find($id);
       
        $response = $data->delete();
        if ($response) 
        {
            $this->log->info('Admin owner deleted successfully', array('admin_user_id' =>  Auth::id(), 'business_id' => $data->business_id, 'owner_id' => $id));
            return Redirect::to("admin/user/business/owner/".Crypt::encrypt($data->business_id))->with('success', trans('labels.ownerdeletesuccessmsg'));
        }
    }

    
}
