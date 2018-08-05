<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\Country;
use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Image;
use Helpers;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objCountry = new Country();
        $this->COUNTRY_FLAG_IMAGE_PATH = Config::get('constant.COUNTRY_FLAG_IMAGE_PATH');
        $this->COUNTRY_FLAG_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.COUNTRY_FLAG_THUMBNAIL_IMAGE_WIDTH');
        $this->COUNTRY_FLAG_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.COUNTRY_FLAG_THUMBNAIL_IMAGE_HEIGHT');
    }

    public function index()
    {
        $countryList = $this->objCountry->getAll();
        return view('Admin.ListCountry', compact('countryList'));
    }

    public function add()
    {
        $data = [];
        return view('Admin.EditCountry', compact('data'));
    }

    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = $this->objCountry->find($id);
            if($data) {
                return view('Admin.EditCountry', compact('data'));
            } else {
                return Redirect::to("admin/country")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            return view('errors.404');
        }
        
    }

    public function save(CountryRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);

        $countryName = $postData['name'];
        if(!empty($countryName)){
            //Formatted country name
            $formattedAddr = str_replace(' ','+',$countryName);
            //Send request and receive json data by countryName
            $geocodeFromAddr = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false'); 
            $output = json_decode($geocodeFromAddr);
            if(isset($output->{'results'}[0]) && !empty($output->{'results'}[0]))
            {
            //Get latitude and longitute from json data
                $data['latitude']  = $output->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
                $data['longitude'] = $output->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
                //Return latitude and longitude of the given address
                if(!empty($data)){
                    $postData['latitude'] = $data['latitude'];
                    $postData['longitude'] = $data['longitude'];
                }
            }
        }

        // upload country flag
        if (Input::file('flag')) 
        {  
            $flag = Input::file('flag'); 

            if (!empty($flag) && count($flag) > 0) 
            {
                $fileName = 'country_flag_' . uniqid() . '.' . $flag->getClientOriginalExtension();
                
                $pathThumb = public_path($this->COUNTRY_FLAG_IMAGE_PATH . $fileName);

                Image::make($flag->getRealPath())->resize($this->COUNTRY_FLAG_THUMBNAIL_IMAGE_WIDTH, $this->COUNTRY_FLAG_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);
                
                $postData['flag'] = $fileName;

                if(isset($postData['old_flag']) && $postData['old_flag'] != '')
                {
                    $thumbImageDelete = Helpers::deleteFileToStorage($postData['old_flag'], $this->COUNTRY_FLAG_IMAGE_PATH, "s3");
                }

                //Uploading on AWS
                $thumbImage = Helpers::addFileToStorage($fileName, $this->COUNTRY_FLAG_IMAGE_PATH, $pathThumb, "s3");
                //Deleting Local Files
                \File::delete($this->COUNTRY_FLAG_IMAGE_PATH . $fileName);
            }
        }
        $response = $this->objCountry->insertUpdate($postData);
        if ($response) {
            return Redirect::to("admin/country")->with('success', trans('labels.countrysuccessmsg'));
        } else {
            return Redirect::to("admin/country")->with('error', trans('labels.countryerrormsg'));
        }
    }

    public function delete($id)
    {
        $data = $this->objCountry->find($id);
        $response = $data->delete();
        if ($response) {
            return Redirect::to("admin/country")->with('success', trans('labels.countrydeletesuccessmsg'));
        }
    }

}
