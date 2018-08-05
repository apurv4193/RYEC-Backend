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

class ProductController extends Controller
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
        $this->PRODUCT_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_WIDTH');
        $this->PRODUCT_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_HEIGHT');

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
        $this->log = new Logger('product-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }
    
    
    /**
     * Add|Edit Product
     */
    public function saveProduct(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        //print_r($requestData); exit;
            try 
            {
                $validator = Validator::make($request->all(), [
                    'name' =>  ['required', 'max:255'],
                    'description' => 'required',
                    'business_id' => 'required',
                    'product_images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
                    'logo' => 'image|mimes:jpeg,png,jpg|max:5120',
                ]);
                if ($validator->fails()) 
                {
                    $this->log->error('API validation failed while saveProduct', array('login_user_id' => Auth::id()));
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
                            $fileName = 'product_logo_' . uniqid() . '.' . $logo->getClientOriginalExtension();

                            $pathOriginal = public_path($this->PRODUCT_ORIGINAL_IMAGE_PATH . $fileName);
                            $pathThumb = public_path($this->PRODUCT_THUMBNAIL_IMAGE_PATH . $fileName);

                            Image::make($logo->getRealPath())->save($pathOriginal);
                            Image::make($logo->getRealPath())->resize($this->PRODUCT_THUMBNAIL_IMAGE_WIDTH, $this->PRODUCT_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);
                            
                            // if logo exist then delete
                            $oldLogo = '';

                            if(isset($requestData['id']) && $requestData['id'] > 0)
                                $oldLogo = $this->objProduct->find($requestData['id'])->logo;

                            if($oldLogo != '')
                            {
                                $originalImageDelete = Helpers::deleteFileToStorage($oldLogo, $this->PRODUCT_ORIGINAL_IMAGE_PATH, "s3");
                                $thumbImageDelete = Helpers::deleteFileToStorage($oldLogo, $this->PRODUCT_THUMBNAIL_IMAGE_PATH, "s3");
                            }
                            //Uploading on AWS
                            $originalImage = Helpers::addFileToStorage($fileName, $this->PRODUCT_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                            $thumbImage = Helpers::addFileToStorage($fileName, $this->PRODUCT_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                            //Deleting Local Files
                            \File::delete($this->PRODUCT_ORIGINAL_IMAGE_PATH . $fileName);
                            \File::delete($this->PRODUCT_THUMBNAIL_IMAGE_PATH . $fileName);

                            $requestData['logo'] = $fileName;
                        }
                    }
                    $response = $this->objProduct->insertUpdate($requestData);
                    if($response)
                    {
                        $productId = (isset($requestData['id']) && $requestData['id'] != 0) ? $requestData['id'] : $response->id;
                       
                        if (Input::file()) 
                        {  
                            $product_images = Input::file('product_images');

                            $imageArray = [];

                            if (!empty($product_images) && count($product_images) > 0) 
                            {   
                                foreach($product_images as $product_image)
                                {   
                                    $fileName = 'product_' . uniqid() . '.' . $product_image->getClientOriginalExtension();
                                    $pathOriginal = public_path($this->PRODUCT_ORIGINAL_IMAGE_PATH . $fileName);
                                    $pathThumb = public_path($this->PRODUCT_THUMBNAIL_IMAGE_PATH . $fileName);
                                    $product_image->getRealPath(); 

                                    Image::make($product_image->getRealPath())->save($pathOriginal);
                                    Image::make($product_image->getRealPath())->resize(100, 100)->save($pathThumb);

                                     //Uploading on AWS
                                    $originalImage = Helpers::addFileToStorage($fileName, $this->PRODUCT_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                                    $thumbImage = Helpers::addFileToStorage($fileName, $this->PRODUCT_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                                    //Deleting Local Files
                                    \File::delete($this->PRODUCT_ORIGINAL_IMAGE_PATH . $fileName);
                                    \File::delete($this->PRODUCT_THUMBNAIL_IMAGE_PATH . $fileName);

                                    ProductImage::firstOrCreate(['product_id' => $productId , 'image_name' => $fileName]);

                                }
                            }
                        }

                        $productData = $this->objProduct->find($productId);
                        $listArray = [];
                        
                        $listArray['id'] =  $productId;
                        $listArray['business_id'] =  $productData->business_id;
                        $listArray['business_name'] =  (isset($productData->productBusiness) && !empty($productData->productBusiness->name)) ? $productData->productBusiness->name : '';
                        $listArray['name'] =  $productData->name;
                        $listArray['descriptions'] =  $productData->description;
                        $listArray['metatags'] =  $productData->metatags;
                        $listArray['cost'] =  $productData->cost;
                        $listArray['default_image'] = url(Config::get('constant.RYEC_DEFAULT_BANNER_IMAGE'));
                        
                        $productLogoThumbImgPath = ((isset($productData->logo) && !empty($productData->logo)) && Storage::size(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$productData->logo) > 0) ? Storage::url(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$productData->logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                        $productLogoOriginalImgPath = ((isset($productData->logo) && !empty($productData->logo)) && Storage::size(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$productData->logo) > 0) ? Storage::url(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$productData->logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                        $listArray['logo_thumbnail'] = $productLogoThumbImgPath;
                        $listArray['logo_original'] = $productLogoOriginalImgPath;

                        $listArray['product_images'] =  [];
                        if(count($productData->productImages) > 0)
                        {
                            $productImageArray = [];
                            foreach($productData->productImages as $productImage)
                            {
                                $imageArray = [];

                                $imageArray['id'] = $productImage['id'];
                                
                                $productThumbImgPath = ((isset($productImage->image_name) && !empty($productImage->image_name)) && Storage::size(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$productImage->image_name) > 0) ? Storage::url(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$productImage->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));

                                $productOriginalImgPath = ((isset($productImage->image_name) && !empty($productImage->image_name)) && Storage::size(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$productImage->image_name) > 0) ? Storage::url(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$productImage->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));

                                $imageArray['image_thumbnail'] = $productThumbImgPath;
                                $imageArray['image_original'] = $productOriginalImgPath;
                                
                                $productImageArray[] = $imageArray;

                            }
                            $listArray['product_images'] =  $productImageArray;
                        }
                        
                        $responseData['status'] = 1;
                        $responseData['message'] =  trans('apimessages.product_saved_success');
                        $responseData['data'] = $listArray;
                        $statusCode = 200;

                    }
                    else
                    {
                        $this->log->error('API something went wrong while saveProduct', array('login_user_id' => Auth::id()));
                        $responseData['status'] = 0;
                        $responseData['message'] = trans('apimessages.default_error_msg');
                        $responseData['data'] = [];
                        $statusCode = 200;
                    }
                }
               
                        
            } catch (Exception $e) {
                $this->log->error('API something went wrong while saveProduct', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
                $responseData = ['status' => 0, 'message' => $e->getMessage()];
                return response()->json($responseData, $statusCode);
            }
        return response()->json($responseData, $statusCode);
    }

    /**
     * Remove Product Image
     */
    public function removeProductImage(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try 
        {
            $validator = Validator::make($request->all(), [
                'product_image_id' =>  'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while removeProductImage', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $productImageId = $requestData['product_image_id'];
                $data = $this->objProductImage->find($productImageId);
                if($data)
                {
                    $response = $data->delete();
                    $originalImageDelete = Helpers::deleteFileToStorage($data->image_name, $this->PRODUCT_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($data->image_name, $this->PRODUCT_THUMBNAIL_IMAGE_PATH, "s3");

                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.product_image_removed_success');
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while removeProductImage', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.invalid_product_image_id');
                    $statusCode = 200;
                }
            }            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while removeProductImage', array('login_user_id' => Auth::id()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
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
                
                $outputArray['data']['id'] = $request->product_id;
                $outputArray['data']['business_id'] = (isset($productDetails->productBusiness) && !empty($productDetails->productBusiness->id)) ? $productDetails->productBusiness->id : 0;
                $outputArray['data']['business_name'] = (isset($productDetails->productBusiness) && !empty($productDetails->productBusiness->name)) ? $productDetails->productBusiness->name : '';
                        
                $outputArray['data']['name'] = $productDetails->name;
                $outputArray['data']['descriptions'] = (isset($productDetails->description) && !empty($productDetails->description)) ? $productDetails->description : '';
                $outputArray['data']['metatags'] = (isset($productDetails->metatags) && !empty($productDetails->metatags)) ? $productDetails->metatags : '';
                $outputArray['data']['cost'] = (isset($productDetails->cost) && !empty($productDetails->cost)) ? $productDetails->cost : '';
                
                $productLogoThumbImgPath = ((isset($productDetails->logo) && !empty($productDetails->logo)) && Storage::size(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$productDetails->logo) > 0) ? Storage::url(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$productDetails->logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                $productLogoOriginalImgPath = ((isset($productDetails->logo) && !empty($productDetails->logo)) && Storage::size(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$productDetails->logo) > 0) ? Storage::url(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$productDetails->logo) : url(Config::get('constant.DEFAULT_IMAGE'));

                $outputArray['data']['logo_thumbnail'] = $productLogoThumbImgPath;
                $outputArray['data']['logo_original'] = $productLogoOriginalImgPath;

                $outputArray['data']['default_image'] = url(Config::get('constant.RYEC_DEFAULT_BANNER_IMAGE'));
                $outputArray['data']['product_images'] = array();
                $i = 0;
                if(isset($productDetails->productImages) && count($productDetails->productImages) > 0)
                {   
                    foreach ($productDetails->productImages as $key => $value)
                    {
                        if(!empty($value->image_name))
                        {
                            //$imgOriginalPath = $this->PRODUCT_ORIGINAL_IMAGE_PATH.$value->image_name;
                            $outputArray['data']['product_images'][$i]['id'] = $value->id;
                            $outputArray['data']['product_images'][$i]['image_thumbnail'] = (Storage::size(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$value->image_name) > 0) ? Storage::url(Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH').$value->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                            $outputArray['data']['product_images'][$i]['image_original'] = (Storage::size(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$value->image_name) > 0) ? Storage::url(Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH').$value->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));
                            $i++;
                        }
                    }
                }                
            }
            else
            {
                $this->log->info('API getProductDetails no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = new \stdClass();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getProductDetails', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    /**
    *  Remove Product
    */
    public function removeProduct(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        
        try 
        {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while removeProduct', array('login_user_id' => $user->id));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $response = $this->objProduct->find($requestData['product_id']);
                
                if ($response) 
                {
                    $productImageData = $this->objProductImage->getProductImagesByProductId($requestData['product_id'])->toArray();
                    $response->delete();
                    if(!empty($productImageData))
                    { 
                        foreach ($productImageData as $productImage) {
                            $originalImageDelete = Helpers::deleteFileToStorage($productImage['image_name'], $this->PRODUCT_ORIGINAL_IMAGE_PATH, "s3");
                            $thumbImageDelete = Helpers::deleteFileToStorage($productImage['image_name'], $this->PRODUCT_THUMBNAIL_IMAGE_PATH, "s3");
                        }
                    } 

                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.product_deleted_success');
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while removeProduct', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while removeProduct', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);    
    }
}
