<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Business;
use App\UserRegisterOTP;
use App\UsersDevice;
use App\UserRole;
use App\UserMetaData;
use App\AgentRequest;
use App\Cms;
use App\Chats;
use App\NotificationList;
use Illuminate\Validation\Rule;
use JWTAuth;
use JWTAuthException;
use Helpers;
use DB;
use Validator;
use Config;
use Input;
use Image;
use File;
use Mail;
use Carbon\carbon;
use Auth;
use App;
use Lang;
use Cache;
use Storage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class UsersController extends Controller {

    private $user;

    public function __construct(User $user) {
        $this->user = $user;
        $this->objUsersDevice = new UsersDevice;
        $this->objNotificationList = new NotificationList;
        $this->objUserMetaData = new UserMetaData();
        $this->objAgentRequest = new AgentRequest();
        $this->USER_ORIGINAL_IMAGE_PATH = Config::get('constant.USER_ORIGINAL_IMAGE_PATH');
        $this->USER_THUMBNAIL_IMAGE_PATH = Config::get('constant.USER_THUMBNAIL_IMAGE_PATH');
        $this->USER_PROFILE_PIC_WIDTH = Config::get('constant.USER_PROFILE_PIC_WIDTH');
        $this->USER_PROFILE_PIC_HEIGHT = Config::get('constant.USER_PROFILE_PIC_HEIGHT');

        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('users-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }

    /**
     * Register a new user.
     *
     * @param Request $request The current request
     * @return \App\User A new \App\User object
     * @throws Exception If there was an error
     * @see \App\User
     * @Post("/")
     * @Parameters({
     *     @Parameter("username", description="The username of the user", type="string"),
     *     @Parameter("email", description="A valid email address", type="string"),
     *     @Parameter("phone", description="A phone number", type="string"),
     *     @Parameter("device_token", description="Device token", type="string"),
     *     @Parameter("dob", description="Date of birth of user", type="date"),
     *     @Parameter("gender", description="User gender", type="integer"),
     *     @Parameter("zipcode", description="Valid US zipcode", type="string"),
     *     @Parameter("password", description="A valid password for the user. Minimum 8 characters.", type="string")
     *     @Parameter("social_type", description="0:App, 1"Gmail, 2:Facebook", type="integer")
     *     @Parameter("social_id", description="Social Id", type="string")
     * })
     * @Transaction({
     *     @Request( {"username": "vandit.kotadiya","email": "vandit.inexture@gmail.com","device_token":"ajjh","phone": "+11234567890","dob": "1993-06-19","gender": "1","zipcode": "90210","password": "12345678"} ),
     *     @Response( {"status": "1","message": "User created successfully.","data": {"userDetail": {"username": "vandit.kotadiya","email": "vandit.kotadiya@inexture.in","phone": "+11234567890","dob": "1993-06-19","gender": "1","zipcode": "90210","latitude": 34.1030032,"longitude": -118.4104684,"city": "Beverly Hills","state": "California","country": "United States","updated_at": "2017-10-31 10:22:20","created_at": "2017-10-31 10:22:20","id": 6},"loginToken": {"token": "ASDFGHe678"}}} ),

     * })
     */
    public function register(Request $request)
    {
        $outputArray = [];
        $data = [];
        try
        {
            DB::beginTransaction();
            $requestData = array_map('trim',$request->all());
            $validator = Validator::make($requestData, [
                    'name' => ['required'],
                    'email' => 'email',
                    'phone' => 'required|min:6|max:13',
                    'password' =>'required|min:8|max:20',
                    'country_code' => 'required'
//                  'device_token' => 'required',
//                  'dob' => 'required|date|date_format:Y-m-d|before:tomorrow',
//                  'dob' => 'required',
//                  'occupation' => 'required'
            ]);
            if ($validator->fails())
            {
                DB::rollback();
                $this->log->error('API validation failed while register');
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray,$statusCode);

                // if ($validator->messages()->has('phone'))
                // {
                //     if($validator->messages()->all()[0] == 'unique')
                //     {
                //         DB::rollback();
                //         $this->log->error('API validation failed while register');
                //         $outputArray['status'] = 0;
                //         $outputArray['message'] = 'The entered phone number has already been taken, Please contact support at: +919099937890';
                //         $errorArray = [];
                //         $errorArray['error']['errorcode'] = 'phone-unique-error';
                //         $errorArray['error']['contact_number'] = '+919099937890';
                //         $outputArray['data'] = $errorArray;

                //         $statusCode = 200;
                //         return response()->json($outputArray,$statusCode);
                //     }
                //     else
                //     {
                //         DB::rollback();
                //         $this->log->error('API validation failed while register');
                //         $outputArray['status'] = 0;
                //         $outputArray['message'] = $validator->messages()->all()[0];
                //         $statusCode = 200;
                //         return response()->json($outputArray,$statusCode);
                //     }
                // }
                // else
                // {
                //     DB::rollback();
                //     $this->log->error('API validation failed while register');
                //     $outputArray['status'] = 0;
                //     $outputArray['message'] = $validator->messages()->all()[0];
                //     $statusCode = 200;
                //     return response()->json($outputArray,$statusCode);
                // }

            }

            if((isset($request->phone) && $request->phone != '') && (isset($request->country_code) && $request->country_code != ''))
            {
                $user = User::where('phone',$request->phone)->where('country_code',$request->country_code)->first();

                if(count($user) > 0)
                {
                    DB::rollback();
                    $this->log->error('API validation failed while register');
                    $outputArray['status'] = 0;
                    $outputArray['message'] = 'The entered phone number has already been taken, Please contact support at: +919099937890';
                    $errorArray = [];
                    $errorArray['error']['errorcode'] = 'phone-unique-error';
                    $errorArray['error']['contact_number'] = '+919099937890';
                    $outputArray['data'] = $errorArray;

                    $statusCode = 200;
                    return response()->json($outputArray,$statusCode);
                }
            }

            if(isset($request->country_code) && $request->country_code != '')
            {
                if(isset($request->otp) && $request->otp != '')
                {
                    $userRegisterOTPdata = UserRegisterOTP::where('otp',$request->otp)
                                            ->where('phone',$request->phone)
                                            ->where('country_code',$request->country_code)
                                            ->first();

                    if($userRegisterOTPdata)
                    {
                        $otp_sent = Carbon::parse($userRegisterOTPdata->created_at);
                        $now = Carbon::now();
                        $diff = $otp_sent->diffInMinutes($now);
                        if($diff > 4320) {
                            DB::rollback();
                            $this->log->error('API user register otp expired');
                            $outputArray['status'] = 0;
                            $outputArray['message'] = trans('apimessages.otp_expired');
                            $statusCode = 200;
                            return response()->json($outputArray,$statusCode);
                        } else {
                            $otpData = UserRegisterOTP::find($userRegisterOTPdata->id);
                            $otpData->delete();
                        }

                    }
                    else
                    {
                        DB::rollback();
                        $this->log->error('API otp varification failed');
                        $outputArray['status'] = 0;
                        $outputArray['message'] = 'OTP verification failed';
                        $statusCode = 200;
                        return response()->json($outputArray,$statusCode);
                    }
                }
                else
                {
                    DB::rollback();
                    $this->log->error('API validation for otp required');
                    $outputArray['status'] = 0;
                    $outputArray['message'] = 'OTP required';
                    $statusCode = 200;
                    return response()->json($outputArray,$statusCode);
                }
            }

//            $data = $request->only('name', 'phone', 'password');
              $data['name'] = $requestData['name']; //$request->name
              $data['phone'] = $requestData['phone']; //$request->phone;
              $data['email'] = (isset($requestData['email']) && $requestData['email'] != '') ? $requestData['email'] : '' ;
              $data['password'] = bcrypt($request->password);
              if(isset($requestData['country_code']) && $requestData['country_code'] != '')
              {
                  $data['country_code'] = $requestData['country_code'];
              }
//            if (isset($request->social_id) && $request->social_id != '' && isset($request->social_type) && $request->social_type != '')            {
//                $data = $request->only('username', 'email', 'phone', 'dob', 'gender', 'zipcode', 'password', 'social_id', 'social_type');
//            } else {
//                $data = $request->only('username', 'email', 'phone', 'dob', 'gender', 'zipcode', 'password');
//            }
            if(isset($requestData['isRajput']) && $requestData['isRajput'] != '')
            {
                $data['isRajput'] = $requestData['isRajput'];
            }

            $data['manual_entry'] = 0;
            $user = $this->user->create($data);
            if($user)
            {
                $userRoleData = [];
                $userRoleData['user_id'] = $user->id;
                $userRoleData['role_id'] = Config::get('constant.USER_ROLE_ID');
                $saveUserRole = UserRole::create($userRoleData);
            }
            // Added device token
            $request->device_type = (isset($request->device_type) && $request->device_type != null) ? $request->device_type : '3';
            $request->device_id = (isset($request->device_id) && $request->device_id != null) ? $request->device_id : '';

            $request->device_token = ($request->device_token) ? $request->device_token : '';

            $device['device_type'] = $request->device_type;
            $device['device_id'] = $request->device_id;
            $device['device_token'] = $request->device_token;
            $device['user_id'] = $user->id;
            if($request->device_type != 3)
            {
                $this->objUsersDevice->insertUpdate($device);
            }


//          Generate authorization token

            $credentials = $request->only('phone', 'password','country_code');

            $token = null;

            try {
                // Get token with phone and password
                if (!$token = JWTAuth::attempt($credentials))
                {
                    DB::rollback();
                    $outputArray['status'] = 0;
                    $this->log->error('API something went wrong while register');
                    $outputArray['message'] = 'Invalid credential.';
                    $statusCode = 200;
                    return response()->json($outputArray, $statusCode);
                }
            } catch (JWTAuthException $e) {
                DB::rollback();
                $this->log->error('API something went wrong while register', array('error' => $e->getMessage()));
                $outputArray['status'] = 0;
                $outputArray['message'] = 'Failed to create token.';
                $statusCode = 520;
                return response()->json($outputArray, $statusCode);
            }
            DB::commit();

//            $request->user()->user_pic = ($request->user()->user_pic != NULL && $request->user()->user_pic != '') ? url($this->userThumbImageUploadPath . $request->user()->user_pic) : '';
//

            $user = User::where('id',$user->id)->first();

            $outputArray['status'] = 1;
            $outputArray['message'] = 'User created successfully.';
            $outputArray['data'] = array();
            $outputArray['data'] = $user;
            $outputArray['data']['isVendor'] = 0;
            $outputArray['data']['profile_pic_thumbnail'] = '';
            $outputArray['data']['profile_pic_original'] = '';
            $outputArray['data']['business_id'] = '';
            $outputArray['data']['business_name'] = '';
            $outputArray['data']['business_approved'] = '';
            $outputArray['data']['business_slug'] = '';
            $outputArray['data']['membership_type'] = '';
            $outputArray['data']['membership_type_icon'] = '';
            $outputArray['data']['loginToken'] = $token;
            $statusCode = 201;
            return response()->json($outputArray, $statusCode);
        } catch (Exception $e) {
            DB::rollback();
            $outputArray['status'] = 0;
            $outputArray['message'] = 'Error registering user.';
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
    }


    /* Forgot password */

    public function forgotPassword(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'forgot_email' => ['required', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                DB::rollback();
                $this->log->error('API validation failed while forgotPassword');
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0]
                ]);
            }
            // Get user detail from database
            $user = User::where('email', $request->forgot_email)->where('status',0)->first();

            // User not exist
            if (is_null($user))
            {
                return response()->json([
                    'status' => 0,
                    'message' => 'User does not exist!'
                ]);
            }
            DB::table('password_resets')->where('email', $request->forgot_email)->delete();

            //create a new token to be sent to the user.
            DB::table('password_resets')->insert([
                'email' => $request->forgot_email,
                'token' => str_random(80),
                'created_at' => Carbon::now()
            ]);

            $tokenData = DB::table('password_resets')->where('email', $request->forgot_email)->first();

            $token = $tokenData->token;
            $email = $request->forgot_email;

            // Send Password reset mail
            $data = [
                'url' => url('password/reset/' . $token),
                'username' => ($user->name == null && $user->name == '')?$user->username:$user->name
            ];

            Mail::send('emails.ResetPassword', $data, function($message) use($email) {
                $message->to($email)->subject('Your Password Reset Link');
            });

            DB::commit();
            return response()->json([
                'status' => 1,
                'message' => 'Mail sent in your account.',
                'data' => [
                ]
            ]);
        } catch (Exception $e) {
            DB::rollback();
            $this->log->error('API something went wrong while forgotPassword', array('error' => $e->getMessage()));
            return response()->json([
                'status' => 0,
                'message' => 'error',
                'code' => $e->getStatusCode()
            ]);
        }
    }

    /**
     * Logout user delete device token.
     *
     * @param Request $request The current request
     * @return \App\User A new \App\User object
     * @throws Exception If there was an error
     * @see \App\User
     * @Post("/")
     * @Parameters({
     *     @Parameter("device_token", description="The device token which you wants to delete.", type="string"),
     * })
     * @Transaction({
     *     @Request( {"device_token":"token"} ),
     *     @Response( {"status": "1","message": "Success","data": []} ),
     *     @Response( {"status": "0",'message': 'error','code' => $e->getStatusCode()} )
     * })
     */
    public function logout(Request $request)
    {
        try
        {
            $user = JWTAuth::parseToken()->authenticate();
            DB::beginTransaction();
            $request->device_type = ($request->device_type) ? $request->device_type : 3;
            $request->device_token = ($request->device_token) ? $request->device_token : '';
            $request->device_id = ($request->device_id) ? $request->device_id : '';

            // Delete device token of logged in user
            $user = $request->user();
            UsersDevice::where('user_id', $user->id)->where('device_type', $request->device_type)->where('device_id', $request->device_id)->forceDelete();
            JWTAuth::invalidate(JWTAuth::getToken());
            DB::commit();
            $outputArray['status'] = 1;
            $outputArray['message'] = 'Logged out successfully.';
            $statusCode = 200;
            $outputArray['data'] = [];
            return response()->json($outputArray, $statusCode);
        } catch (Exception $e)
        {
            DB::rollback();
            $this->log->error('API something went wrong while logout', array('error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = 'Something Went to Wrong';
            $statusCode =  $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
        }
    }

    public function getProfile(Request $request)
    {
        $userId = Auth::id();
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $userData = [];
            $response = $this->user->find($userId);
            if($response)
            {
                $response = Helpers::getProfileExtraFields($response);
                $response['isVendor'] = Helpers::userIsVendorOrNot($userId);

                if($response['isVendor'] && isset($response->singlebusiness))
                {
                    $response['business_id'] = $response->singlebusiness->id;
                    $response['business_name'] = $response->singlebusiness->name;
                    $response['business_approved'] = $response->singlebusiness->approved;
                    $response['business_slug'] = $response->singlebusiness->business_slug;
                    if($response->singlebusiness->membership_type == 2)
                    {
                        $response['membership_type'] = 1;
                        $response['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                    }
                    elseif($response->singlebusiness->membership_type == 1)
                    {
                        $response['membership_type'] = 1;
                        $response['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                    }
                    else
                    {
                        $response['membership_type'] = 0;
                        $response['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                    }
                    unset($response['singlebusiness']);
                }
                else
                {
                    $response['business_id'] = '';
                    $response['business_name'] = '';
                    $response['business_approved'] = 0;
                    $response['business_slug'] = '';
                    $response['membership_type'] = '';
                    $response['membership_type_icon'] = '';
                }

                $responseData['status'] = 1;
                $responseData['message'] =  trans('apimessages.default_success_msg');
                $responseData['data'] =  $response;
                $statusCode = 200;
            }
            else
            {
                $this->log->error('API something went wrong while getProfile');
                $responseData['status'] = 0;
                $responseData['message'] = trans('apimessages.empty_data_msg');
                $responseData['data'] =  [];
                $statusCode = 200;
            }
        } catch (Exception $e) {
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    public function saveProfile(Request $request)
    {
        $userId = Auth::id();
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $userData = [];
            $validator = Validator::make($request->all(), [
                'email' => 'email',
                'dob' => 'date'
            ]);

            if ($validator->fails())
            {
                $this->log->error('API validation failed while saveProfile', array('login_user_id' => $userId));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 400;
            }
            else
            {
                $requestData['id'] = $userId;
                $response = $this->user->insertUpdate($requestData);

                if($response)
                {
                    $data = $this->user->find($userId);

                    $data = Helpers::getProfileExtraFields($data);
                    $data['isVendor'] = Helpers::userIsVendorOrNot($userId);
                    if($data['isVendor'] && isset($data->singlebusiness))
                    {
                        $data['business_id'] = $data->singlebusiness->id;
                        $data['business_name'] = $data->singlebusiness->name;
                        $data['business_approved'] = $data->singlebusiness->approved;
                        $data['business_slug'] = $data->singlebusiness->business_slug;
                        if($data->singlebusiness->membership_type == 2)
                        {
                            $response['membership_type'] = 1;
                            $response['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                        }
                        elseif($data->singlebusiness->membership_type == 1)
                        {
                            $data['membership_type'] = 1;
                            $data['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                        }
                        else
                        {
                            $data['membership_type'] = 0;
                            $data['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                        }
                        unset($data['singlebusiness']);
                    }
                    else
                    {
                        $data['business_id'] = '';
                        $data['business_name'] = '';
                        $data['business_approved'] = 0;
                        $data['business_slug'] = '';
                        $data['membership_type'] = '';
                        $data['membership_type_icon'] = '';
                    }

                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.profile_updated_success');
                    $responseData['data'] =  $data;
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while saveProfile', array('login_user_id' => $userId));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $responseData['data'] =  [];
                    $statusCode = 200;
                }
            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while saveProfile', array('login_user_id' => $userId, 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    public function changePassword(Request $request)
    {
        $userId = Auth::id();
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $userData = [];
            $validator = Validator::make($request->all(), [
                'oldPassword' => 'required',
                'newPassword' => 'required|min:8',

            ]);

            if ($validator->fails())
            {
                $this->log->error('API validation failed while changePassword', array('login_user_id' => $userId));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 400;
            }
            else
            {
                $bool = $this->user->checkCurrentPassword($userId, $requestData['oldPassword']);
                if($bool)
                {
                    $userData = [];
                    $userData['id'] = $userId;
                    $userData['password'] = bcrypt($requestData['newPassword']);

                    $response = $this->user->insertUpdate($userData);
                    if($response)
                    {
                        $responseData['status'] = 1;
                        $responseData['message'] =  trans('apimessages.newpassword_updated_success');
                        $statusCode = 200;
                    }
                    else
                    {
                        $this->log->error('API something went wrong while changePassword', array('login_user_id' => $userId));
                        $responseData['status'] = 0;
                        $responseData['message'] = trans('apimessages.default_error_msg');
                        $statusCode = 200;
                    }
                }
                else
                {
                    $this->log->error('API something went wrong while changePassword', array('login_user_id' => $userId));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.invalid_oldpassword_msg');
                    $statusCode = 200;
                }
            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while changePassword', array('login_user_id' => $userId, 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    public function saveProfilePicture()
    {
        $userId = Auth::id();
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $profile_pic = Input::file('profile_pic');
            if (!empty($profile_pic) && count($profile_pic) > 0)
            {
                $fileName = 'user_' . uniqid() . '.' . $profile_pic->getClientOriginalExtension();
                $pathOriginal = public_path($this->USER_ORIGINAL_IMAGE_PATH . $fileName);
                $pathThumb = public_path($this->USER_THUMBNAIL_IMAGE_PATH . $fileName);
                $profile_pic->getRealPath();

                Image::make($profile_pic->getRealPath())->save($pathOriginal);
                Image::make($profile_pic->getRealPath())->resize($this->USER_PROFILE_PIC_WIDTH, $this->USER_PROFILE_PIC_HEIGHT)->save($pathThumb);

                // if profile pic exist then delete
                $oldImage = $this->user->find($userId)->profile_pic;

                if($oldImage != '') {
                    $originalImageDelete = Helpers::deleteFileToStorage($oldImage, $this->USER_ORIGINAL_IMAGE_PATH, "s3");
                    $thumbImageDelete = Helpers::deleteFileToStorage($oldImage, $this->USER_THUMBNAIL_IMAGE_PATH, "s3");
                }

                 //Uploading on AWS
                $originalImage = Helpers::addFileToStorage($fileName, $this->USER_ORIGINAL_IMAGE_PATH, $pathOriginal, "s3");
                $thumbImage = Helpers::addFileToStorage($fileName, $this->USER_THUMBNAIL_IMAGE_PATH, $pathThumb, "s3");
                //Deleting Local Files
                \File::delete($this->USER_ORIGINAL_IMAGE_PATH . $fileName);
                \File::delete($this->USER_THUMBNAIL_IMAGE_PATH . $fileName);

                $response = $this->user->insertUpdate(['id' => $userId , 'profile_pic' => $fileName]);

                if($response)
                {

                    $data = $this->user->find($userId);
                    $data = Helpers::getProfileExtraFields($data);
                    $data['isVendor'] = Helpers::userIsVendorOrNot($userId);

                    if($data['isVendor'] && isset($data->singlebusiness))
                    {
                        $data['business_id'] = $data->singlebusiness->id;
                        $data['business_name'] = $data->singlebusiness->name;
                        $data['business_approved'] = $data->singlebusiness->approved;
                        $data['business_slug'] = $data->singlebusiness->business_slug;
                        if($data->singlebusiness->membership_type == 2)
                        {
                            $response['membership_type'] = 1;
                            $response['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));
                        }
                        elseif($data->singlebusiness->membership_type == 1)
                        {
                            $data['membership_type'] = 1;
                            $data['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                        }
                        else
                        {
                            $data['membership_type'] = 0;
                            $data['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                        }
                        unset($data['singlebusiness']);
                    }
                    else
                    {
                        $data['business_id'] = '';
                        $data['business_name'] = '';
                        $data['business_approved'] = 0;
                        $data['business_slug'] = '';
                        $data['membership_type'] = '';
                        $data['membership_type_icon'] = '';
                    }

                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.uploaded_successfully');
                    $responseData['data'] =  $data;
                    $statusCode = 200;
                }
                else
                {

                    $this->log->error('API something went wrong while saveProfilePicture', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while saveProfilePicture', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    public function getAppUpdateStatus(Request $request)
    {
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg'), 'data' => []];
        $statusCode = 400;
        $requestData = Input::all();
        try {
            $headerData = $request->header('DeviceType');
            if(isset($headerData))
            {
                $deviceType = $request->header('DeviceType');
                if($deviceType == 'android')
                {
                    $appVersion['appVersion'] =  Config::get('constant.ANDROID_APP_VERSION');
                    $appVersion['forceUpdate'] =  Config::get('constant.ANDROID_APP_FORCE_UPDATE');
                }
                elseif ($deviceType == 'ios') {
                    $appVersion['appVersion'] =  Config::get('constant.IOS_APP_VERSION');
                    $appVersion['forceUpdate'] =  Config::get('constant.IOS_APP_FORCE_UPDATE');
                }
            }
            else
            {
                $appVersion['appVersion'] =  1;
                $appVersion['forceUpdate'] =  TRUE;
            }

            if($appVersion['appVersion'] == $requestData['appVersion'])
            {
                $appVersion['forceUpdate'] = FALSE;
            }

            if($appVersion['appVersion'] > $requestData['appVersion']) {
                $appVersion['appUpdate'] = TRUE;
            } else {
                $appVersion['appUpdate'] = FALSE;
            }
            $appVersion['languageLabelsVersion'] = Config::get('constant.LANGUAGE_LABELS_VERSION');
            $responseData = ['status' => 1, 'message' => trans('apimessages.default_success_msg')];
            $responseData['data'] = $appVersion;
            $statusCode = 200;
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getAppUpdateStatus', array('error' => $e->getMessage()));
            $responseData = ['status' => 1, 'message' => $e->getMessage(), 'data' => []];
        }
        return response()->json($responseData, $statusCode);
    }

    public function getLanguageLabels()
    {
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg'), 'data' => []];
        $statusCode = 400;
        $requestData = Input::all();
        try {

            App::setLocale('en');
            $labels['en'] = Lang::get('application_labels');
            App::setLocale('hi');
            $labels['hi'] = Lang::get('application_labels');
            App::setLocale('gu');
            $labels['gu'] = Lang::get('application_labels');

            $statusCode = 200;
            $responseData = ['status' => 1, 'message' => trans('apimessages.default_success_msg'), 'data' => $labels];

        } catch (Exception $e) {
            $this->log->error('API something went wrong while getLanguageLabels', array('error' => $e->getMessage()));
            $responseData = ['status' => 1, 'message' => $e->getMessage(), 'data' => []];
        }
        return response()->json($responseData, $statusCode);
    }

    public function contactUs(Request $request)
    {
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg'), 'data' => []];
        $statusCode = 400;
        $requestData = Input::all();

        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'description' => 'required'
            ]);
            if ($validator->fails())
            {
                $this->log->error('API validation failed while contactUs', array('error' => $validator->messages()->all()[0]));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
            }
            else
            {
                // start- send mail by helpers function
                $replaceArray = array();
                if(isset($request->subject) && !empty($request->subject))
                {
                    $replaceArray['SUBJECT'] = $request->subject;
                }
                else
                {
                    $replaceArray['SUBJECT'] = 'No Subject';
                }
                $replaceArray['DESCRIPTION'] = $requestData['description'];
                $replaceArray['EMAIL'] = $requestData['email'];
                $replaceArray['NAME'] = Auth::user()->name;
                $replaceArray['PHONE'] = Auth::user()->phone;
                if(isset(Auth::user()->singlebusiness->name) && !empty(Auth::user()->singlebusiness->name))
                {
                    $replaceArray['BUSINESSNAME'] = Auth::user()->singlebusiness->name;
                }
                else
                {
                    $replaceArray['BUSINESSNAME'] = 'No Business';
                }

                $et_templatepseudoname = 'contact-us';
                $emailParametersArray = [
                                            'toEmail' => Config::get('constant.ADMIN_EMAIL')
                                        ];
                $toName = 'RYEC - Admin';

                Helpers::sendMailByTemplate($replaceArray,$et_templatepseudoname,$emailParametersArray,$toName);

                // end- send mail by helpers function
                $responseData = ['status' => 1, 'message' => trans('apimessages.submitted_success_msg'),'data'=> []];
                $statusCode = 200;
            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while contactUs', array('error' => $e->getMessage()));
            $responseData = ['status' => 1, 'message' => $e->getMessage(), 'data' => []];
        }
        return response()->json($responseData, $statusCode);
    }

    public function addAgentRequest(Request $request)
    {
        $userId = Auth::id();
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg'), 'data' => []];
        $statusCode = 400;
        $requestData = Input::all();

        try {

            $validator = Validator::make($request->all(), [
                'comment' => 'required'
            ]);
            if ($validator->fails())
            {
                $this->log->error('API validation failed while addAgentRequest', array('error' => $validator->messages()->all()[0]));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
            }
            else
            {
                $requestData['user_id'] = $userId;
                $response = AgentRequest::firstOrCreate(['user_id' => $userId]);
                $response->comment = $requestData['comment'];
                $response->save();
                Cache::forget('agentRequestList');
                if($response)
                {
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.agent_request_send_success');
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while addAgentRequest', array('login_user_id' => $userId));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 200;
                }
            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while addAgentRequest', array('login_user_id' => $userId, 'error' => $e->getMessage()));
            $responseData = ['status' => 1, 'message' => $e->getMessage(), 'data' => []];
        }
        return response()->json($responseData, $statusCode);
    }

    /**
    *  Get getCountryCode
    */
    public function getCountryCode(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $countryCodes = Helpers::getCountries();
            $mainArray = [];
            if (count($countryCodes) > 0)
            {
                $indiaListArray = [];
                $indiaMainArray = [];
                foreach ($countryCodes as $key => $code)
                {
                    $listArray = [];
                    if($code->country_code == '+91')
                    {
                        $indiaListArray['country_name'] = $code->name;
                        $indiaListArray['country_code'] = $code->country_code;
                        $thumbImgPath = ((isset($code->flag) && !empty($code->flag)) && Storage::size(Config::get('constant.COUNTRY_FLAG_IMAGE_PATH').$code->flag) > 0) ? Storage::url(Config::get('constant.COUNTRY_FLAG_IMAGE_PATH').$code->flag) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $indiaListArray['country_flag'] = $thumbImgPath;
                        $indiaMainArray[] = $indiaListArray;
                    }
                    else
                    {
                        $listArray['country_name'] = $code->name;
                        $listArray['country_code'] = $code->country_code;
                        $thumbImgPath = ((isset($code->flag) && !empty($code->flag)) && Storage::size(Config::get('constant.COUNTRY_FLAG_IMAGE_PATH').$code->flag) > 0) ? Storage::url(Config::get('constant.COUNTRY_FLAG_IMAGE_PATH').$code->flag) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $listArray['country_flag'] = $thumbImgPath;
                        $mainArray[] = $listArray;
                    }
                }


                $this->log->info('API getCountryCode successfully', array('login_user_id' => Auth::id()));
                $responseData['status'] = 1;
                $responseData['message'] =  trans('apimessages.get_country_code');
                $responseData['data'] =  array_merge($indiaMainArray,$mainArray);
                $statusCode = 200;
            }
            else
            {
                $this->log->info('API getCountryCode no records found', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = trans('apimessages.norecordsfound');
                $responseData['data'] =  $mainArray;
                $statusCode = 200;
            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while getCountryCode', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
    *  Get getAddressMaster
    */
    public function getAddressMaster(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $countries = Helpers::getCountries();
            $states = Helpers::getStates();
            $cities = Helpers::getCities();
            $mainArray['countries'] = [];
            $mainArray['states'] = [];
            $mainArray['cities'] = [];

            if(count($countries) > 0)
            {
                foreach ($countries as $key => $country)
                {
                    $listArray = [];
                    $listArray['name'] = $country->name;
                    $listArray['id'] = $country->id;
                    $mainArray['countries'][] = $listArray;
                }
            }

            if(count($states) > 0)
            {
                foreach ($states as $key => $state)
                {
                    $listArray = [];
                    $listArray['name'] = $state->name;
                    $listArray['id'] = $state->id;
                    $mainArray['states'][] = $listArray;
                }
            }

            if(count($cities) > 0)
            {
                foreach ($cities as $key => $city)
                {
                    $listArray = [];
                    $listArray['name'] = $city->name;
                    $listArray['id'] = $city->id;
                    $mainArray['cities'][] = $listArray;
                }
            }


            $this->log->info('API getAddressMaster successfully', array('login_user_id' => Auth::id()));
            $responseData['status'] = 1;
            $responseData['message'] = trans('apimessages.get_country_state_city');
            $responseData['data'] =  $mainArray;
            $statusCode = 200;


        } catch (Exception $e) {
            $this->log->error('API something went wrong while getAddressMaster', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
    *  Get Cms
    */
    public function getCms(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $cmsData = Cms::get();

            if(count($cmsData) > 0)
            {
               $this->log->info('API get cms templates successfully', array('login_user_id' => Auth::id()));
                $responseData['status'] = 1;
                $responseData['message'] = trans('apimessages.get_cms_template_successfully');
                $responseData['data'] =  $cmsData;
                $statusCode = 200;
            }
            else
            {
                $this->log->info('API cms template no records found', array('login_user_id' => Auth::id()));
                $responseData['status'] = 1;
                $responseData['message'] = trans('apimessages.norecordsfound');
                $responseData['data'] =  [];
                $statusCode = 200;
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while get cms template', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    public function sendRegisterOTP(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'country_code' => 'required'
            ]);
            if ($validator->fails())
            {
                DB::rollback();
                $this->log->error('API validation failed while send OTP for registration', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
            }
            else
            {
                $userData = User::where('phone',$request->phone)->where('country_code',$request->country_code)->first();
                if($userData)
                {
                    DB::rollback();
                    $this->log->error('API validation failed while register');
                    $responseData['status'] = 0;
                    $responseData['message'] = 'The entered phone number has already been taken, Please contact support at: +919099937890';
                    $errorArray = [];
                    $errorArray['error']['errorcode'] = 'phone-unique-error';
                    $errorArray['error']['contact_number'] = '+919099937890';
                    $responseData['data'] = $errorArray;
                    $statusCode = 200;

                }
                else
                {

                    $registerOtpdetail = UserRegisterOTP::where('phone',$requestData['phone'])->where('country_code',$requestData['country_code'])->first();
                    if($registerOtpdetail && count($registerOtpdetail) > 0)
                    {
                        $userRegisterOtp = UserRegisterOTP::find($registerOtpdetail->id);
                        $userRegisterOtp->delete();
                    }

                    $registerOtp = UserRegisterOTP::firstOrCreate(['phone' => $requestData['phone'],'country_code'=>$requestData['country_code']]);
                    if($registerOtpdetail && count($registerOtpdetail) > 0)
                    {
                        $registerOtp->otp = $registerOtpdetail->otp;
                    }
                    else
                    {
                        $registerOtp->otp = Helpers::genrateOTP();
                    }
                    $registerOtp->save();

                    if($requestData['country_code'] == '+91')
                    {
                        $response = Helpers::sendMessage($requestData['phone'],"RYUVA Club - ".$registerOtp->otp." is the OTP for User Registration");
                        if($response) {

                            $this->log->info('API send OTP for registration successfully', array('login_user_id' => Auth::id()));
                            $responseData['status'] = 1;
                            $responseData['message'] = 'OTP has been sent successfully to your mobile '.$requestData['country_code'].$requestData['phone'];
                            $responseData['data'] = new \stdClass();
                            $statusCode = 200;
                        }
                        else
                        {
                            $this->log->info('API something went wrong while send OTP for User registration', array('login_user_id' => Auth::id()));
                            $responseData['status'] = 1;
                            $responseData['message'] = trans('apimessages.norecordsfound');
                            $responseData['data'] =  new \stdClass();
                            $statusCode = 200;
                        }
                    }
                    else
                    {
                        $validator = Validator::make($request->all(), [
                            'email' => 'required|email',
                        ]);

                        if ($validator->fails())
                        {
                            DB::rollback();
                            $this->log->error('API validation failed while invalid email', array('login_user_id' => Auth::id()));
                            $responseData['status'] = 0;
                            $responseData['message'] = $validator->messages()->all()[0];
                            $statusCode = 200;
                        }
                        else
                        {
                            // start- send mail by helpers function
                            $replaceArray = array();
                            $replaceArray['OTP'] = $registerOtp->otp;

                            $et_templatepseudoname = 'register-otp';
                            $emailParametersArray = [
                                                        'toEmail' => $requestData['email']
                                                    ];
                            $toName = '';

                            Helpers::sendMailByTemplate($replaceArray,$et_templatepseudoname,$emailParametersArray,$toName);
                            $registerOtp->save();
                            $this->log->info('API send OTP mail for registration successfully', array('login_user_id' => Auth::id()));
                            $responseData['status'] = 1;
                            $responseData['message'] = 'OTP has been sent successfully to your email '.$requestData['email'];
                            $responseData['data'] = new \stdClass();
                            $statusCode = 200;
                            // end- send mail by helpers function
                        }

                    }
                }
            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while send OTP for User registration', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }



    public function updateNotificationToken(Request $request)
    {

        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();

        try
        {
             $validator = Validator::make($request->all(), [
                'device_id' => 'required',
                'device_token' => 'required',

            ]);
            if ($validator->fails())
            {
                $this->log->error('API something went wrong while update device token', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
            }
            else
            {
                $user = JWTAuth::parseToken()->authenticate();
                $userDevice = UsersDevice::where('user_id',$user->id)->where('device_id',$requestData['device_id'])->first();
                if($userDevice && count($userDevice) > 0)
                {
                    $userDevice->device_token = $requestData['device_token'];
                    $userDevice->save();
                    $responseData['status'] = 1;
                    $responseData['message'] = 'Token updated successfully';
                    $statusCode = 200;
                }
                else
                {
                    $responseData['status'] = 0;
                    $responseData['message'] = 'Record not found';
                    $statusCode = 200;
                }

            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while update device token', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    public function notificationList(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        $headerData = (!empty($request->header('Platform'))) ? $request->header('Platform') : '';
        try
        {
            // for getting total count of list
            $notificationsCount = $this->objNotificationList->where('user_id',$user->id)->get()->count();

            $pageNo = (isset($requestData['page']) && !empty($requestData['page'])) ? $requestData['page'] : 1;

            $filters = [];

            if(!empty($headerData) && $headerData == Config::get('constant.MOBILE_PLATFORM'))
            {
                $offset = Helpers::getOffset($pageNo);
                $filters['offset'] = $offset;
                $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');

            }
            elseif(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                $offset = Helpers::getWebOffset($pageNo);
                $filters['offset'] = $offset;
                $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
            }

            $filters['user_id'] = $user->id;

            $notificationAllListing = $this->objNotificationList->getAll($filters);

            if(!empty($headerData) && $headerData == Config::get('constant.MOBILE_PLATFORM'))
            {
                if($notificationAllListing->count() < Config::get('constant.API_RECORD_PER_PAGE'))
                {
                    $responseData['loadMore'] = 0;
                } else{
                    $offset = Helpers::getOffset($pageNo+1);
                    $loadfilters = [];
                    $loadfilters['offset'] = $offset;
                    $loadfilters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                    $loadNotifications =  $this->objNotificationList->getAll($loadfilters);
                    $responseData['loadMore'] = (count($loadNotifications) > 0) ? 1 : 0 ;
                }
            }
            elseif(!empty($headerData) && $headerData == Config::get('constant.WEBSITE_PLATFORM'))
            {
                if($notificationAllListing->count() < Config::get('constant.WEBSITE_RECORD_PER_PAGE'))
                {
                    $responseData['loadMore'] = 0;
                } else{
                    $offset = Helpers::getOffset($pageNo+1);
                    $loadfilters = [];
                    $loadfilters['offset'] = $offset;
                    $loadfilters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                    $loadNotifications =  $this->objNotificationList->getAll($loadfilters);
                    $responseData['loadMore'] = (count($loadNotifications) > 0) ? 1 : 0 ;
                }
            }


            if($notificationAllListing && count($notificationAllListing) > 0)
            {
                $mainArray = [];
                foreach($notificationAllListing as $notification)
                {

                    $listArray = [];
                    $listArray['id'] = $notification->id;
                    $listArray['user_id'] = $notification->user_id;
                    $listArray['activity_user_id'] = $notification->activity_user_id;
                    $listArray['activity_user_business_id'] = (isset($notification->user) && isset($notification->user->singlebusiness->id))? $notification->user->singlebusiness->id : '';
                    $listArray['activity_user_business_name'] = (isset($notification->user) && isset($notification->user->singlebusiness->name))? $notification->user->singlebusiness->name : '';
                    $listArray['thread_id'] = $notification->thread_id;
                    $listArray['business_id'] = $notification->business_id;
                    $listArray['business_slug'] = '';
                    if($notification->business_id != '')
                    {
                        $businessDetail = Business::find($notification->business_id);
                        $listArray['business_slug'] = (isset($businessDetail->business_slug) && $businessDetail->business_slug != '') ? $businessDetail->business_slug : '';
                    }

                    $listArray['title'] = $notification->title;
                    $listArray['message'] = $notification->message;
                    $listArray['type'] = $notification->type;
                    $listArray['business_name'] = $notification->business_name;
                    $listArray['user_name'] = $notification->user_name;
                    $listArray['created_at'] = strtotime($notification->created_at)*1000;
                    $listArray['activity_user_name'] = '';
                    $listArray['activity_user_phone'] = '';
                    $listArray['activity_user_country_code'] = '';
                    $listArray['thread_title'] = '';
                    if($notification->activity_user_id != '' && $notification->activity_user_id > 0)
                    {
                        $users = User::find($notification->activity_user_id);
                        if($users && count($users) > 0)
                        {
                            $listArray['activity_user_name'] = $users->name;
                            $listArray['activity_user_phone'] = ($users->gender != 2) ? $users->phone : "";
                            $listArray['activity_user_country_code'] = $users->country_code;
                        }
                    }
                    if($notification->thread_id != '' && $notification->thread_id > 0)
                    {
                        $chatThread = Chats::find($notification->thread_id);
                        if($chatThread && count($chatThread) > 0)
                        {
                            $listArray['thread_title'] = $chatThread->title;
                        }
                    }
                    $mainArray[] = $listArray;
                }
                $this->log->info('API notification list get successfully', array('login_user_id' => $user->id));
                $responseData['status'] = 1;
                $responseData['message'] = trans('apimessages.default_success_msg');
                $responseData['data']['notificationsCount'] = $notificationsCount;
                $responseData['data']['notifications'] = $mainArray;
                $statusCode = 200;
            }
            else
            {
                $this->log->error('API something went wrong while get notification list', array('login_user_id' => Auth::id()));
                $responseData['status'] = 1;
                $responseData['message'] = 'Record not found';
                $responseData['data']['notificationsCount'] = 0;
                $responseData['data']['notifications'] = [];
                $statusCode = 200;
            }

        } catch (Exception $e) {
            $this->log->error('API something went wrong while get notification list', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

    /**
     * Delete Notification
     */
    public function deleteNotification(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        $user = JWTAuth::parseToken()->authenticate();
        try
        {
            $validator = Validator::make($request->all(), [
                'notification_id' => 'required'
            ]);
            if ($validator->fails())
            {
                $this->log->error('API validation failed while delete notification', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
            }
            else
            {
                $notificationData = $this->objNotificationList->find($requestData['notification_id']);

                if($notificationData)
                {
                    if($notificationData->user_id == $user->id)
                    {
                        $notificationData->delete();
                        $responseData['status'] = 1;
                        $responseData['message'] =  trans('apimessages.notification_deleted_success');
                        $statusCode = 200;
                    }
                    else
                    {
                        $responseData['status'] = 0;
                        $responseData['message'] =  "Unauthorized user can't delete notification";
                        $statusCode = 200;
                    }

                }
                else
                {
                    $this->log->error('API something went wrong while delete notification', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.invalid_notification_id');
                    $statusCode = 200;
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while delete notification', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

}
