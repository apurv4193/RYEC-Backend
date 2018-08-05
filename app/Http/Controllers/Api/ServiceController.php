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
use App\ServiceImage;
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

class ServiceController extends Controller
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
        $this->SERVICE_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.SERVICE_THUMBNAIL_IMAGE_WIDTH');
        $this->SERVICE_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.SERVICE_THUMBNAIL_IMAGE_HEIGHT');
        
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
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('service-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));

        $this->catgoryTempImage = Config::get('constant.CATEGORY_TEMP_PATH');
    }
    
    
    /**
     * Add|Edit Product
     */
    public function saveService(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        
            try 
            {
                $validator = Validator::make($request->all(), [
                    'name' =>  ['required', 'max:100', 'regex:/^[a-zA-Z\ ]+$/'],
                    'description' => 'required',
                    'business_id' => 'required',
                    'business_images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
                    'logo' => 'image|mimes:jpeg,png,jpg|max:5120'
                ]);
                if ($validator->fails()) 
                {
                    $this->log->error('API validation failed while saveService', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = $validator->messages()->all()[0];
                    $statusCode = 200;               
                }
                else
                {
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
                            
                            // if logo exist then delete
                            $oldLogo = '';

                            if(isset($requestData['id']) && $requestData['id'] > 0)
                                $oldLogo = $this->objService->find($requestData['id'])->logo;

                            if($oldLogo != '')
                            {
                                $originalImageDelete = Helpers::deleteFileToStorage($oldLogo, $this->SERVICE_ORIGINAL_IMAGE_PATH, "s3");
                                $thumbImageDelete = Helpers::deleteFileToStorage($oldLogo, $this->SERVICE_THUMBNAIL_IMAGE_PATH, "s3");
                            }
                            //Uploading on AWS
                            $originalImage = Helpers::addFileToStorage($fileName, $this->SERVICE_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                            $thumbImage = Helpers::addFileToStorage($fileName, $this->SERVICE_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                            //Deleting Local Files
                            \File::delete($this->SERVICE_ORIGINAL_IMAGE_PATH . $fileName);
                            \File::delete($this->SERVICE_THUMBNAIL_IMAGE_PATH . $fileName);

                            $requestData['logo'] = $fileName;
                        }
                    }
                    
                    $response = $this->objService->insertUpdate($requestData);
                    
                    if ($response) {

                        $serviceId = (isset($requestData['id']) && $requestData['id'] != 0) ? $requestData['id'] : $response->id;
                        
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

                        $serviceData = $this->objService->find($serviceId);

                        $listArray = [];
                        
                        $listArray['id'] =  (string)$serviceId;
                        $listArray['business_id'] =  $serviceData->business_id;
                        $listArray['business_name'] =  (isset($serviceData->serviceBusiness) && !empty($serviceData->serviceBusiness->name)) ? $serviceData->serviceBusiness->name : '';
                        $listArray['name'] =  $serviceData->name;
                        $listArray['descriptions'] =  $serviceData->description;
                        $listArray['metatags'] =  $serviceData->metatags;
                        $listArray['cost'] =  $serviceData->cost;
                        $listArray['default_image'] = url(Config::get('constant.RYEC_DEFAULT_BANNER_IMAGE'));
                       

                        $serviceThumbImgPath = ((isset($serviceData->logo) && !empty($serviceData->logo)) && Storage::size(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$serviceData->logo) > 0) ? Storage::url(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$serviceData->logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                        $serviceOriginalImgPath = ((isset($serviceData->logo) && !empty($serviceData->logo)) && Storage::size(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$serviceData->logo) > 0) ? Storage::url(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$serviceData->logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                        $listArray['thumb_logo'] =  $serviceThumbImgPath;
                        $listArray['original_logo'] =  $serviceOriginalImgPath;

                        $listArray['service_images'] =  [];
                        if(count($serviceData->serviceImages) > 0)
                        {
                            $serviceImageArray = [];
                            foreach($serviceData->serviceImages as $serviceImage)
                            {
                                $imageArray = [];

                                $imageArray['id'] = $serviceImage['id'];
                              
                                $serviceThumbImgPath = ((isset($serviceImage->image_name) && !empty($serviceImage->image_name)) && Storage::size(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$serviceImage->image_name) > 0) ? Storage::url(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$serviceImage->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));

                                $serviceOriginalImgPath = ((isset($serviceImage->image_name) && !empty($serviceImage->image_name)) && Storage::size(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$serviceImage->image_name) > 0) ? Storage::url(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$serviceImage->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));

                                $imageArray['image_thumbnail'] = $serviceThumbImgPath;
                                $imageArray['image_original'] = $serviceOriginalImgPath;
                                
                                $serviceImageArray[] = $imageArray;

                            }
                            $listArray['service_images'] =  $serviceImageArray;
                        }
                        

                        $responseData['status'] = 1;
                        $responseData['message'] =  trans('apimessages.service_saved_success');
                        $responseData['data'] = $listArray;
                        $statusCode = 200;
                        
                    } else {
                        $this->log->error('API something went wrong while saveService', array('login_user_id' => Auth::id()));
                        $responseData['status'] = 0;
                        $responseData['message'] = trans('apimessages.default_error_msg');
                        $responseData['data'] = [];
                        $statusCode = 200;
                    }
                }
            } catch (Exception $e) {
                $this->log->error('API something went wrong while saveService', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
                $responseData = ['status' => 0, 'message' => $e->getMessage()];
                return response()->json($responseData, $statusCode);
            }
        return response()->json($responseData, $statusCode);
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
                
                $outputArray['data']['id'] = $request->service_id;
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

                $imgThumbUrl = (($serviceDetails->logo != '') && Storage::size(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$serviceDetails->logo) > 0) ? Storage::url(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$serviceDetails->logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                $outputArray['data']['default_image'] = url(Config::get('constant.RYEC_DEFAULT_BANNER_IMAGE'));
                $outputArray['data']['thumb_logo'] = $imgThumbUrl;                
                $outputArray['data']['original_logo'] = $imgOriginalUrl;     
                $outputArray['data']['service_images'] = array();
                $i = 0;
                if(isset($serviceDetails->serviceImages) && count($serviceDetails->serviceImages) > 0)
                {   
                    foreach ($serviceDetails->serviceImages as $key => $value)
                    {
                        if(!empty($value->image_name))
                        {
                            $outputArray['data']['service_images'][$i]['id'] = $value->id;
                            $outputArray['data']['service_images'][$i]['image_thumbnail'] = (Storage::size(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$value->image_name) > 0) ? Storage::url(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$value->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                            $outputArray['data']['service_images'][$i]['image_original'] = (Storage::size(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$value->image_name) > 0) ? Storage::url(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$value->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                            $i++;
                        }
                    }
                }           
            }
            else
            {
                $this->log->info('API getServiceDetails no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = new \stdClass();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getServiceDetails', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
    *  Remove Service
    */
    public function removeService(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        
        try 
        {
            $validator = Validator::make($request->all(), [
                'service_id' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while removeService', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $data = $this->objService->find($requestData['service_id']);
                
                if ($data) 
                {
                    $data->delete();
                    $originalImageDelete = Helpers::deleteFileToStorage($data->logo, $this->SERVICE_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($data->logo, $this->SERVICE_THUMBNAIL_IMAGE_PATH, "s3");
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.service_deleted_success');
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while removeService', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while removeService', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);    
    }
}
