<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use App\Http\Requests\NewsletterRequest;
use App\Newsletter;
use Config;
use Redirect;
use Helpers;
use Input;
use File;
use Image;
use Crypt;
use Response;
use Carbon\Carbon;
use Mail;
use Session;
use Illuminate\Contracts\Encryption\DecryptException;

class NewsletterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objNewsletter = new Newsletter();
        $this->controller = 'NewsletterController';          
    }

    public function index()
    {
        $newsletterList = $this->objNewsletter->getAll();
        return view('Admin.ListNewsletter', compact('newsletterList'));
    }

    public function create()
    {
        $data = [];
        return view('Admin.EditNewsletter', compact('data'));
    }

    public function edit($enrid)
    {
        try {
            $id = Crypt::decrypt($enrid);
            $data = Newsletter::find($id);        
            if($data) {
                return view('Admin.EditNewsletter', compact('data'));
            } else {
                return Redirect::to("admin/newsletter")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            return view('errors.404');
        }
        
    }

    public function save(NewsletterRequest $request)
    {   
        $requestData = [];
        $requestData['id'] = e(input::get('id'));
        $id = $requestData['id'];
        $requestData['title'] = e(input::get('title'));
        $requestData['body'] = e(input::get('body'));
        $requestData['publish_status'] = e(input::get('publish_status'));
        $notifySubscribers = e(input::get('notify_subscribers'));
        if(isset($request->save_send) && $request->save_send)
        {
            $notifySubscribers = 1;
        }
        $requestData['notify_subscribers'] = (isset($notifySubscribers) && !empty($notifySubscribers)) ? $notifySubscribers : 0;
        $requestData['author'] = (isset(Auth::user()->id) && !empty(Auth::user()->id)) ? Auth::user()->id : 0;  
       
        $response = $this->objNewsletter->insertUpdate($requestData);
       
        if ($response) 
        {
            return Redirect::to("admin/newsletter")->with('success', trans('labels.newslettersuccessmsg'));
        } else {
            return Redirect::to("admin/newsletter")->with('error', trans('labels.saveerrormsg'));
        }
    }
    
    public function updateNotifySubscriberStatus($enrid) 
    {
        $id = Crypt::decrypt($enrid);
        $newsLetterData = Newsletter::find($id);
        $newsLetterData->notify_subscribers = 1;
        $response = $newsLetterData->save();
        if ($response)
        {
            return Redirect::to("admin/newsletter")->with('success', trans('labels.newsletternotifysubscriberssuccessmsg'));
        }
        else
        {
            return Redirect::to("admin/newsletter")->with('success', trans('labels.common_error'));
        }   
    }

    public function delete($enrid)
    {
        $id = Crypt::decrypt($enrid);
        $newsLetterData = Newsletter::find($id);
        $response = $newsLetterData->delete();
        if ($response)
        {
            return Redirect::to("admin/newsletter")->with('success', trans('labels.newsletterdeletesuccessmsg'));
        }
        else
        {
            return Redirect::to("admin/newsletter")->with('success', trans('labels.common_error'));
        }        
    }

}
