<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Requests\BusinessRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\User;
use App\UserRegisterOTP;
use App\Business;
use App\UserRole;
use App\UserMetaData;
use App\AgentUser;
use App\Http\Controllers\Controller;
use Crypt;
use Helpers;
use Image;
use Illuminate\Contracts\Encryption\DecryptException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objUser = new User();
        $this->objUserMetaData = new UserMetaData();
        $this->objAgentUser = new AgentUser();
        $this->objUserRole = new UserRole();
        $this->objBusiness = new Business();
        $this->BUSINESS_BANNER_IMAGE_PATH = Config::get('constant.BUSINESS_BANNER_IMAGE_PATH');
        $this->USER_ORIGINAL_IMAGE_PATH = Config::get('constant.USER_ORIGINAL_IMAGE_PATH');
        $this->USER_THUMBNAIL_IMAGE_PATH = Config::get('constant.USER_THUMBNAIL_IMAGE_PATH');
        $this->USER_THUMBNAIL_IMAGE_HEIGHT = Config::get('constant.USER_THUMBNAIL_IMAGE_HEIGHT');
        $this->USER_THUMBNAIL_IMAGE_WIDTH = Config::get('constant.USER_THUMBNAIL_IMAGE_WIDTH');
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('user-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }

    public function index()
    {   
        $postData = Input::get();

        if((isset($postData['searchtext']) && $postData['searchtext'] != '') || (isset($postData['usertype']) && $postData['usertype'] != '') || (isset($postData['fieldname']) && $postData['fieldname'] != '' && isset($postData['fieldtype']) && $postData['fieldtype'] != ''))
        {   
            $this->log->info('Admin user listing page', array('admin_user_id' => Auth::id()));
            if(Auth::user()->agent_approved == 1)
            {
                $postData['created_by'] = Auth::id();
                
                $mainArray = [];
                $agentAssignUsers = Business::where('agent_user',Auth::id())->select('user_id')->get();
                if(!empty($agentAssignUsers))
                {
                   foreach ($agentAssignUsers as $user) 
                    {
                        $mainArray[] = $user->user_id;
                    }
                    $postData['user_ids'] = $mainArray;
                }
            }
            $userList = $this->objUser->userFilter($postData);
        }
        else
        {
            $this->log->info('Admin user listing page', array('admin_user_id' => Auth::id()));
            if(Auth::user()->agent_approved == 1)
            {
                $mainArray = [];
                $agentAssignUsers = Business::where('agent_user',Auth::id())->select('user_id')->get();
                if(!empty($agentAssignUsers) && count($agentAssignUsers) > 0)
                {
                    foreach ($agentAssignUsers as $user) 
                    {
                        $mainArray[] = $user->user_id;
                    }
                }
                $filters['deleted'] = 'all';
                $filters['status'] = 'all';
                $filters['created_by'] = Auth::id();
                $filters['user_ids'] = $mainArray;
                $userList = $this->objUser->getAll($filters,true);
            }
            else
            {
                $userList = $this->objUser->getAll(['deleted'=>'all','status' => 'all'],true);
            }
            
        }
        
        return view('Admin.ListUsers', compact('userList','postData'));
    }

    public function add()
    {
        $this->log->info('Admin user add page', array('admin_user_id' => Auth::id()));
        return view('Admin.EditUser');
    }

    public function addAgent()
    {
        $agent_approved = '1';
        $this->log->info('Admin agent add page', array('admin_user_id' => Auth::id()));
        return view('Admin.EditUser',compact('agent_approved'));
    }

    public function save(UserRequest $request)
    {
        $postData = Input::get();
       
        unset($postData['_token']);
        
        if(isset($postData['password']) && $postData['password'] != '') 
        {
            $postData['password'] = bcrypt($postData['password']);
        } else {
            unset($postData['password']);
        }
        
        // upload user profile picture
        if (Input::file('profile_pic')) 
        {  
            $profile_pic = Input::file('profile_pic');

            if (!empty($profile_pic) && count($profile_pic) > 0) 
            {
                $fileName = 'user_' . uniqid() . '.' . $profile_pic->getClientOriginalExtension();

                $pathOriginal = public_path($this->USER_ORIGINAL_IMAGE_PATH . $fileName);
                $pathThumb = public_path($this->USER_THUMBNAIL_IMAGE_PATH . $fileName);

                Image::make($profile_pic->getRealPath())->save($pathOriginal);
                Image::make($profile_pic->getRealPath())->resize($this->USER_THUMBNAIL_IMAGE_WIDTH, $this->USER_THUMBNAIL_IMAGE_HEIGHT)->save($pathThumb);

                if(isset($postData['old_profile_pic']) && $postData['old_profile_pic'] != '')
                {
                    $originalImageDelete = Helpers::deleteFileToStorage($postData['old_profile_pic'], $this->USER_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($postData['old_profile_pic'], $this->USER_THUMBNAIL_IMAGE_PATH, "s3");
                }

                //Uploading on AWS
    			$originalImage = Helpers::addFileToStorage($fileName, $this->USER_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
    			$thumbImage = Helpers::addFileToStorage($fileName, $this->USER_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
    			//Deleting Local Files
    			\File::delete($this->USER_ORIGINAL_IMAGE_PATH . $fileName);
    			\File::delete($this->USER_THUMBNAIL_IMAGE_PATH . $fileName);

                $postData['profile_pic'] = $fileName;
            }
        }
        
        $subscription =  (!isset($postData['subscription'])) ? 0 : 1;
        $postData['subscription'] = $subscription;

        $isRajput =  (!isset($postData['isRajput'])) ? 0 : 1;
        $postData['isRajput'] = $isRajput;
        
        if($postData['id'] == 0)
        {
            $userData = $this->objUser->where('phone',$postData['phone'])->where('country_code',$postData['country_code'])->get();
        }
        else
        {
            $userData = $this->objUser->where('phone',$postData['phone'])->where('id','<>',$postData['id'])->where('country_code',$postData['country_code'])->get();
        }
        if(count($userData) > 0)
        {
            return Redirect::back()->withErrors('Country code and phone number\'s combination must be unique')->withInput();
        }
        else
        {
            if(isset($postData['action']) && $postData['action'] == 1)
            {
                $postData['agent_approved'] = 1;
            }elseif (isset($postData['action']) && $postData['action'] == 0) {
                $postData['agent_approved'] = 0;

            }
            $response = $this->objUser->insertUpdate($postData);
        }

        $userId = (isset($postData['id']) && $postData['id'] == 0) ? $response->id : $postData['id'];

        if(isset($postData['action']) && $postData['action'] == 1)
        {
            $agentDetail = AgentUser::firstOrCreate(['user_id' =>$userId]);
            $agentDetail->user_id = $userId;

            if(isset($postData['agent_city']) && !empty($postData['agent_city']))
            {
                $agentDetail->city = implode(',',$postData['agent_city']);
            }
            if(isset($postData['agent_bank_detail']) && $postData['agent_bank_detail'] != '')
            {
                $agentDetail->bank_detail = $postData['agent_bank_detail'];
            }
            
            $agentDetail->save();
            
        }
        elseif (isset($postData['action']) && $postData['action'] == 0) {

            $agentDetail = AgentUser::where('user_id',$userId)->first();
            if(!empty($agentDetail))
            {
                $agent = AgentUser::find($agentDetail->id);
                $agent->delete();
            }
            
        }

        if ($response) 
        {            
            UserRole::firstOrCreate(['user_id' =>  ($postData['id'] == 0)?$response->id:$postData['id'], 'role_id' => Config::get('constant.USER_ROLE_ID')]);
            if(isset($postData['agent_approved']) && $postData['agent_approved'] == 1)
            {
                $this->log->info('Admin user added/updated successfully', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId));
                return Redirect::to("admin/users")->with('success', trans('labels.agentsuccessmsg'));
            }
            else
            {
                $this->log->info('Admin user added/updated successfully', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId));
                return Redirect::to("admin/users")->with('success', trans('labels.usersuccessmsg'));
            }
        } else {
            $this->log->error('Admin something went wrong while adding/updating user', array('admin_user_id' =>  Auth::id(), 'user_id' => $userId));
            return Redirect::to("admin/users")->with('error', trans('labels.usererrormsg'));
        }
    }

    public function edit($id)
    {
        try 
        {
            $id = Crypt::decrypt($id);
            $data = $this->objUser->find($id);
            $isVendor = Helpers::userIsVendorOrNot($id);
            
            if($data) 
            {
                $this->log->info('Admin user edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id));
                return view('Admin.EditUser', compact('data','isVendor'));
            } else {
                $this->log->error('Admin something went wrong while user edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id));
                return Redirect::to("admin/users")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while user edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id, 'error' => $e->getMessage()));
            return view('errors.404');
        }
                
        
    }

    public function editAgent($id)
    {
        try 
        {
            $id = Crypt::decrypt($id);
            $data = $this->objUser->find($id);
            $isVendor = Helpers::userIsVendorOrNot($id);
           
            if($data) 
            {
                $this->log->info('Admin user edit agent page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id));
                return view('Admin.EditUser', compact('data','isVendor'));
            } else {
                $this->log->error('Admin something went wrong while agent edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id));
                return Redirect::to("admin/users")->with('error', trans('labels.recordnotexist'));
            }
        }   
        catch (DecryptException $e) 
        {
            $this->log->error('Admin something went wrong while agent edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id, 'error' => $e->getMessage()));
            return view('errors.404');
        }
    }

    public function delete($id)
    {
        $data = $this->objUser->find($id); 
        $response = $data->delete();
        if ($response) 
        {
            $this->log->info('Admin user deleted successfully', array('admin_user_id' => Auth::id(),'user_id' => $id));
            return Redirect::to("admin/users")->with('success', trans('labels.userdeletesuccessmsg'));
        }
    } 

    public function hardDelete($id)
    {
        $data = $this->objUser->withTrashed()->find($id); 
        $response = $data->forceDelete();
        
        $this->log->info('Admin user have hard deleted successfully', array('admin_user_id' => Auth::id(),'user_id' => $id));
        return Redirect::to("admin/users")->with('success', trans('labels.userharddeletesuccessmsg'));
        
    }

    public function setUserActive($id)
    {
        $data = $this->objUser->withTrashed()->find($id);
        $data->deleted_at = NULL;
        $response = $data->save();
        if ($response) 
        {
           return 1;
        }
    }

    public function getOtpList()
    {
        $otpList = UserRegisterOTP::orderBy('id','desc')->get(); 
        return view('Admin.ListOtp', compact('otpList'));
    }
    
    public function editOtp($id)
    {
        try
        {
            $id = Crypt::decrypt($id);
            $data = UserRegisterOTP::find($id);
           
            if($data) 
            {
                $this->log->info('Admin user otp edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id));
                return view('Admin.EditOtp', compact('data','isVendor'));
            } else {
                $this->log->error('Admin something went wrong while user otp edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id));
                return Redirect::to("admin/otp")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            $this->log->error('Admin something went wrong while user otp edit page', array('admin_user_id' =>  Auth::id(), 'user_id' => $id, 'error' => $e->getMessage()));
            return view('errors.404');
        }
    }

    public function saveOtp(Request $request)
    {
        $postData = Input::get();
        $otpDetail = UserRegisterOTP::find($postData['id']);
        $otpDetail->otp = $postData['otp'];
        $otpDetail->save();
        if($request->input('action') == 'sendotp')
        {
            $response = Helpers::sendMessage($otpDetail->phone,"RYUVA Club - ".$otpDetail->otp." is the OTP for User Registration");

            if($response) 
            {
                
                $this->log->info('API send OTP for registration successfully', array('login_user_id' => Auth::id()));
                return Redirect::to("admin/otp")->with('success', trans('labels.otpsendsuccessmsg')); 
            }
            else
            {
                $this->log->info('Something went wrong while send OTP for User registration', array('login_user_id' => Auth::id()));
                return Redirect::to("admin/otp")->with('error', trans('labels.otpsenderrormsg'));
            }
        }
        else
        {
            return Redirect::to("admin/otp")->with('success', trans('labels.otpsavesuccessmsg'));
        }
        
    }


    public function sendOtp($id,$type)
    {
        if($type == 'single')
        {
            $id = Crypt::decrypt($id);

            $otpDetail = UserRegisterOTP::find($id);
            
            $response = Helpers::sendMessage($otpDetail->phone,"RYUVA Club - ".$otpDetail->otp." is the OTP for User Registration");
        }
        elseif ($type == 'all') {
            
            $otpList = UserRegisterOTP::get();
            if(count($otpList) > 0)
            {
                foreach($otpList as $otp)
                {
                    $response = Helpers::sendMessage($otp->phone,"RYUVA Club - ".$otp->otp." is the OTP for User Registration");
                }
            }
        }

        $this->log->info('API send OTP for registration successfully', array('login_user_id' => Auth::id()));
        
        return Redirect::to("admin/otp")->with('success', trans('labels.otpsend')); 
        
    }



}
