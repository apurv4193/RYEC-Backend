<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Cms;
use App\SearchTerm;
use App\Branding;
use App\Http\Requests\CmsRequest;
use Auth;
use Input;
use Config;
use Redirect;
use Crypt;
use Image;
use File;
use DB;
use Illuminate\Contracts\Encryption\DecryptException;

class CmsController extends Controller
{
    public function __construct()
    { 
        $this->middleware('auth');
        $this->objCms = new Cms();
        $this->objSearchTerm = new SearchTerm();
        $this->objBranding = new Branding();
    }

    public function index()
    {
        $cmsList = $this->objCms->getAll();
        return view('Admin.ListCms', compact('cmsList'));
    }

    public function add()
    {
        $data = [];
        return view('Admin.EditCms', compact('data'));
    }

    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = $this->objCms->find($id);
            if($data) {
                return view('Admin.EditCms', compact('data'));
            } else {
                return Redirect::to("admin/cms")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            return view('errors.404');
        }
        
    }

    public function save(CmsRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);
        $response = $this->objCms->insertUpdate($postData);
        if ($response) {
            return Redirect::to("admin/cms")->with('success', trans('labels.cmssuccessmsg'));
        } else {
            return Redirect::to("admin/cms")->with('error', trans('labels.cmserrormsg'));
        }
    }

    public function delete($id)
    {
        $data = $this->objCms->find($id);
        $response = $data->delete();
        if ($response) {
            return Redirect::to("admin/cms")->with('success', trans('labels.cmsdeletesuccessmsg'));
        }
    }

    public function brandingImage()
    {
        $brandingDetail = $this->objBranding->first();
        return view('Admin.Branding',compact('brandingDetail'));
    }

    public function savebrandingImage()
    {
        $postData = Input::get();

        $brandingArray = [];

        if($postData['type'] == 1)
        {
            if (Input::file())
            {
                $file = Input::file('image');
                if (isset($file) && !empty($file))
                {
                    $fileName = 'branding_image.png';
                    $pathOriginal = public_path('images/'. $fileName);
                    Image::make($file->getRealPath())->save($pathOriginal);
                    $brandingArray['name'] = $fileName;
                    $brandingArray['type'] = 1;
                }
            }
        }

        if($postData['type'] == 2)
        {
            $brandingArray['name'] = $postData['video'];
            $brandingArray['type'] = 2; 
        }

        if($postData['type'] == 3)
        {
            $brandingArray['name'] = $postData['text'];
            $brandingArray['type'] = 3; 
        }

        $brandingDetail = $this->objBranding->first();
        if(count($brandingDetail) > 0)
        {
            $this->objBranding->where('id',$brandingDetail->id)->update($brandingArray);
        }
        else
        {
            $this->objBranding->create($brandingArray);
        }

        return Redirect::to('admin/branding')->with('success', trans('labels.brandingsavesuccessmsg'));
    }

    public function deletebrandingImage()
    {
        DB::table('branding')->truncate();

        if (file_exists('images/branding_image.png')) 
        {
           \File::delete('images/branding_image.png');
        }
        return Redirect::to('admin/branding')->with('success', trans('labels.brandingdeletesuccessmsg'));
    }

    public function getSearchTerm()
    {
        $searchTermList = $this->objSearchTerm->get();
        return view('Admin.ListSearchTerms',compact('searchTermList'));
    }
}
