<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\Membership;
use App\SubscriptionPlan;
use App\Business;
use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Image;
use Helpers;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MembershipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objMembership = new Membership();
        $this->objSubscriptionPlan = new SubscriptionPlan();

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
            $this->log->info('Admin membership listing page', array('admin_user_id' =>  Auth::id(),'business_id' => $businessId));
            return view('Admin.ListMembership', compact('businessDetails','businessId'));
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while membership listing page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'error' => $e->getMessage()));
            return view('errors.404');
        }
    }

   public function add($businessId)
    {
        try 
        {
            $businessId = Crypt::decrypt($businessId);
            $businessDetails = Business::find($businessId);
            $planList = $this->objSubscriptionPlan->getAll();
            $this->log->info('Admin membership add page', array('admin_user_id' =>  Auth::id(),'business_id' => $businessId));
            return view('Admin.EditMembership',compact('businessId','businessDetails','planList'));
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while membership add page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'error' => $e->getMessage()));
            return view('errors.404');
        }
    }

    public function edit($id)
    {
        try 
        {
            $id = Crypt::decrypt($id);
            $data = $this->objMembership->find($id);
            $planList = $this->objSubscriptionPlan->getAll();
            $businessId = $data->business_id;
            $businessDetails = Business::find($businessId);
           
            if($data) {
                $this->log->info('Admin membership edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'membership_id' => $id));
                return view('Admin.EditMembership', compact('businessId','parentCategories','categories','data','productImages','businessDetails','planList'));
            } else {
                $this->log->error('Admin something went wrong while membership edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'membership_id' => $id));
                return Redirect::to("admin/user/business/membership/".Crypt::encrypt($businessId))->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while membership edit page', array('admin_user_id' =>  Auth::id(), 'business_id' => $businessId, 'membership_id' => $id, 'error' => $e->getMessage()));
            return view('errors.404');
        }
        
    }

    public function save(MembershipRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);
        
        $response = $this->objMembership->insertUpdate($postData);
        
        $membershipId = ($postData['id'] == 0 && isset($response->id) && $response->id > 0) ? $response->id : $postData['id'];

        if ($response) {
            $this->log->info('Admin membership added/updated successfully', array('admin_user_id' =>  Auth::id(), 'business_id' => $postData['business_id'], 'membership_id' => $membershipId));
            return Redirect::to("admin/user/business/membership/".Crypt::encrypt($postData['business_id']))->with('success', trans('labels.membershipsuccessmsg'));
        } else {
            $this->log->error('Admin something went wrong while adding/updating membership', array('admin_user_id' =>  Auth::id(), 'business_id' => $postData['business_id'], 'membership_id' => $membershipId));
            return Redirect::to("admin/user/business/membership/".Crypt::encrypt($postData['business_id']))->with('error', trans('labels.membershiperrormsg'));
        }
    }

    public function delete($id)
    {
        $id = Crypt::decrypt($id);
        $data = $this->objMembership->find($id);
       
        $response = $data->delete();
        if ($response) 
        {
            $this->log->info('Admin business membership deleted successfully', array('admin_user_id' =>  Auth::id(), 'business_id' => $data->business_id, 'membership_id' => $id));
            return Redirect::to("admin/user/business/membership/".Crypt::encrypt($data->business_id))->with('success', trans('labels.membershipdeletesuccessmsg'));
        }
    }

}
