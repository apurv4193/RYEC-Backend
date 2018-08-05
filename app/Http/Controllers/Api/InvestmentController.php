<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\InvestmentIdeasRequest;
use App\Http\Controllers\Controller;
use App\Category;
use App\InvestmentIdeas;
use App\InvestmentIdeasFiles;
use App\InvestmentIdeasInterest;
use Auth;
use Helpers;
use DB;
use Input;
use Config;
use Redirect;
use Response;
use Mail;
use Session;
use Image;
use File;
use Crypt;
use Validator;
use Carbon\Carbon;
use Cache;
use Storage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class InvestmentController extends Controller
{
    public function __construct()
    {
        $this->objInvestmentIdeas = new InvestmentIdeas();
        $this->objInvestmentIdeasFiles = new InvestmentIdeasFiles();
        $this->objInvestmentIdeasInterest = new InvestmentIdeasInterest();
      
        $this->investmentIdeasFileImagesPath = Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH');
        $this->investmentIdeasFileVideosPath = Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_VIDEOS_PATH');
        $this->investmentIdeasFileDocsPath = Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_DOCS_PATH');
        $this->userThumbnailImagePath = Config::get('constant.USER_THUMBNAIL_IMAGE_PATH');
        $this->catgoryTempImage = Config::get('constant.CATEGORY_TEMP_PATH');
        
        $this->controller = 'InvestmentController';

        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('investment-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }

    public function getInvestmentIdeaDetails(Request $request)
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
        $data = [];
        $requestData = $request->all();
        try 
        {
            if(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                $titleSlug = (isset($requestData['idea_slug']) && !empty($requestData['idea_slug'])) ? $requestData['idea_slug'] : '';
                $investmentIdeaDetails = InvestmentIdeas::where('title_slug', $titleSlug)->first();
            }
            else
            {
                $requestData['idea_id'] = (isset($requestData['idea_id']) && !empty($requestData['idea_id'])) ? $requestData['idea_id'] : '';
                $investmentIdeaDetails = $this->objInvestmentIdeas->getInvestmentIdeasDetails($requestData['idea_id']);
            }
            
            Cache::forget('investmentDetail');
            if($investmentIdeaDetails && count($investmentIdeaDetails) > 0)
            {
                $interestDetails = $this->objInvestmentIdeasInterest->getAll(array('idea_id'=>$investmentIdeaDetails->id,'user_id'=>Auth::id()));
                $interestFlag = 0;
                if($interestDetails->count() > 0)
                {
                    $interestFlag = 1;
                }
                if(Auth::id() != $investmentIdeaDetails->user_id)
                {
                    $investmentIdeaDetails->visits = $investmentIdeaDetails->visits + 1;
                    $investmentIdeaDetails->save();
                }
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.investment_ideas_getails_fetch_successfully');
                $statusCode = 200; 
                $outputArray['data'] = array();
                
                
                $outputArray['data']['interest_flag'] = $interestFlag;
                $outputArray['data']['id'] = $investmentIdeaDetails->id;
                $outputArray['data']['creator_name'] = (isset($investmentIdeaDetails->getUsersDetails) && !empty($investmentIdeaDetails->getUsersDetails->name)) ? $investmentIdeaDetails->getUsersDetails->name : '';
                $outputArray['data']['creator_email'] = (isset($investmentIdeaDetails->getUsersDetails) && !empty($investmentIdeaDetails->getUsersDetails->email)) ? $investmentIdeaDetails->getUsersDetails->email : '';
                $outputArray['data']['creator_phone'] = (isset($investmentIdeaDetails->getUsersDetails) && !empty($investmentIdeaDetails->getUsersDetails->phone)) ? $investmentIdeaDetails->getUsersDetails->phone : '';
                $outputArray['data']['creator_business_name'] = (isset($investmentIdeaDetails->getUsersDetails->singlebusiness->name) && !empty($investmentIdeaDetails->getUsersDetails->singlebusiness->name)) ? $investmentIdeaDetails->getUsersDetails->singlebusiness->name : '';
                $outputArray['data']['creator_business_id'] = (isset($investmentIdeaDetails->getUsersDetails->singlebusiness->name) && !empty($investmentIdeaDetails->getUsersDetails->singlebusiness->name)) ? $investmentIdeaDetails->getUsersDetails->singlebusiness->id : '';
                $outputArray['data']['creator_business_slug'] = (isset($investmentIdeaDetails->getUsersDetails->singlebusiness->name) && !empty($investmentIdeaDetails->getUsersDetails->singlebusiness->name)) ? $investmentIdeaDetails->getUsersDetails->singlebusiness->business_slug : '';
                $outputArray['data']['default_image'] = url(Config::get('constant.RYEC_DEFAULT_BANNER_IMAGE'));


                $businessImageName = (isset($investmentIdeaDetails->getUsersDetails->singlebusiness->businessImagesById->image_name) && !empty($investmentIdeaDetails->getUsersDetails->singlebusiness->businessImagesById->image_name)) ? $investmentIdeaDetails->getUsersDetails->singlebusiness->businessImagesById->image_name : '';

                $businessThumbImage = ((isset($businessImageName) && !empty($businessImageName)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH') .$businessImageName) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH') . $businessImageName) : url(Config::get('constant.DEFAULT_IMAGE'));

                $businessOriginalImage = ((isset($businessImageName) && !empty($businessImageName)) && Storage::size(Config::get('constant.BUSINESS_ORIGINAL_IMAGE_PATH') .$businessImageName) > 0) ? Storage::url(Config::get('constant.BUSINESS_ORIGINAL_IMAGE_PATH') . $businessImageName) : url(Config::get('constant.DEFAULT_IMAGE'));

                 $outputArray['data']['creator_business_thumb_image'] = $businessThumbImage;
                 $outputArray['data']['creator_business_original_image'] = $businessOriginalImage;

                $outputArray['data']['category_id'] = $investmentIdeaDetails->category_id;
                $outputArray['data']['category_name'] = (isset($investmentIdeaDetails->getCategoryDetails) && !empty($investmentIdeaDetails->getCategoryDetails->name)) ? $investmentIdeaDetails->getCategoryDetails->name : '';
                $outputArray['data']['location'] = $investmentIdeaDetails->location;
                
                $creatorProfilePicPath = '';
                if(isset($investmentIdeaDetails->getUsersDetails) && !empty($investmentIdeaDetails->getUsersDetails->profile_pic))
                {
                    $creatorProfilePic = $investmentIdeaDetails->getUsersDetails->profile_pic;
                    $creatorProfilePicPath = $this->userThumbnailImagePath.$creatorProfilePic;
                }
                $outputArray['data']['creator_profile_pic'] = (file_exists($creatorProfilePicPath)) ? url($creatorProfilePicPath) : url($this->catgoryTempImage);
                $outputArray['data']['title'] = $investmentIdeaDetails->title;
                $outputArray['data']['title_slug'] = (!empty($investmentIdeaDetails->title_slug)) ? $investmentIdeaDetails->title_slug : '';
                $outputArray['data']['descriptions'] = $investmentIdeaDetails->description;
                $outputArray['data']['investment_amount_start'] = $investmentIdeaDetails->investment_amount_start;
                $outputArray['data']['investment_amount_end'] = $investmentIdeaDetails->investment_amount_end;
                $outputArray['data']['project_duration'] = $investmentIdeaDetails->project_duration;
                $outputArray['data']['member_name'] = $investmentIdeaDetails->member_name;
                $outputArray['data']['member_email'] = $investmentIdeaDetails->member_email;
                $outputArray['data']['member_phone'] = $investmentIdeaDetails->member_phone;
                $outputArray['data']['offering_percent'] = $investmentIdeaDetails->offering_percent;
                
                $outputArray['data']['interests'] = array();
                if(isset($investmentIdeaDetails->investmentIdeasInterest) && count($investmentIdeaDetails->investmentIdeasInterest) > 0)
                {
                    $i = 0;
                    foreach($investmentIdeaDetails->investmentIdeasInterest as $interestKey => $interestValue)
                    {
                        $outputArray['data']['interests'][$i]['descriptions'] = ($interestValue->description) ? $interestValue->description : '';
                        $outputArray['data']['interests'][$i]['user_name'] = (isset($interestValue->getUsers) && $interestValue->getUsers->name) ? $interestValue->getUsers->name : '';
                        
                        $outputArray['data']['interests'][$i]['phone'] = (isset($interestValue->getUsers) && $interestValue->getUsers->phone) ? $interestValue->getUsers->phone : '';
                        
                        $interestProfilePicPath = '';
                        if(isset($interestValue->getUsers) && !empty($interestValue->getUsers->profile_pic))
                        {
                            $interestProfilePic = $interestValue->getUsers->profile_pic;
                            $interestProfilePicPath = $this->userThumbnailImagePath.$interestProfilePic;
                        }
                        $outputArray['data']['interests'][$i]['profile_pic'] = (file_exists($interestProfilePicPath)) ? url($interestProfilePicPath) : url($this->catgoryTempImage);
                        
                        $i++;
                    }
                }                
                
                $outputArray['data']['file_images'] = array();
                $outputArray['data']['file_videos'] = array();
                $outputArray['data']['file_docs'] = array();                
                if(isset($investmentIdeaDetails->investmentIdeasFiles) && count($investmentIdeaDetails->investmentIdeasFiles) > 0)
                {
                    $j = 0;
                    $k = 0;
                    $l = 0;
                    foreach ($investmentIdeaDetails->investmentIdeasFiles as $invIdeasFileKey => $invIdeasFileValue)
                    {

                        if($invIdeasFileValue->file_type == 1)
                        {
                            $fileImgName = $invIdeasFileValue->file_name;
                            
                            if((isset($invIdeasFileValue->file_name) && !empty($invIdeasFileValue->file_name)) && Storage::size(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH').$invIdeasFileValue->file_name) > 0)
                            {
                                //$outputArray['data']['file_images'][$j] =  url($fileImgPath);
                                
                                $outputArray['data']['file_images'][$j]['id'] = $invIdeasFileValue->id;
                                $outputArray['data']['file_images'][$j]['url'] =  Storage::url(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH').$invIdeasFileValue->file_name);
                                 $j++;
                            }
                        }
                        elseif($invIdeasFileValue->file_type == 2)
                        {
                            $fileVideoName = $invIdeasFileValue->file_name;
                            
                            if(!empty($fileVideoName))
                            {

                                $media = $fileVideoName; 

                                $videoId = '';
                                
                                if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $media, $match)) 
                                {
                                    $videoId = $match[1];
                                    $thumbnailVideo = 'https://img.youtube.com/vi/'.$videoId.'/1.jpg';
                                }
                                $media_video_url_id  =  $videoId;
                                $outputArray['data']['file_videos'][$k]['id'] = $invIdeasFileValue->id;
                                $outputArray['data']['file_videos'][$k]['url'] =  $fileVideoName;
                                $outputArray['data']['file_videos'][$k]['thumbnail'] =  $thumbnailVideo;
                                $outputArray['data']['file_videos'][$k]['video_id'] =  $videoId;
                                $k++;
                            }
                        }
                        elseif($invIdeasFileValue->file_type == 3)
                        {
                            $fileDocName = $invIdeasFileValue->file_name;
                            $fileDocPath = $this->investmentIdeasFileDocsPath.$fileDocName;

                            if((isset($invIdeasFileValue->file_name) && !empty($invIdeasFileValue->file_name)) && Storage::size(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_DOCS_PATH').$invIdeasFileValue->file_name) > 0)
                            {
                                //$outputArray['data']['file_images'][$j] =  url($fileImgPath);
                                
                                $outputArray['data']['file_docs'][$l]['id'] = $invIdeasFileValue->id;
                                $outputArray['data']['file_docs'][$l]['url'] =  Storage::url(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_DOCS_PATH').$invIdeasFileValue->file_name);
                                $outputArray['data']['file_docs'][$l]['name'] = $invIdeasFileValue->file_name;
                                $l++;
                            }
                            
                        }
                    }
                }
            }
            else
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
        }   catch (Exception $e) {
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
       return response()->json($outputArray, $statusCode); 
    }
    
    public function showInterestOnInvestmentIdea(Request $request) 
    {
        $outputArray = [];
        $data = [];
        $requestData = $request->all();
        try 
        {
            $validator = Validator::make($request->all(), [
                    'idea_id' => 'required',
                    'user_id'  =>  'required',
                    'description'  =>  'required'
                ]
            );            
            if ($validator->fails()) 
            {

                DB::rollback(); 
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray,$statusCode);
            }
            else
            {   
                $data['idea_id'] = $requestData['idea_id'];
                $data['user_id'] = $requestData['user_id'];
                $data['description'] = $requestData['description'];
                $response = $this->objInvestmentIdeasInterest->insertUpdate($data);
                if($response)
                {
                    $outputArray['status'] = 1;
                    $outputArray['message'] =  trans('apimessages.show_interest_on_investment_ideas_added_successfully');
                    $statusCode = 200;
                }
                else
                {
                    $outputArray['status'] = 0;
                    $outputArray['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 400;
                }
            }
        } catch (Exception $e) {
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }
    
    public function getInvestmentIdeas(Request $request) 
    {
        $outputArray = [];
        $headerData = $request->header('Platform');
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        $limit = (isset($request->limit) && !empty($request->limit)) ? $request->limit : 5;
        $price_range = (isset($request->price_range) && !empty($request->price_range)) ? $request->price_range : '';
        try 
        {
            $perPageRecord = $limit;
            $offset = Helpers::getOffset($pageNo, $perPageRecord);
            $sortParam = [];
            $sortParam['offset'] = $offset;
            $sortParam['limit'] = $limit;
            $sortParam['price_range'] = $price_range;
            if((isset($request->min_price) && $request->min_price != '') && (isset($request->max_price) && $request->max_price != ''))
            {
                $sortParam['min_price'] = $request->min_price;
                $sortParam['max_price'] = $request->max_price;
            }
            if((isset($request->category_id) && count(array_filter($request->category_id))>0))
            {  
                $sortParam['category_id'] = $request->category_id;
            }
            if((isset($request->location) && count(array_filter($request->location))>0))
            { 
                $sortParam['location'] = $request->location;
            }
            if(isset($request->sortBy) && !empty($request->sortBy))
            {
                if($request->sortBy == 'popular')
                {
                    $sortParam['sortBy'] = 'popular';
                }
                elseif($request->sortBy == 'recentlyAdded')
                {
                    $sortParam['sortBy'] = 'recentlyAdded';
                }
                elseif($request->sortBy == 'nearMe' && isset($request->latitude) && !empty ($request->latitude) && isset($request->longitude) && !empty ($request->longitude))
                {
                    $sortParam['sortBy'] = $request->sortBy;
                    $sortParam['latitude'] = $request->latitude;
                    $sortParam['longitude'] = $request->longitude;
                }
            }
            if(isset($request->myInvestment) && $request->myInvestment == 1)
            {
                $sortParam['user_id'] = Auth::id();
            }
            $investmentIdeasListing = $this->objInvestmentIdeas->getAll($sortParam);
            Cache::forget('investmentDetail');
            if($investmentIdeasListing)
            {

                if(count($investmentIdeasListing) > 0)
                {   
                    $outputArray['status'] = 1;
                    $outputArray['message'] = trans('apimessages.investment_ideas_fetched_successfully');
                    $statusCode = 200;
                    $filters = [];
                    if(!(isset($sortParam['limit'])) && $investmentIdeasListing->count() < Config::get('constant.API_RECORD_PER_PAGE'))
                    {
                        $outputArray['loadMore'] = 0;
                    }
                    elseif(isset($sortParam['limit']) && $investmentIdeasListing->count() < $sortParam['limit'])
                    {
                        $outputArray['loadMore'] = 0;
                    }
                    else{
                       
                        $offset = Helpers::getOffset($pageNo+1);
                       
                        $filters['offset'] = $offset;
                        $investmentIdeasCount = $this->objInvestmentIdeas->getAll($filters);
                        $outputArray['loadMore'] = (count($investmentIdeasCount) > 0) ? 1 : 0 ;
                    }    
                    $investmentFilters = [];
                    $investmentFilters = $filters;
                    if(isset($request->myInvestment) && $request->myInvestment == 1)
                    {
                        $investmentFilters['user_id'] = Auth::id();
                    }
                    unset($investmentFilters['offset']);
                    $outputArray['investment_count'] = $this->objInvestmentIdeas->getAll($investmentFilters)->count();
                    
                    $outputArray['data'] = array();                    
                    $i = 0;
                    foreach ($investmentIdeasListing as $investmentIdeasKey => $investmentIdeasValue)
                    {
                        $outputArray['data'][$i]['id'] = $investmentIdeasValue->id;
                        $outputArray['data'][$i]['creator_name'] = (isset($investmentIdeasValue->getUsersDetails) && !empty($investmentIdeasValue->getUsersDetails->name)) ? $investmentIdeasValue->getUsersDetails->name : '';
                        
                        $creatorProfilePicPath = '';
                        if(isset($investmentIdeasValue->getUsersDetails) && !empty($investmentIdeasValue->getUsersDetails->profile_pic))
                        {
                            $creatorProfilePic = $investmentIdeasValue->getUsersDetails->profile_pic;
                            $creatorProfilePicPath = $this->userThumbnailImagePath.$creatorProfilePic;
                        }
                        $outputArray['data'][$i]['creator_profile_pic'] = (file_exists($creatorProfilePicPath)) ? url($creatorProfilePicPath) : '';
                        
                        $outputArray['data'][$i]['category_id'] = (isset($investmentIdeasValue->getCategoryDetails) && !empty($investmentIdeasValue->getCategoryDetails->id)) ? $investmentIdeasValue->getCategoryDetails->id : '';
                        $outputArray['data'][$i]['category_name'] = (isset($investmentIdeasValue->getCategoryDetails) && !empty($investmentIdeasValue->getCategoryDetails->name)) ? $investmentIdeasValue->getCategoryDetails->name : '';
                        $outputArray['data'][$i]['category_slug'] = (isset($investmentIdeasValue->getCategoryDetails) && !empty($investmentIdeasValue->getCategoryDetails->category_slug)) ? $investmentIdeasValue->getCategoryDetails->category_slug : '';
                        
                        $outputArray['data'][$i]['title'] = $investmentIdeasValue->title;
                        $outputArray['data'][$i]['title_slug'] = (!empty($investmentIdeasValue->title_slug)) ? $investmentIdeasValue->title_slug : '';
                        $outputArray['data'][$i]['descriptions'] = $investmentIdeasValue->description;
                        $outputArray['data'][$i]['investment_amount_start'] = $investmentIdeasValue->investment_amount_start;
                        $outputArray['data'][$i]['investment_amount_end'] = $investmentIdeasValue->investment_amount_end;
                        $outputArray['data'][$i]['project_duration'] = $investmentIdeasValue->project_duration;
                        $outputArray['data'][$i]['member_name'] = $investmentIdeasValue->member_name;
                        $outputArray['data'][$i]['member_email'] = $investmentIdeasValue->member_email;
                        $outputArray['data'][$i]['member_phone'] = $investmentIdeasValue->member_phone;
                        $outputArray['data'][$i]['offering_percent'] = $investmentIdeasValue->offering_percent;
                        $outputArray['data'][$i]['location'] = $investmentIdeasValue->location;
                        $outputArray['data'][$i]['city'] = $investmentIdeasValue->city;
                        
                        $outputArray['data'][$i]['file_images'] =array();
                        if(isset($investmentIdeasValue->investmentIdeasImage) && !empty($investmentIdeasValue->investmentIdeasImage->file_name) && $investmentIdeasValue->investmentIdeasImage->file_type == 1)
                        {
                           $fileImgName = $investmentIdeasValue->investmentIdeasImage->file_name;

                            $investmentImage = ((isset($fileImgName) && !empty($fileImgName)) && Storage::size(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH') .$fileImgName) > 0) ? Storage::url(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH') . $fileImgName) : url(Config::get('constant.DEFAULT_IMAGE'));

                            $outputArray['data'][$i]['file_images'] =  $investmentImage;
                        }
                        else
                        {
                            $outputArray['data'][$i]['file_images'] =  url($this->catgoryTempImage);
                        }
                        $i++;
                    }
                }
                else
                {
                    $outputArray['status'] = 1;
                    $outputArray['message'] = trans('apimessages.norecordsfound');
                    $statusCode = 200;
                    $outputArray['data'] = array();
                }
            }
            else
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
            return response()->json($outputArray, $statusCode);
        } catch (Exception $e) {
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
    }

    public function addInvestmentIdea(Request $request)
    {
        $outputArray = [];
        $data = [];
        $statusCode = 400;
        $requestData = $request->all();
        try 
        {
            DB::beginTransaction();            
            $validator = Validator::make($request->all(), 
                [
                    'file_docs.*' => 'mimes:pdf,xlsx,xls,csv,doc,docx,txt,pptx,ppt|max:52400',
                    'file_images.*' => 'mimes:jpeg,jpg,bmp,png,gif|max:52400',
                    'user_id' => 'required',
                    'category_id' => 'required',
                    'title' => 'required',
                    'description'  =>  'required',
                    'investment_amount_start'  =>  'required',
                    'investment_amount_end'  =>  'required',
                    'project_duration'  =>  'required',
                    'member_name'  =>  'required',
                    'member_email'  =>  'required|email',
                    'member_phone'  =>  'required|numeric',
                    'offering_percent' => 'required',
                ]
            );
            
            if ($validator->fails()) 
            {
                DB::rollback(); 
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray,$statusCode);
            }
            else
            {   
                $data['user_id'] = $requestData['user_id'];
                $data['category_id'] = $requestData['category_id'];
                $data['title'] = trim($requestData['title']);
                $titleSlug = (!empty($data['title']) &&  $data['title'] != '') ? Helpers::getSlug($data['title']) : NULL;
                $data['title_slug'] = $titleSlug;
                $data['description'] = $requestData['description'];
                $data['investment_amount_start'] = $requestData['investment_amount_start'];
                $data['investment_amount_end'] = $requestData['investment_amount_end'];
                $data['project_duration'] = $requestData['project_duration'];
                $data['member_name'] = $requestData['member_name'];
                $data['member_email'] = $requestData['member_email'];
                $data['member_phone'] = $requestData['member_phone'];
                $data['offering_percent'] = $requestData['offering_percent'];
                
                $data['id'] = (isset($requestData['id']) && $requestData['id'] > 0) ? $requestData['id'] : '';
                $response = $this->objInvestmentIdeas->insertUpdate($data);
                Cache::forget('investmentDetail');
                if($response)
                {
                    $investmentId = (isset($requestData['id']) && $requestData['id'] > 0) ? $requestData['id'] : $response->id;
                    if (Input::file('file_images')) 
                    {   
                        $data['file_images'] = array();
                        $fileImagesArray = Input::file('file_images');                       
                        if (isset($fileImagesArray) && count($fileImagesArray) > 0 && !empty($fileImagesArray) ) 
                        {   
                            $i= 0;
                            foreach($fileImagesArray as $fileImageKey => $fileImageValue)
                            {
                                $fileImgName = 'investment_image_' . str_random(10). '.'. $fileImageValue->getClientOriginalExtension();
                                $pathOriginal = public_path($this->investmentIdeasFileImagesPath . $fileImgName);
                                Image::make($fileImageValue->getRealPath())->save($pathOriginal);

                                //Uploading on AWS
                                //$originalImage = Helpers::addFileToStorage($fileImgName, $this->investmentIdeasFileImagesPath, $pathOriginal, "s3");
                               
                                //Deleting Local Files
                             //   \File::delete($this->investmentIdeasFileImagesPath . $fileImgName);
                                                                
                                InvestmentIdeasFiles::firstOrCreate(['investment_id' => $investmentId , 'file_type' => 1, 'file_name' => $fileImgName]);
                                $data['file_images'][$i] = $fileImgName;
                                $i++;
                            }
                        }
                    }
                    if(isset($requestData['file_videos']) && count($requestData['file_videos']) > 0)
                    {
                        $i= 0;
                        foreach($requestData['file_videos'] as $fileVideoKey => $fileVideoValue)
                        {
                            $fileVideoUrl = (!empty($fileVideoValue)) ? $fileVideoValue : '';
                            InvestmentIdeasFiles::firstOrCreate(['investment_id' => $investmentId , 'file_type' => 2, 'file_name' => $fileVideoUrl]);
                            $data['file_videos'][$i]['video_url'] = $fileVideoUrl;
                            $i++;
                        }
                    }
                    if (Input::file('file_docs')) 
                    {   
                        $data['file_docs'] = array();
                        $fileDocsArray = Input::file('file_docs');                       
                        if (isset($fileDocsArray) && count($fileDocsArray) > 0 && !empty($fileDocsArray)) 
                        {   
                            $i= 0;
                            foreach($fileDocsArray as $fileDocKey => $fileDocValue)
                            {
                                $fileDocName = 'investment_docs_' . str_random(10) . '.' . $fileDocValue->getClientOriginalExtension();
                                $pathOriginal = public_path($this->investmentIdeasFileDocsPath . $fileDocName);
                                $docMoveData = $fileDocValue->move($this->investmentIdeasFileDocsPath, $fileDocName);

                                //Uploading on AWS
                                // $originalImage = Helpers::addFileToStorage($fileDocName, $this->investmentIdeasFileDocsPath, $pathOriginal, "s3");
                               
                                //Deleting Local Files
                                // \File::delete($this->investmentIdeasFileDocsPath . $fileDocName);

                                InvestmentIdeasFiles::firstOrCreate(['investment_id' => $investmentId , 'file_type' => 3, 'file_name' => $fileDocName]);
                                $data['file_docs'][$i] = $fileDocName;
                                $i++;
                            }
                        }
                    }  
                    DB::commit();
                    $outputArray['status'] = 1;
                    $outputArray['message'] =  trans('apimessages.investment_ideas_added_successfully');
                    $statusCode = 200;
                }
                else
                {
                    DB::rollback();
                    $outputArray['status'] = 0;
                    $outputArray['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 400;
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }

    public function deleteInvestmentIdea(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try 
        {
            $validator = Validator::make($request->all(), [
                'idea_id' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while deleteInvestmentIdea', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $investmentIdeaData = $this->objInvestmentIdeas->find($requestData['idea_id']);
                
                if($investmentIdeaData)
                {
                    $investmentIdeaFiles =  $investmentIdeaData->investmentIdeasFiles;
                   
                    $investmentIdeaData->delete();

                    if(!empty($investmentIdeaFiles))
                    {
                        foreach($investmentIdeaFiles as $file)
                        {
                            if($file['type'] == 1)
                            {
                                $originalImageDelete = Helpers::deleteFileToStorage($file['file_name'], $this->investmentIdeasFileImagesPath, "s3");
                            }
                            if($file['type'] == 2)
                            {
                                $originalVideoDelete = Helpers::deleteFileToStorage($file['file_name'], $this->investmentIdeasFileVideosPath, "s3");
                            }
                            if($file['type'] == 3)
                            {
                                $originalDocumentDelete = Helpers::deleteFileToStorage($file['file_name'], $this->investmentIdeasFileDocsPath, "s3");
                            }
                            
                        }
                    }
                    $this->log->info('API delete investment Idea successfully', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.investment_idea_deleted_success');
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while delete investment Idea', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.invalid_owner_id');
                    $statusCode = 200;
                }
            }            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while delete investment Idea', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
    *  Get getInvestmentInterestById
    */
    public function getInvestmentInterestById(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        
        try 
        {
            $validator = Validator::make($request->all(), [
                'interest_id' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while get Investment Interest By Id', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $interestData = $this->objInvestmentIdeasInterest->find($requestData['interest_id']);
                $listArray = [];
                if(count($interestData) > 0)
                {
                        
                    $listArray['id'] = $interestData->id;
                    $listArray['idea_id'] = $interestData->idea_id;
                    $listArray['user_id'] = $interestData->user_id;
                    $listArray['description'] = $interestData->description;
                    $listArray['username'] = (isset($interestData->getUsers->name))?$interestData->getUsers->name:'';
                    
                    $this->log->info('API get investment Interest successfully', array('login_user_id' => Auth::id()));  
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.get_investment_interest');
                    $responseData['data'] =  $listArray;
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while getting investment Interest by Id', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.norecordsfound');
                    $responseData['data'] =  $listArray;
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getting investment Interest by Id', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);    
    }

    /**
    *  Get getAllInvestmentInterest
    */
    public function getAllInvestmentInterest(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        
        try 
        {
            $interestData = $this->objInvestmentIdeasInterest->getAll();
            $mainArray = [];
            if(count($interestData) > 0)
            {
                foreach($interestData as $interest)
                {
                    $listArray['id'] = $interest->id;
                    $listArray['idea_id'] = $interest->idea_id;
                    $listArray['user_id'] = $interest->user_id;
                    $listArray['description'] = $interest->description;
                    $listArray['username'] = (isset($interest->getUsers->name))?$interest->getUsers->name:'';
                    $mainArray[] = $listArray;
                }    
                
                $this->log->info('API get  all investment Interest successfully', array('login_user_id' => Auth::id()));   
                $responseData['status'] = 1;
                $responseData['message'] =  trans('apimessages.get_all_investment_interest');
                $responseData['data'] =  $mainArray;
                $statusCode = 200;
            }
            else
            {
                $this->log->error('API something went wrong while getting all investment Interests', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = trans('apimessages.norecordsfound');
                $responseData['data'] =  $mainArray;
                $statusCode = 200;
            }
            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getting all investment Interests', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);    
    }

    /**
     * Save InvestmentInterest
     */
    public function saveInvestmentInterest(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try 
        {
            $validator = Validator::make($request->all(), [
                'idea_id' => 'required'
               
            ]);
            if ($validator->fails()) 
            {
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $requestData['user_id'] = Auth::id();
                $response = $this->objInvestmentIdeasInterest->insertUpdate($requestData);

                if($response)
                {
                    $data = $this->objInvestmentIdeasInterest->find($response->id);

                    $listArray['id'] = $data->id;
                    $listArray['idea_id'] = $data->idea_id;
                    $listArray['user_id'] = $data->user_id;
                    $listArray['description'] = $data->description;
                    $listArray['username'] = (isset($data->getUsers->name))?$data->getUsers->name:'';

                    $this->log->info('API save investment Interest successfully', array('login_user_id' => Auth::id()));  
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.save_investment_interest');
                    $responseData['data'] =  $listArray;
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while save investment Interest', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $responseData['data'] = [];
                    $statusCode = 200;
                }
            }            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while save investment Interest', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
    *  Get getMyInvestmentInterest
    */
    public function getMyInvestmentInterest(Request $request)
    {
        $outputArray = [];
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        $price_range = (isset($request->price_range) && !empty($request->price_range)) ? $request->price_range : '';
        try 
        {
            $offset = Helpers::getOffset($pageNo);
            $sortParam = [];
            $sortParam['offset'] = $offset;
            
            $idea_ids = $this->objInvestmentIdeasInterest->getAll(array('user_id' => Auth::id()));
            $idArray = []; 
            if(!empty($idea_ids))
            {
                foreach ($idea_ids as $key => $value) {
                    $idArray[] = $value['idea_id'];
                }
                
            }
            $sortParam['ids'] = $idArray;
            $investmentIdeasListing = $this->objInvestmentIdeas->getAll($sortParam);
            Cache::forget('investmentDetail');
            if($investmentIdeasListing)
            {
                if(count($investmentIdeasListing) > 0)
                {
                    $outputArray['status'] = 1;
                    $outputArray['message'] = trans('apimessages.get_my_investment_interest');
                    $statusCode = 200;
                    if($investmentIdeasListing->count() < Config::get('constant.API_RECORD_PER_PAGE'))
                    {
                        $outputArray['loadMore'] = 0;
                        $outputArray['investment_count'] = count($investmentIdeasListing);
                    }else{
                        $offset = Helpers::getOffset($pageNo+1);
                        $filters = [];
                        $filters['offset'] = $offset;
                        $investmentIdeasCount = $this->objInvestmentIdeas->getAll($filters);
                        $outputArray['loadMore'] = (count($investmentIdeasCount) > 0) ? 1 : 0 ;
                        $outputArray['investment_count'] = count($investmentIdeasCount);
                    }    
                    
                    $outputArray['data'] = array();                    
                    $i = 0;
                    foreach ($investmentIdeasListing as $investmentIdeasKey => $investmentIdeasValue)
                    {
                        $outputArray['data'][$i]['id'] = $investmentIdeasValue->id;
                        $outputArray['data'][$i]['creator_name'] = (isset($investmentIdeasValue->getUsersDetails) && !empty($investmentIdeasValue->getUsersDetails->name)) ? $investmentIdeasValue->getUsersDetails->name : '';
                        
                        $creatorProfilePicPath = '';
                        if(isset($investmentIdeasValue->getUsersDetails) && !empty($investmentIdeasValue->getUsersDetails->profile_pic))
                        {
                            $creatorProfilePic = $investmentIdeasValue->getUsersDetails->profile_pic;
                            $creatorProfilePicPath = $this->userThumbnailImagePath.$creatorProfilePic;
                        }
                        $outputArray['data'][$i]['creator_profile_pic'] = (file_exists($creatorProfilePicPath)) ? url($creatorProfilePicPath) : '';
                        
                        $outputArray['data'][$i]['category_id'] = (isset($investmentIdeasValue->getCategoryDetails) && !empty($investmentIdeasValue->getCategoryDetails->id)) ? $investmentIdeasValue->getCategoryDetails->id : '';
                        $outputArray['data'][$i]['category_name'] = (isset($investmentIdeasValue->getCategoryDetails) && !empty($investmentIdeasValue->getCategoryDetails->name)) ? $investmentIdeasValue->getCategoryDetails->name : '';
                        $outputArray['data'][$i]['category_slug'] = (isset($investmentIdeasValue->getCategoryDetails) && !empty($investmentIdeasValue->getCategoryDetails->category_slug)) ? $investmentIdeasValue->getCategoryDetails->category_slug : '';
                        
                        $outputArray['data'][$i]['title'] = $investmentIdeasValue->title;
                        $outputArray['data'][$i]['title_slug'] = (!empty($investmentIdeasValue->title_slug)) ? $investmentIdeasValue->title_slug : '';
                        $outputArray['data'][$i]['descriptions'] = $investmentIdeasValue->description;
                        $outputArray['data'][$i]['investment_amount_start'] = $investmentIdeasValue->investment_amount_start;
                        $outputArray['data'][$i]['investment_amount_end'] = $investmentIdeasValue->investment_amount_end;
                        $outputArray['data'][$i]['project_duration'] = $investmentIdeasValue->project_duration;
                        $outputArray['data'][$i]['member_name'] = $investmentIdeasValue->member_name;
                        $outputArray['data'][$i]['member_email'] = $investmentIdeasValue->member_email;
                        $outputArray['data'][$i]['member_phone'] = $investmentIdeasValue->member_phone;
                        $outputArray['data'][$i]['offering_percent'] = $investmentIdeasValue->offering_percent;
                        $outputArray['data'][$i]['location'] = $investmentIdeasValue->location;
                        
                        $outputArray['data'][$i]['file_images'] =array();
                        if(isset($investmentIdeasValue->investmentIdeasImage) && !empty($investmentIdeasValue->investmentIdeasImage->file_name) && $investmentIdeasValue->investmentIdeasImage->file_type == 1)
                        {
                            $fileImgName = $investmentIdeasValue->investmentIdeasImage->file_name;

                            $investmentImage = ((isset($fileImgName) && !empty($fileImgName)) && Storage::size(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH') .$fileImgName) > 0) ? Storage::url(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH') . $fileImgName) : url(Config::get('constant.DEFAULT_IMAGE'));

                            $outputArray['data'][$i]['file_images'] =  $investmentImage;
                           
                        }
                        else
                        {
                            $outputArray['data'][$i]['file_images'] =  url($this->catgoryTempImage);
                        }
                        $i++;
                    }
                }
                else
                {
                    $outputArray['status'] = 1;
                    $outputArray['message'] = trans('apimessages.norecordsfound');
                    $statusCode = 200;
                    $outputArray['data'] = array();
                }
            }
            else
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
            }
            return response()->json($outputArray, $statusCode);
        } catch (Exception $e) {
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        } 
    }

    public function getInvestmentFilters()
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        
        try 
        {
            $investmentData = $this->objInvestmentIdeas->getAll();
            $categories = Category::whereHas('investment_ideas')->where('parent', 0)->select(['name', 'id'])->get();
            
            if ($investmentData) 
            {
                $categoryArray = ($categories) ? $categories->toArray() : [];
                $locationArray = [];
                foreach($investmentData as $investment)
                {
                    if($investment->city != '')
                        $locationArray[] = $investment->city;
                }
                $cityArray = [];
                foreach(array_unique($locationArray) as $location)
                {
                    $cityArray[] = $location;
                }
                
                $minMaxAmount = $this->objInvestmentIdeas->getInvestmentMaxMinAmountFilters();
                $max_amount = (count($minMaxAmount) > 0)? $minMaxAmount['max_amount'] : 0;
                $min_amount = (count($minMaxAmount) > 0)? $minMaxAmount['min_amount'] : 0;

                $this->log->info('API investment filter get successfully', array('login_user_id' => Auth::id()));
                $responseData['status'] = 1;
                $responseData['message'] =  trans('apimessages.get_investment_filter');
                $responseData['data'] = ['min_amount'=>$min_amount,'max_amount'=>$max_amount,'categories'=>$categoryArray,'locations'=>$cityArray];
                $statusCode = 200;
            }
            else
            {
                $this->log->error('API something went wrong while get investment filters', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = trans('apimessages.default_error_msg');
                $statusCode = 200;
            }
            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while get investment filters', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);    
    }


}
