<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Requests\ServiceRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\User;
use App\Service;
use App\Business;
use App\ServiceImage;
use App\UserRole;
use App\Category;
use App\Http\Controllers\Controller;
use Crypt;
use Image;
use File;
use Helpers;
use Illuminate\Contracts\Encryption\DecryptException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objUser = new User();
        $this->objUserRole = new UserRole();
        $this->objService = new Service();
        $this->objServiceImage = new ServiceImage();
        $this->SERVICE_ORIGINAL_IMAGE_PATH = Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH');
        $this->SERVICE_THUMBNAIL_IMAGE_PATH = Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH');
        $this->SERVICE_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.USER_THUMBNAIL_IMAGE_HEIGHT');
        $this->SERVICE_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.USER_THUMBNAIL_IMAGE_WIDTH');
        $this->objCategory = new Category();
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('service-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }

    public function index($businessId)
    {   
        try {
            $businessId = Crypt::decrypt($businessId);
            $businessDetails = Business::find($businessId);
            //$serviceList = $this->objService->getAll(['business_id'=>$businessId]);
            $this->log->info('Admin service listing page', array('admin_user_id' =>  Auth::id(),'business_id' => $businessId));
            return view('Admin.ListService', compact('businessDetails','businessId'));
        } catch (DecryptException $e) {
             $this->log->error('Admin something went wrong while service listing page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function add($businessId)
    {
        try {
            $businessId = Crypt::decrypt($businessId);
            $businessDetails = Business::find($businessId);
            $businessCategory = '';

            $sort = [];
            $sort['parent'] = $businessDetails->category_id;
            $parentCategories = $this->objCategory->getAll($sort);
            
            $pSort = [];
            $pSort['parent'] = '0';
            $categories = $this->objCategory->getAll($pSort);
            $this->log->info('Admin service add page', array('admin_user_id' =>  Auth::id(),'business_id' => $businessId));
            return view('Admin.EditService',compact('businessId','parentCategories','categories','businessCategory','businessDetails'));
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while service add page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function edit($id)
    {
        try 
        {
            $id = Crypt::decrypt($id);
            $data = $this->objService->find($id);
            $businessId = $data->business_id;
            $businessDetails = Business::find($businessId);
            $businessCategory = '';
           
            $sort = [];
            $sort['parent'] = $businessDetails->category_id;
            $parentCategories = $this->objCategory->getAll($sort);
            
            $pSort = [];
            $pSort['parent'] = '0';
            $categories = $this->objCategory->getAll($pSort);
            
            if($data) 
            {
                $this->log->info('Admin service edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'service_id' => $id));
                return view('Admin.EditService', compact('businessId','parentCategories','categories','data','businessCategory','businessDetails'));
            } else {
                $this->log->error('Admin something went wrong while product edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'service_id' => $id));
                return Redirect::to("admin/users")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while service edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'service_id' => $id, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function save(ServiceRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);
        
        // $postData['categoryArray'] = array_filter($postData['categoryArray']);

        // if(!empty($postData['categoryArray']))
        // {
        //     $postData['category_hierarchy'] = implode(',',$postData['categoryArray']);
        //     $postData['category_id'] = end($postData['categoryArray']);
        // }
        // unset($postData['categoryArray']);
        // $postData['cost'] = ($postData['cost']) ? $postData['cost'] : 0;

        if (Input::file('logo')) 
        {  
            $logo = Input::file('logo'); 

            if (!empty($logo) && count($logo) > 0) 
            {
                $fileName = 'service_logo_' . uniqid() . '.' . $logo->getClientOriginalExtension();

                $pathOriginal = public_path($this->SERVICE_ORIGINAL_IMAGE_PATH . $fileName);
                $pathThumb = public_path($this->SERVICE_THUMBNAIL_IMAGE_PATH . $fileName);

                Image::make($logo->getRealPath())->save($pathOriginal);
                Image::make($logo->getRealPath())->resize($this->SERVICE_THUMBNAIL_IMAGE_WIDTH, $this->SERVICE_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);
                
                if(isset($postData['old_logo']) && $postData['old_logo'] != '')
                {
                    $originalImageDelete = Helpers::deleteFileToStorage($postData['old_logo'], $this->SERVICE_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($postData['old_logo'], $this->SERVICE_THUMBNAIL_IMAGE_PATH, "s3");
                }
                //Uploading on AWS
                $originalImage = Helpers::addFileToStorage($fileName, $this->SERVICE_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                $thumbImage = Helpers::addFileToStorage($fileName, $this->SERVICE_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                //Deleting Local Files
                \File::delete($this->SERVICE_ORIGINAL_IMAGE_PATH . $fileName);
                \File::delete($this->SERVICE_THUMBNAIL_IMAGE_PATH . $fileName);

                $postData['logo'] = $fileName;
            }
        }
        
        $response = $this->objService->insertUpdate($postData);
        $serviceId = ($postData['id'] == 0 && isset($response->id) && $response->id > 0) ? $response->id : $postData['id'];

        if(isset($postData['id']) && $postData['id'] == 0)
        {
            
            $this->validate($request,
                ['service_images' => 'required',
                'service_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'],
                ['service_images.*.max' => 'File size must be less than 5 MB']
            );
        }
        else
        {
            $this->validate($request,
                ['service_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'],
                ['service_images.*.max' => 'File size must be less than 5 MB']
            );
        }
        
        if (Input::file()) 
        {  
            $service_images = Input::file('service_images');

            $imageArray = [];

            if (!empty($service_images) && count($service_images) > 0) 
            {   
                foreach($service_images as $service_image)
                {   
                    $fileName = 'service_' . uniqid() . '.' . $service_image->getClientOriginalExtension();
                    $pathOriginal = public_path($this->SERVICE_ORIGINAL_IMAGE_PATH . $fileName);
                    $pathThumb = public_path($this->SERVICE_THUMBNAIL_IMAGE_PATH . $fileName);
                    $service_image->getRealPath(); 

                    Image::make($service_image->getRealPath())->save($pathOriginal);
                    Image::make($service_image->getRealPath())->resize(100, 100)->save($pathThumb);

                     //Uploading on AWS
                    $originalImage = Helpers::addFileToStorage($fileName, $this->SERVICE_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                    $thumbImage = Helpers::addFileToStorage($fileName, $this->SERVICE_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                    //Deleting Local Files
                    \File::delete($this->SERVICE_ORIGINAL_IMAGE_PATH . $fileName);
                    \File::delete($this->SERVICE_THUMBNAIL_IMAGE_PATH . $fileName);

                    ServiceImage::firstOrCreate(['service_id' => $serviceId , 'image_name' => $fileName]);

                }
            }
        }

        if ($response) {
            $this->log->info('Admin service added/updated successfully', array('admin_user_id' =>  Auth::id(), 'business_id' => $postData['business_id'], 'service_id' => $serviceId));
            return Redirect::to("admin/user/business/service/".Crypt::encrypt($postData['business_id']))->with('success', trans('labels.servicesuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while adding/updating service', array('admin_user_id' =>  Auth::id(), 'business_id' => $postData['business_id'], 'service_id' => $serviceId));
            return Redirect::to("admin/user/business/service/".Crypt::encrypt($postData['business_id']))->with('error', trans('labels.serviceerrormsg'));
        }
    }

    public function delete($id)
    {
        $id = Crypt::decrypt($id);
        $data = $this->objService->find($id);
       
        if ($data) 
        {
            $response = $data->delete();
            $originalImageDelete = Helpers::deleteFileToStorage($data->logo, $this->SERVICE_ORIGINAL_IMAGE_PATH, "s3");
            $thumbImageDelete = Helpers::deleteFileToStorage($data->logo, $this->SERVICE_THUMBNAIL_IMAGE_PATH, "s3");
            $this->log->info('Admin service deleted successfully', array('admin_user_id' =>  Auth::id(), 'business_id' => $data->business_id, 'service_id' => $id));
            return Redirect::to("admin/user/business/service/".Crypt::encrypt($data->business_id))->with('success', trans('labels.servicedeletesuccessmsg'));
        }
    }

    public function removeServiceImage()
    {
        $serviceImageId = Input::get('serviceImageId');
        $data = $this->objServiceImage->find($serviceImageId);
        if($data)
        {
            $response = $data->delete();
            $originalImageDelete = Helpers::deleteFileToStorage($data->image_name, $this->SERVICE_ORIGINAL_IMAGE_PATH, "s3");
            $thumbImageDelete = Helpers::deleteFileToStorage($data->image_name, $this->SERVICE_THUMBNAIL_IMAGE_PATH, "s3");
        }
        return 1;
        
    }

   
}
