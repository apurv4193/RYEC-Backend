<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StateRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\State;
use App\Country;
use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class StateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objState = new State();
        $this->objCountry = new Country();
    }

    public function index()
    {
        $stateList = $this->objState->getAll();
        return view('Admin.ListState', compact('stateList'));
    }

    public function add()
    {
        $data = [];
        $countries = $this->objCountry->getAll();
        return view('Admin.EditState', compact('data','countries'));
    }

    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = $this->objState->find($id);
            $countries = $this->objCountry->getAll();
            if($data) {
                return view('Admin.EditState', compact('data','countries'));
            } else {
                return Redirect::to("admin/state")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            return view('errors.404');
        }
        
    }

    public function save(StateRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);

        $stateName = $postData['name'];
        if(!empty($stateName))
        {
            //Formatted country name
            $formattedAddr = str_replace(' ','+',$stateName);
            //Send request and receive json data by stateName
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
        $response = $this->objState->insertUpdate($postData);
        if ($response) {
            return Redirect::to("admin/state")->with('success', trans('labels.statesuccessmsg'));
        } else {
            return Redirect::to("admin/state")->with('error', trans('labels.stateerrormsg'));
        }
    }

    public function delete($id)
    {
        $data = $this->objState->find($id);
        $response = $data->delete();
        if ($response) {
            return Redirect::to("admin/state")->with('success', trans('labels.statedeletesuccessmsg'));
        }
    }

}
