<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CategoryRequest;
use Auth;
use Helpers;
use Config;
use Image;
use File;
use DB;
use Input;
use Redirect;
use App\Category;
use App\Business;
use Crypt;
use Response;
use Carbon\Carbon;
use Mail;
use Session;
use Cache;
use Storage;
use \stdClass;
use Validator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->objCategory = new Category();
        $this->objBusiness = new Business();
        
        $this->categoryLogoOriginalImagePath = Config::get('constant.CATEGORY_LOGO_ORIGINAL_IMAGE_PATH');
        $this->categoryLogoThumbImagePath = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH');
        $this->categoryLogoThumbImageHeight = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_HEIGHT');
        $this->categoryLogoThumbImageWidth = Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_WIDTH');
        
        $this->categoryBannerImagePath = Config::get('constant.CATEGORY_BANNER_ORIGINAL_IMAGE_PATH');
        $this->catgoryTempImage = Config::get('constant.CATEGORY_TEMP_PATH');
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('category-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
        
        $this->controller = 'CategoryController';
    }
 
    public function getTrendingCategories(Request $request)
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
        try    
        {   
            $filters = [];
            $filters['trending_category'] = 1;
//          $pageNo = (!empty($request->page)) ? $request->page : 1;
            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {   
                if(isset($request->limit) && !empty($request->limit))
                {
                    $filters['limit'] = $request->limit;
//                  $filters['skip'] = 0; 
                }
            }
            $categories = $this->objCategory->getAll($filters);
            if($categories && count($categories) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.trending_categories_fetched_successfully');
                $statusCode = 200;
                $trendingCategories = array();
                $i = 0;
                foreach ($categories as $key => $value)
                {
                    $trendingCategories[$i]['category_id'] = $value->id;
                    $trendingCategories[$i]['category_name'] = $value->name;
                    $trendingCategories[$i]['category_slug'] = (!empty($value->category_slug)) ? $value->category_slug : '';
                    // if(isset($value->cat_logo) && !empty($value->cat_logo))
                    // {
                    //     //$catLogoPath = $this->categoryLogoThumbImagePath.$value->cat_logo;
                       

                    // }
                    $catLogoPath = ((isset($value->cat_logo) && !empty($value->cat_logo)) && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$value->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$value->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $trendingCategories[$i]['category_logo'] = $catLogoPath;
                    $trendingCategories[$i]['sub_category_count'] = (isset($value->childCategroyData) && count($value->childCategroyData) > 0) ? count($value->childCategroyData) : 0;
                    $trendingCategories[$i]['businesses'] =array();
                    if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
                    {
                        $offsetValue = '0';
                        $filters = [];
                        $filters['offset'] = $offsetValue;  
                        $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    }
                    else
                    {
                        $offsetValue = '-1';
                        $filters = [];
                        $filters['offset'] = $offsetValue; 
                    }                    
                    $getBusinessesData = $this->objBusiness->getBusinessListingByCategoryId($value->id, $filters);

                    if(isset($getBusinessesData) && count($getBusinessesData->where('approved', 1)) > 0)
                    {
                        $j=0;
                        foreach ($getBusinessesData->where('approved', 1) as $getBusinessKey => $getBusinessValue)
                        {
                            $trendingCategories[$i]['businesses'][$j]['id'] = $getBusinessValue->id;
                            $trendingCategories[$i]['businesses'][$j]['name'] = $getBusinessValue->name;
                            $trendingCategories[$i]['businesses'][$j]['business_slug'] = (!empty($getBusinessValue->business_slug)) ? $getBusinessValue->business_slug : '';
                            $trendingCategories[$i]['businesses'][$j]['phone'] = $getBusinessValue->phone;
                            $trendingCategories[$i]['businesses'][$j]['mobile'] = $getBusinessValue->mobile;
                            $trendingCategories[$i]['businesses'][$j]['descriptions'] = $getBusinessValue->description;
                            $trendingCategories[$i]['businesses'][$j]['latitude'] = (!empty($getBusinessValue->latitude)) ? $getBusinessValue->latitude : '';
                            $trendingCategories[$i]['businesses'][$j]['longitude'] = (!empty($getBusinessValue->longitude)) ? $getBusinessValue->longitude : '';
                            $trendingCategories[$i]['businesses'][$j]['address'] = $getBusinessValue->address;
                            $j++;
                        }
                    }
                    $i++;
                }
                $outputArray['data'] = $trendingCategories;
            }
            else
            {
                $this->log->info('API getTrendingCategories no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getTrendingCategories', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }
    
    public function getAllServices(Request $request)
    {
        $outputArray = [];
        $headerData = $request->header('Platform');
        try 
        {
            $filters = [];
            $filters['service_type'] = 1;
            if(isset($headerData) && !empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {    
                if(isset($request->limit) && !empty($request->limit))
                {
                    $filters['take'] = $request->limit;
                    $filters['skip'] = 0; 
                }
            }
            $categories = $this->objCategory->getAll($filters);
            if($categories && count($categories) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.trending_services_fetched_successfully');
                $statusCode = 200;
                $trendingServices = array();
                $i = 0;
                foreach ($categories as $key => $value)
                {
                    $trendingServices[$i]['category_id'] = $value->id;
                    $trendingServices[$i]['name'] = $value->name;
                    $trendingServices[$i]['category_slug'] = (!empty($value->category_slug))? $value->category_slug : '';
                    // if(isset($value->cat_logo) && !empty($value->cat_logo))
                    // {
                    //     // $catLogoPath = $this->categoryLogoThumbImagePath.$value->cat_logo;
                        
                    // }
                    $catLogoPath = ((isset($value->cat_logo) && !empty($value->cat_logo))) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$value->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $trendingServices[$i]['image_url'] = $catLogoPath;
                    $trendingServices[$i]['sub_category_count'] = (isset($value->childCategroyData) && count($value->childCategroyData) > 0) ? count($value->childCategroyData) : 0;

                    $trendingServices[$i]['businesses'] =array();
                    if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
                    {
                        $offsetValue = '0';
                        $filters = [];
                        $filters['offset'] = $offsetValue;  
                        $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    }
                    else
                    {
                        $offsetValue = '-1';
                        $filters = [];
                        $filters['offset'] = $offsetValue;
                    } 
                    $getBusinessesData = $this->objBusiness->getBusinessListingByCategoryId($value->id, $filters);
                    if(isset($getBusinessesData) && count($getBusinessesData->where('approved', 1)) > 0)
                    {
                        $j=0;
                        foreach ($getBusinessesData->where('approved', 1) as $getBusinessKey => $getBusinessValue)
                        {
                            $trendingServices[$i]['businesses'][$j]['id'] = $getBusinessValue->id;
                            $trendingServices[$i]['businesses'][$j]['name'] = $getBusinessValue->name;
                            $trendingServices[$i]['businesses'][$j]['business_slug'] = (!empty($getBusinessValue->business_slug)) ? $getBusinessValue->business_slug : '';
                            $trendingServices[$i]['businesses'][$j]['phone'] = $getBusinessValue->phone;
                            $trendingServices[$i]['businesses'][$j]['mobile'] = $getBusinessValue->mobile;
                            $trendingServices[$i]['businesses'][$j]['descriptions'] = $getBusinessValue->description;
                            $trendingServices[$i]['businesses'][$j]['latitude'] = (!empty($getBusinessValue->latitude)) ? $getBusinessValue->latitude : '';
                            $trendingServices[$i]['businesses'][$j]['longitude'] = (!empty($getBusinessValue->longitude)) ? $getBusinessValue->longitude : '';
                            $trendingServices[$i]['businesses'][$j]['address'] = $getBusinessValue->address;
                            $j++;
                        }
                    }
                    $i++;
                }
                $outputArray['data'] = $trendingServices;
            }
            else
            {
                $this->log->info('API getAllServices no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getAllServices', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }
    
    public function getTrendingServices(Request $request)
    {
        $outputArray = [];
        $headerData = $request->header('Platform');
        try 
        {
            $filters = [];
            $filters['service_type'] = 1;
            $filters['trending_service'] = 1;
            if(isset($headerData) && !empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {    
                if(isset($request->limit) && !empty($request->limit))
                {
                    $filters['take'] = $request->limit;
                    $filters['skip'] = 0; 
                }
            }
            $categories = $this->objCategory->getAll($filters);
            if($categories && count($categories) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.trending_services_fetched_successfully');
                $statusCode = 200;
                $trendingServices = array();
                $i = 0;
                foreach ($categories as $key => $value)
                {
                    $trendingServices[$i]['service_id'] = $value->id;
                    $trendingServices[$i]['category_name'] = $value->name;
                    $trendingServices[$i]['category_slug'] = (!empty($value->category_slug))? $value->category_slug : '';
                    // if(isset($value->cat_logo) && !empty($value->cat_logo))
                    // {
                    //     // $catLogoPath = $this->categoryLogoThumbImagePath.$value->cat_logo;
                        
                    // }
                    $catLogoPath = ((isset($value->cat_logo) && !empty($value->cat_logo)) && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$value->cat_logo) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$value->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $trendingServices[$i]['category_logo'] = $catLogoPath;
                    $trendingServices[$i]['sub_category_count'] = (isset($value->childCategroyData) && count($value->childCategroyData) > 0) ? count($value->childCategroyData) : 0;

                    $trendingServices[$i]['businesses'] =array();
                    if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
                    {
                        $offsetValue = '0';
                        $filters = [];
                        $filters['offset'] = $offsetValue;  
                        $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    }
                    else
                    {
                        $offsetValue = '-1';
                        $filters = [];
                        $filters['offset'] = $offsetValue;
                    } 
                    $getBusinessesData = $this->objBusiness->getBusinessListingByCategoryId($value->id, $filters);
                    if(isset($getBusinessesData) && count($getBusinessesData->where('approved', 1)) > 0)
                    {
                        $j=0;
                        foreach ($getBusinessesData->where('approved', 1) as $getBusinessKey => $getBusinessValue)
                        {
                            $trendingServices[$i]['businesses'][$j]['id'] = $getBusinessValue->id;
                            $trendingServices[$i]['businesses'][$j]['name'] = $getBusinessValue->name;
                            $trendingServices[$i]['businesses'][$j]['business_slug'] = (!empty($getBusinessValue->business_slug)) ? $getBusinessValue->business_slug : '';
                            $trendingServices[$i]['businesses'][$j]['phone'] = $getBusinessValue->phone;
                            $trendingServices[$i]['businesses'][$j]['mobile'] = $getBusinessValue->mobile;
                            $trendingServices[$i]['businesses'][$j]['descriptions'] = $getBusinessValue->description;
                            $trendingServices[$i]['businesses'][$j]['latitude'] = (!empty($getBusinessValue->latitude)) ? $getBusinessValue->latitude : '';
                            $trendingServices[$i]['businesses'][$j]['longitude'] = (!empty($getBusinessValue->longitude)) ? $getBusinessValue->longitude : '';
                            $trendingServices[$i]['businesses'][$j]['address'] = $getBusinessValue->address;
                            $j++;
                        }
                    }
                    $i++;
                }
                $outputArray['data'] = $trendingServices;
            }
            else
            {
                $this->log->info('API getTrendingServices no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getTrendingServices', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }
    
    public function getSubCategory(Request $request)
    {   
        $headerData = $request->header('Platform');
        try 
        {
            $filters['category_id'] = (isset($request->category_id) && !empty($request->category_id)) ? $request->category_id : 0;  
            $filters['category_slug'] = (isset($request->category_slug) && !empty($request->category_slug)) ? $request->category_slug : '';        
            $subCategories = $this->objCategory->getSubCategoryWithCount($filters);
            if($subCategories && !empty($subCategories))
            {
                $response['status'] = 1;
                $response['message'] = trans('apimessages.category_fetched_successfully');
                $statusCode = 200;
                $response['data'] = array();
                $response['data']['category_id'] = $subCategories->id;
                $response['data']['name'] = str_replace("&amp;","&",$subCategories->name);
                $response['data']['category_slug'] = (!empty($subCategories->category_slug)) ? $subCategories->category_slug : '';
                
                $catLogo = $this->catgoryTempImage;
                // if(isset($subCategories->cat_logo) && !empty($subCategories->cat_logo))
                // {
                //     $catLogoPath = $this->categoryLogoThumbImagePath.$subCategories->cat_logo;
                //     $catLogo = (file_exists($catLogoPath)) ? $catLogoPath : $this->catgoryTempImage;

                // }
                $catLogoPath = ((isset($subCategories->cat_logo) && !empty($subCategories->cat_logo))) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$subCategories->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                $response['data']['image_url'] = $catLogoPath;
                $catBannerPath = ((isset($subCategories->banner_img) && !empty($subCategories->banner_img))) ? Storage::url(Config::get('constant.CATEGORY_BANNER_ORIGINAL_IMAGE_PATH').$subCategories->banner_img) : url(Config::get('constant.RYEC_DEFAULT_BANNER_IMAGE'));
                $response['data']['banner_image_url'] = $catBannerPath;

                $response['data']['sub_category'] = array();
                if($subCategories->childCategroyData && count($subCategories->childCategroyData) > 0)
                {
                    $j = 0;
                    foreach($subCategories->childCategroyData as $cKey => $cValue)
                    {
                        $subCatLogo = $this->catgoryTempImage;
                        // if(isset($cValue->cat_logo) && !empty($cValue->cat_logo))
                        // {
                        //     $subCatLogoPath = $this->categoryLogoThumbImagePath.$cValue->cat_logo;
                        //     $subCatLogo = (file_exists($subCatLogoPath)) ? $subCatLogoPath : $this->catgoryTempImage;
                        // }
                        $subCatLogo = ((isset($cValue->cat_logo) && !empty($cValue->cat_logo))) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$cValue->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $response['data']['sub_category'][$j]['category_id'] = $cValue->id;
                        $response['data']['sub_category'][$j]['name'] = str_replace("&amp;","&",$cValue->name);
                        $response['data']['sub_category'][$j]['category_slug'] = (isset($cValue->category_slug)) ? $cValue->category_slug : '';
                        $response['data']['sub_category'][$j]['image_url'] = $subCatLogo;
                        $response['data']['sub_category'][$j]['sub_category_count'] = (isset($cValue->childCategroyData) && count($cValue->childCategroyData) > 0) ? count($cValue->childCategroyData) : 0;

                        $businessData = $this->objCategory->categoryHasBusinesses($cValue->id);
                        $response['data']['sub_category'][$j]['isBusiness'] = ($businessData && count($businessData) > 0)? 1 : 0;
                        $j++;
                    }                    
                }
            }
            else
            {
                $this->log->info('API getSubCategory no records found', array('login_user_id' => Auth::id()));
                $response['status'] = 0;
                $response['message'] = trans('apimessages.norecordsfound');
                $response['data'] = new \stdClass();
                $statusCode = 200;
            }
            return response()->json($response, $statusCode);
        } catch (Exception $ex) {
            $this->log->error('API something went wrong while getSubCategory', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $response['status'] = 0;
            $response['message'] = $e->getMessage();
            $statusCode = 400;
            return response()->json($response, $statusCode);
        }
    }
    
    public function getMainCategory(Request $request)
    {
        $headerData = $request->header('Platform');
        $filters = [];
        $filters['parent'] = '0';
        $categories = $this->objCategory->getAll($filters);
        if($categories)
        {
            if(count($categories) > 0)
            {
                $response['status'] = 1;
                $response['message'] = trans('apimessages.category_fetched_successfully');
                $statusCode = 200;
                $response['data'] = array();
                $i = 0;
                foreach($categories as $pKey => $pValue)
                {
                    $response['data'][$i]['category_id'] = $pValue->id;
                    $response['data'][$i]['name'] = str_replace("&amp;","&",$pValue->name);
                    $response['data'][$i]['category_slug'] = (!empty($pValue->category_slug)) ? $pValue->category_slug : '';
                    $businessData = $this->objCategory->categoryHasBusinesses($pValue->id);
                    $response['data'][$i]['isBusiness'] = ($businessData && count($businessData) > 0)? 1 : 0;
                    
                    $catLogo = $this->catgoryTempImage;
                    // if(isset($pValue->cat_logo) && !empty($pValue->cat_logo))
                    // {
                    //     $catLogoPath = $this->categoryLogoThumbImagePath.$pValue->cat_logo;
                    //     $catLogo = (file_exists($catLogoPath)) ? $catLogoPath : $this->catgoryTempImage;
                    // }
                    $catLogo = ((isset($pValue->cat_logo) && !empty($pValue->cat_logo))) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$pValue->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $response['data'][$i]['image_url'] = $catLogo;
                    $response['data'][$i]['sub_category'] = array();
                    if($pValue->childCategroyData && count($pValue->childCategroyData) > 0)
                    {
                        $j = 0;
                        foreach($pValue->childCategroyData as $cKey => $cValue)
                        {
                            $response['data'][$i]['sub_category'][$j]['category_id'] = $cValue->id;
                            $response['data'][$i]['sub_category'][$j]['name'] = str_replace("&amp;","&",$cValue->name);
                            $response['data'][$i]['sub_category'][$j]['category_slug'] = (!empty($cValue->category_slug)) ? $cValue->category_slug : '';
                            
                            $subCatLogo = $this->catgoryTempImage;
                            // if(isset($cValue->cat_logo) && !empty($cValue->cat_logo))
                            // {
                            //     $subCatLogoPath = $this->categoryLogoThumbImagePath.$cValue->cat_logo;
                            //     $subCatLogo = (file_exists($subCatLogoPath)) ? $subCatLogoPath : $this->catgoryTempImage;
                            // }           
                            $subCatLogo = ((isset($cValue->cat_logo) && !empty($cValue->cat_logo)) > 0) ? Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$cValue->cat_logo) : url(Config::get('constant.DEFAULT_IMAGE'));                 
                            $response['data'][$i]['sub_category'][$j]['image_url'] = $subCatLogo;
                            $j++;
                        }                    
                    }
                    $i++;
                }
            }
            else
            {
                $this->log->info('API getMainCategory no records found', array('login_user_id' => Auth::id()));
                $response['status'] = 0;
                $response['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $response['data'] = array();
            }            
        }
        else
        {
            $this->log->error('API something went wrong while getMainCategory', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $response['status'] = 0;
            $response['message'] = trans('apimessages.default_error_msg');
            $statusCode = 400;
            $response['data'] = array();
        }
        return response()->json($response, $statusCode);        
    }

    /**
    *  Get CategoryMetaTags
    */
    public function getCategoryMetaTags(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        
        try 
        {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $categoryArray['idIn'] = explode(',',$requestData['category_id']);
                $categoryData = $this->objCategory->getAll($categoryArray);
                $listArray = [];

                if (count($categoryData) > 0)
                {
                    foreach($categoryData as $cat)
                    {
                        if($cat['metatags'])
                        {
                            $explodeTags = explode(',',$cat['metatags']);
                            foreach($explodeTags as $tag)
                            {
                                $listArray[] = $tag;
                            }
                        }
                    }
                    $this->log->info('API getCategoryMetaTags get successfully', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.get_category_meta_tags');
                    $responseData['data'] =  $listArray;
                    $statusCode = 200;
                }
                else
                {
                    $this->log->info('API getCategoryMetaTags no records found', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.norecordsfound');
                    $responseData['data'] =  $listArray;
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getCategoryMetaTags', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);    
    }
    
}
