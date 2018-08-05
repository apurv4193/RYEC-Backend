<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Requests\ProductRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\User;
use App\Product;
use App\Business;
use App\ProductImage;
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

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objUser = new User();
        $this->objUserRole = new UserRole();
        $this->objProduct = new Product();
        $this->objProductImage = new ProductImage();
        $this->PRODUCT_ORIGINAL_IMAGE_PATH = Config::get('constant.PRODUCT_ORIGINAL_IMAGE_PATH');
        $this->PRODUCT_THUMBNAIL_IMAGE_PATH = Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_PATH');
        $this->PRODUCT_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_WIDTH');
        $this->PRODUCT_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.PRODUCT_THUMBNAIL_IMAGE_HEIGHT');
        $this->objCategory = new Category();
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('product-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }

    public function index($businessId)
    {   
        try 
        {
            $businessId = Crypt::decrypt($businessId);
            $businessDetails = Business::find($businessId);
            $this->log->info('Admin product listing page', array('admin_user_id' =>  Auth::id(),'business_id' => $businessId));
            return view('Admin.ListProduct', compact('businessDetails','businessId'));
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while product listing page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function add($businessId)
    {
        try 
        {
            $businessId = Crypt::decrypt($businessId);
            $businessDetails = Business::find($businessId);
            $this->log->info('Admin product add page', array('admin_user_id' =>  Auth::id(),'business_id' => $businessId));
            return view('Admin.EditProduct',compact('businessId','businessDetails'));
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while product add page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'error' => $e->getMessage()));
            return view('errors.404');
        }
    }

    public function edit($id)
    {
        try 
        {
            $id = Crypt::decrypt($id);
            $data = $this->objProduct->find($id);
            $productImages = $this->objProductImage->getProductImagesByproductId($id)->toArray();
            $businessId = $data->business_id;
            $businessDetails = Business::find($businessId);
           // $businessCategory = $this->objCategory->find($businessDetails->category_id)->name;
            
            $sort = [];
            $sort['parent'] = $businessDetails->category_id;
            $parentCategories = $this->objCategory->getAll($sort);
            
            $pSort = [];
            $pSort['parent'] = '0';
            $categories = $this->objCategory->getAll($pSort);
            if($data) {
                $this->log->info('Admin product edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'product_id' => $id));
                return view('Admin.EditProduct', compact('businessId','parentCategories','categories','data','productImages','businessDetails'));
            } else {
                $this->log->error('Admin something went wrong while product edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'product_id' => $id));
                return Redirect::to("admin/user/business/product/".Crypt::encrypt($businessId))->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while product edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'product_id' => $id, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function save(ProductRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);
        
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
                
                if(isset($postData['old_logo']) && $postData['old_logo'] != '')
                {
                    $originalImageDelete = Helpers::deleteFileToStorage($postData['old_logo'], $this->PRODUCT_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($postData['old_logo'], $this->PRODUCT_THUMBNAIL_IMAGE_PATH, "s3");
                }
                //Uploading on AWS
                $originalImage = Helpers::addFileToStorage($fileName, $this->PRODUCT_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                $thumbImage = Helpers::addFileToStorage($fileName, $this->PRODUCT_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                //Deleting Local Files
                \File::delete($this->PRODUCT_ORIGINAL_IMAGE_PATH . $fileName);
                \File::delete($this->PRODUCT_THUMBNAIL_IMAGE_PATH . $fileName);

                $postData['logo'] = $fileName;
            }
        }

        $response = $this->objProduct->insertUpdate($postData);
        
        $productId = ($postData['id'] == 0 && isset($response->id) && $response->id > 0) ? $response->id : $postData['id'];

        if(isset($postData['id']) && $postData['id'] == 0)
        {
            $this->validate($request,
                ['product_images' => 'required',
                'product_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'],
                ['product_images.*.max' => 'File size must be less than 5 MB']
            );
        }
        else
        {
            $this->validate($request,
                ['product_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'],
                ['product_images.*.max' => 'File size must be less than 5 MB']
            );
        }
        
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

        if ($response) {
            $this->log->info('Admin product added/updated successfully', array('admin_user_id' =>  Auth::id(), 'business_id' => $postData['business_id'], 'product_id' => $productId));
            return Redirect::to("admin/user/business/product/".Crypt::encrypt($postData['business_id']))->with('success', trans('labels.productsuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while adding/updating product', array('admin_user_id' =>  Auth::id(), 'business_id' => $postData['business_id'], 'product_id' => $productId));
            return Redirect::to("admin/user/business/product/".Crypt::encrypt($postData['business_id']))->with('error', trans('labels.producterrormsg'));
        }
    }

    public function delete($id)
    {
        $id = Crypt::decrypt($id);
        $data = $this->objProduct->find($id);
        $productImageData = $this->objProductImage->getProductImagesByProductId($id)->toArray();

        $response = $data->delete();
        if ($response) 
        {
            if(!empty($productImageData))
            { 
                foreach ($productImageData as $productImage) {
                    $originalImageDelete = Helpers::deleteFileToStorage($productImage['image_name'], $this->PRODUCT_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($productImage['image_name'], $this->PRODUCT_THUMBNAIL_IMAGE_PATH, "s3");
                }
            }
            $this->log->info('Admin product deleted successfully', array('admin_user_id' =>  Auth::id(), 'business_id' => $data->business_id, 'owner_id' => $id));
            return Redirect::to("admin/user/business/product/".Crypt::encrypt($data->business_id))->with('success', trans('labels.productdeletesuccessmsg'));
        }
    }

    public function getSubCategotyById()
    {
        $categoryId = Input::get('categoryId');
        $categoryArray = [];

        if($categoryId != '') {
            $categoryArray = $this->objCategory->getAll(array('parent' =>  $categoryId));
        } 
        if(!empty($categoryArray->toArray()))
            return view('Admin.CategoriesTemplate', compact('categoryArray'));
    }

    public function removeProductImage()
    {
        $productImageId = Input::get('productImageId');
        $data = $this->objProductImage->find($productImageId);
        if($data)
        {
            $response = $data->delete();
            $originalImageDelete = Helpers::deleteFileToStorage($data->image_name, $this->PRODUCT_ORIGINAL_IMAGE_PATH, "s3");
            $thumbImageDelete = Helpers::deleteFileToStorage($data->image_name, $this->PRODUCT_THUMBNAIL_IMAGE_PATH, "s3");
        }
        return 1;
        
    }


}
