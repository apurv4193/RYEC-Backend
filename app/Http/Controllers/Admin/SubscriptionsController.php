<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\SubscriptionRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\SubscriptionPlan;
use App\MembershipRequest;
use App\Http\Controllers\Controller;
use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Image;
use File;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Helpers;

class SubscriptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objSubscription = new SubscriptionPlan();
        $this->SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH = Config::get('constant.SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH');
        $this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH = Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH');
        $this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_WIDTH');
        $this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_HEIGHT');
    }

    public function index()
    {
        $subscriptionList = $this->objSubscription->getAll();
        return view('Admin.ListSubscriptions', compact('subscriptionList'));
    }

    public function add()
    {
        return view('Admin.EditSubscriptions');
    }

    public function save(SubscriptionRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);
        
        if (Input::file('logo')) 
        {  
            $logo = Input::file('logo'); 

            if (!empty($logo) && count($logo) > 0) 
            {
                $fileName = 'subscription_plan_logo_' . uniqid() . '.' . $logo->getClientOriginalExtension();

                $pathOriginal = public_path($this->SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH . $fileName);
                $pathThumb = public_path($this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH . $fileName);

                Image::make($logo->getRealPath())->save($pathOriginal);
                Image::make($logo->getRealPath())->resize($this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_WIDTH, $this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);
                
                if(isset($postData['old_logo']) && $postData['old_logo'] != '')
                {
                    $originalImageDelete = Helpers::deleteFileToStorage($postData['old_logo'], $this->SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($postData['old_logo'], $this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH, "s3");
                }
                //Uploading on AWS
                $originalImage = Helpers::addFileToStorage($fileName, $this->SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                $thumbImage = Helpers::addFileToStorage($fileName, $this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                //Deleting Local Files
                \File::delete($this->SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH . $fileName);
                \File::delete($this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH . $fileName);

                $postData['logo'] = $fileName;
            }
        }

        $response = $this->objSubscription->insertUpdate($postData);
        if ($response) {
            return Redirect::to("admin/subscriptions")->with('success', trans('labels.subscriptionsuccessmsg'));
        } else {
            return Redirect::to("admin/subscriptions")->with('error', trans('labels.subscriptionerrormsg'));
        }
    }

    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = $this->objSubscription->find($id);
            if($data) {
                return view('Admin.EditSubscriptions', compact('data'));
            } else {
                return Redirect::to("admin/subscriptions")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            return view('errors.404');
        }
        
    }

    public function delete($id)
    {
        $data = $this->objSubscription->find($id);
        $response = $data->delete();
        if ($response) {
            return Redirect::to("admin/subscriptions")->with('success', trans('labels.subscriptiondeletesuccessmsg'));
        }
    }

    public function membershipRequest()
    {
        $postData = Input::get();

        if((isset($postData['status']) && $postData['status'] != ''))
        {   
            $membershipRequests = MembershipRequest::where('status',$postData['status'])->orderBy('id', 'DESC')->get();
        }
        else
        {
            $membershipRequests = MembershipRequest::where('status','<>',2)->orderBy('id', 'DESC')->get();
        }
        
        return view('Admin.ListMembershipRequests',compact('membershipRequests','postData'));
    }

   
    public function membershipApprove($id,$status)
    {
        try {
                $id = Crypt::decrypt($id);
                $membershipRequests = MembershipRequest::find($id);
                $membershipRequests->status = $status;
                $membershipRequests->save();   
              
                return Redirect::to("admin/membershiprequest?status=".$status)->with('success', trans('labels.membershipupdatesuccessfully'));
        } catch (DecryptException $e) {
            return view('errors.404');
        }
    }

    public function membershipReject()
    {
        try {
                $postData = Input::get();
                
                if(isset($postData))
                {
                    $id = $postData['request_id'];
                    $status = $postData['status'];
                    $reasons = $postData['reasons'];
                }
                
                $membershipRequests = MembershipRequest::find($id);
                $membershipRequests->status = $status;
                $membershipRequests->reasons = $reasons;
                $membershipRequests->save();   
                
                return Redirect::to("admin/membershiprequest?status=".$status)->with('success', trans('labels.membershipupdatesuccessfully'));
        } catch (DecryptException $e) {
            return view('errors.404');
        }
    }

}
