<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\InvestmentIdeasRequest;
use App\Http\Controllers\Controller;
use App\InvestmentIdeas;
use App\InvestmentIdeasFiles;
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
use Carbon\Carbon;
use Cache;
use Illuminate\Contracts\Encryption\DecryptException;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Category;
use App\City;

class InvestmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objInvestmentIdeas = new InvestmentIdeas();
        $this->objInvestmentIdeasFiles = new InvestmentIdeasFiles();
      
        $this->investmentIdeasFileImagesPath = Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH');
        $this->investmentIdeasFileVideosPath = Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_VIDEOS_PATH');
        $this->investmentIdeasFileDocsPath = Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_DOCS_PATH');
        
        $this->controller = 'InvestmentController';

        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('investment-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
        $this->objCategory = new Category();
        $this->objCity = new City();
    }

    public function index()
    {   
        Cache::forget('investmentDetail');
        $investmentDetail = $this->objInvestmentIdeas->getAll();
        // if (Cache::has('investmentDetail')){
        //     $investmentDetail = Cache::get('investmentDetail');
        // } else {
        //     $investmentDetail = $this->objInvestmentIdeas->getAll();
        //     Cache::put('investmentDetail', $investmentDetail, 60);
        // }

        return view('Admin.ListInvestmentIdeas', compact('investmentDetail'));
    }

    public function add()
    {
        $parentCategories = $this->objCategory->getAll(array('parent' => '0'));
        $cities =  $this->objCity->getAll();
        return view('Admin.EditInvestmentIdea',compact('parentCategories','cities'));
    }

    public function edit($id)
    {
        try {
            $cities =  $this->objCity->getAll();
            $parentCategories = $this->objCategory->getAll(array('parent' => '0'));
            $id = Crypt::decrypt($id);
            $data = $this->objInvestmentIdeas->find($id);
            
            if($data) {
                $this->log->info('Admin Investment opportunity edit page', array('admin_user_id' =>  Auth::id()));
                return view('Admin.EditInvestmentIdea', compact('data','parentCategories','cities'));
            } else {
                return Redirect::to("admin/investmentideas")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while Investment opportunity edit page', array('admin_user_id' =>  Auth::id(), 'error' => $e->getMessage()));
            return view('errors.404');
        }
    }

    public function save(InvestmentIdeasRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);

        if($postData['id'] == 0 || !isset($postData['id']))
        {
            $titleSlug = SlugService::createSlug(InvestmentIdeas::class, 'title_slug', $postData['title']);
            $postData['title_slug'] = (isset($titleSlug) && !empty($titleSlug)) ? $titleSlug : NULL;
        } 
        
        if(isset($postData['city']) && $postData['city'] != '')
        {
            $cityData = City::where('name',$postData['city'])->first();

            if(count($cityData) > 0)
            {
                $postData['latitude'] = $cityData->latitude;
                $postData['longitude'] = $cityData->longitude;
            }
        }
        
        $response = $this->objInvestmentIdeas->insertUpdate($postData);
        
        $investmentId = ($postData['id'] == 0 && isset($response->id) && $response->id > 0) ? $response->id : $postData['id'];
        if (Input::file()) 
        {  
            $investment_images = Input::file('investment_images');
            
            $imageArray = [];

            if (!empty($investment_images) && count($investment_images) > 0) 
            {   
                foreach($investment_images as $investment_image)
                {   
                    $fileName = 'investment_image' . uniqid() . '.' . $investment_image->getClientOriginalExtension();

                    $pathOriginal = public_path($this->investmentIdeasFileImagesPath . $fileName);
                    //$pathThumb = public_path($this->investmentIdeasFileImagesPath . $fileName);
                    $investment_image->getRealPath(); 

                    Image::make($investment_image->getRealPath())->save($pathOriginal);
                    //Image::make($investment_image->getRealPath())->resize(100, 100)->save($pathThumb);

                     //Uploading on AWS
                    $originalImage = Helpers::addFileToStorage($fileName, $this->investmentIdeasFileImagesPath, $pathOriginal, "s3"); 
                    //$thumbImage = Helpers::addFileToStorage($fileName, $this->PRODUCT_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                    //Deleting Local Files
                    \File::delete($this->investmentIdeasFileImagesPath. $fileName);
                    // \File::delete($this->PRODUCT_THUMBNAIL_IMAGE_PATH . $fileName);

                    InvestmentIdeasFiles::firstOrCreate(['investment_id' => $investmentId,'file_type'=>'1' , 'file_name' => $fileName]);

                }
            }

            $investment_docs = Input::file('investment_docs');

            $imageArray = [];

            if (!empty($investment_docs) && count($investment_docs) > 0) 
            {   
                foreach($investment_docs as $investment_doc)
                {   
                    $fileName = 'investment_doc' . uniqid() . '.' . $investment_doc->getClientOriginalExtension();
                    $pathOriginal = public_path($this->investmentIdeasFileDocsPath . $fileName);
                    //$pathThumb = public_path($this->investmentIdeasFileImagesPath . $fileName);
                    $investment_doc->getRealPath(); 

                    $docMoveData = $investment_doc->move($this->investmentIdeasFileDocsPath, $fileName);

                     //Uploading on AWS
                    $originalImage = Helpers::addFileToStorage($fileName, $this->investmentIdeasFileDocsPath,$pathOriginal, "s3");
                    //$thumbImage = Helpers::addFileToStorage($fileName, $this->PRODUCT_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                    //Deleting Local Files
                    \File::delete($this->investmentIdeasFileDocsPath . $fileName);
                    // \File::delete($this->PRODUCT_THUMBNAIL_IMAGE_PATH . $fileName);

                    InvestmentIdeasFiles::firstOrCreate(['investment_id' => $investmentId,'file_type'=>'3' , 'file_name' => $fileName]);

                }
            }
        }
        if ($response) {
            $this->log->info('Admin Investment opportunity added/updated successfully', array('admin_user_id' =>  Auth::id()));

            //insert/update investment videos
             
            if(isset($postData['add_investment_video']) && !empty($postData['add_investment_video']))
            {

                foreach(array_filter($postData['add_investment_video']) as $key=>$video)
                {   
                    $videoArray = [];
                    $videoArray['investment_id'] = $investmentId;
                    $videoArray['file_type'] = 2;
                    $videoArray['file_name'] = $video;
                   
                    $this->objInvestmentIdeasFiles->insertUpdate($videoArray);
                }
            }

            if(isset($postData['deleted_videos']) && !empty($postData['deleted_videos']))
            {
                foreach($postData['deleted_videos'] as $video)
                {
                    $data = $this->objInvestmentIdeasFiles->find($video);
                    $data->delete();
                }
            }

            if(isset($postData['update_investment_video']) && !empty($postData['update_investment_video']))
            {
                foreach($postData['update_investment_video'] as $key=>$video)
                {
                    $videoArray = [];
                    $videoArray['investment_id'] = $investmentId;
                    $videoArray['file_type'] = 2;
                    $videoArray['file_name'] = $video;
                    $videoArray['id'] = $postData['update_investment_id'][$key];
                    $this->objInvestmentIdeasFiles->insertUpdate($videoArray);
                }
            }

            return Redirect::to("admin/investmentideas")->with('success', trans('labels.investmentopportunitysuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while adding/updating Investment opportunity', array('admin_user_id' =>  Auth::id()));
            return Redirect::to("admin/investmentideas")->with('error', trans('labels.investmentopportunityerrormsg'));
        }
    }

    public function delete($id)
    {
        Cache::forget('investmentDetail');
    }

}
