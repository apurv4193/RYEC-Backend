<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Input;
use Redirect;
use App\Business;
use App\Branding;
use App\Notification;
use Cache;
use Illuminate\Contracts\Encryption\DecryptException;
use JWTAuth;
use JWTAuthException;
use Validator;
use Helpers;
use Config;
use \stdClass;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objBusiness = new Business();

        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('business-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }

    public function index()
    {
        if (false){
            $membersForApproval = Cache::get('membersForApproval');
        } else {
            $filters = [
                'approved' => 0
            ];
            $membersForApproval = $this->objBusiness->getAll($filters);
            Cache::put('membersForApproval', $membersForApproval, 60);
        }

        // if(Auth::user()->agent_approved == 1)
        // {
        //    $this->log->error('Admin something went wrong while open dashboard', array('admin_user_id' =>  Auth::id(), 'error' => 'not allow to open Dashboard'));
        //     return view('errors.404');
        // }

        return view('Admin.Dashboard', compact('membersForApproval'));
    }

    public function getLogout()
    {
        Auth::logout();

        return Redirect::to('admin/login');
    }

    public function siteAnalytics()
    {
        return view('Admin.Analytics');
    }

    public function notifications()
    {
        return view('Admin.ListNotifications');
    }

    public function getPushNotification()
    {
        return view('Admin.TopicPushNotification');
    }

    public function sendPushNotification(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'title' => 'required',
                'description' => 'required'
            ]
        );
        if ($validator->fails())
        {
            return redirect()->back()->withErrors([$validator->messages()->all()[0]]);
        }
        $brandingDetail = Branding::first();
        if($brandingDetail && count($brandingDetail) > 0)
        {
            $type = '';
            $mainArray['id'] = $brandingDetail->id;
            $mainArray['name'] = ($brandingDetail->type == 1) ? url('images/branding_image.png') : $brandingDetail->name;
            if($brandingDetail->type == 1)
            {
                $type = 'image';
            }
            elseif($brandingDetail->type == 2)
            {
                $type = 'video';
                $videoId = '';
                if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $brandingDetail->name, $match))
                {
                    $videoId = $match[1];
                }
                $mainArray['videoId'] = $videoId;
            }
            else
            {
                $type = 'text';
            }
            $mainArray['branding_type'] = $type;
            $mainArray['type'] = '10';
            $mainArray['title'] = $request->title;
            $mainArray['message'] = $request->description;
            $token = "/topics/ryec";
//          $response = Helpers::pushNotificationForAndroid($token, $mainArray);
            $response = Helpers::topicPushNotification($token, $mainArray);
            if($response)
            {
                return redirect()->back()->with('success', 'Notification sent successfully');
            }
            else
            {
                return redirect()->back()->withErrors(['Invalid Notification']);
            }
        }
        else
        {
            return redirect()->back()->withErrors(['Record not found']);
        }
    }
}
