<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CityRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\State;
use App\City;
use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class CityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objCity = new City();
        $this->objState = new State();
    }

    public function index()
    {
        $cityList = $this->objCity->getAll();
        return view('Admin.ListCity', compact('cityList'));
    }

    public function add()
    {
        $data = [];
        $states = $this->objState->getAll();
        return view('Admin.EditCity', compact('data','states'));
    }

    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = $this->objCity->find($id);
            $states = $this->objState->getAll();
            if($data) {
                return view('Admin.EditCity', compact('data','states'));
            } else {
                return Redirect::to("admin/city")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            return view('errors.404');
        }
        
    }

    public function save(CityRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);

        $cityName = $postData['name'];
        if(!empty($cityName))
        {
            //Formatted country name
            $formattedAddr = str_replace(' ','+',$cityName);
            //Send request and receive json data by cityName
            $geocodeFromAddr = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false'); 
            $output = json_decode($geocodeFromAddr);
            //Get latitude and longitute from json data
            if(isset($output->{'results'}[0]) && !empty($output->{'results'}[0]))
            {
                $data['latitude']  = $output->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
                $data['longitude'] = $output->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
                //Return latitude and longitude of the given address
                if(!empty($data)){
                    $postData['latitude'] = $data['latitude'];
                    $postData['longitude'] = $data['longitude'];
                }
            }
        }
        $response = $this->objCity->insertUpdate($postData);
        if ($response) {
            return Redirect::to("admin/city")->with('success', trans('labels.citysuccessmsg'));
        } else {
            return Redirect::to("admin/city")->with('error', trans('labels.cityerrormsg'));
        }
    }

    public function delete($id)
    {
        $data = $this->objCity->find($id);
        $response = $data->delete();
        if ($response) {
            return Redirect::to("admin/city")->with('success', trans('labels.citydeletesuccessmsg'));
        }
    }

}
