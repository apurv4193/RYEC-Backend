<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Helpers;
use Config;
use App\User;
use App\Business;
use App\UsersDevice;
use Validator;
use DB;
use JWTAuth;
use JWTAuthException;
use Storage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class AuthController extends Controller {

    public function __construct()
    {
        $this->objUsersDevice = new UsersDevice;
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('auth-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }

    /**
     * Get user token The current request
     *
     * @param Request $request
     * @return array An array with a single item, keyed with 'token', that contains an authorization token to be used with other API methods
     * @throws JWTAuthException If there was a JWT authentication error
     * @see \App\User
     * @Post("/")
     * @Parameters({
     *      @Parameter("email", description="email or username of user", type="string"),
     *      @Parameter("password", description="user password.", type="string"),
     * })
     * @Transaction({
     *      @Request( {"email": "test@test.com","password": "12345678"}),
     *      @Response( {"status": "1","message": "Token created successfully.","data": {"userDetail": {"id": 2,"name": null,"username": "vandit.kotadiya","email": "vandit.kotadiya@inexture.in","phone": "+11234567890","dob": "1993-06-19","longitude": -118.410468,"latitude": 34.103003,"zipcode": "90210","city": "Beverly Hills","state": "California","country": "United States","user_pic": null,"gender": 1,"points": null,"roster_app_amount": null,"funds": null,"is_admin": 0,"created_at": "2017-10-17 11:54:50","updated_at": "2017-10-17 11:54:50","deleted_at": null},"loginToken": {"token": "ASDFG5766"}}} )
     *      @Response( {"status": "0",'message': 'Invalid credential.','code' => 422} )
     *      @Response( {"status": "0",'message': 'Failed to create token.','code' => 500} )
     *      @Response( {"status": "0",'message': 'Unauthorized access.','code' => 401} )
     * })
     */
    public function login(Request $request)
    {
        $outputArray = [];
        $validator = Validator::make($request->all(), [
                    'username' => 'required',
                    'password' => 'required',
                    'country_code' => 'required'
        ]);
        if ($validator->fails())
        {
            DB::rollback();
            $this->log->error('API validation failed while login');
            $outputArray['status'] = 0;
            $outputArray['message'] = $validator->messages()->all()[0];
            $statusCode = 200;
            return response()->json($outputArray, $statusCode);
        }

        $request->device_type = (isset($request->device_type) && $request->device_type != null) ? $request->device_type : '3';
        $request->device_token = (isset($request->device_token) && $request->device_token != null) ? $request->device_token : '';
        $request->device_id = (isset($request->device_id) && $request->device_id != null) ? $request->device_id : '';


        $userData = User::where('phone', $request->username)->where('country_code',$request->country_code)->first();
        $token = null;

        if(!$userData)
        {
            // If user not exists
            $outputArray['status'] = 0;
            $this->log->error(trans('apimessages.user_does_not_exist'));
            $outputArray['message'] = trans('apimessages.user_does_not_exist');
            $statusCode = 200;
            return response()->json($outputArray, $statusCode);
        }
        else
        {
            try
            {
                $credentials = [
                    'phone' => $request->username,
                    'password' => $request->password,
                    'country_code' => $request->country_code
                ];
                // Get token with email and password
                if (!$token = JWTAuth::attempt($credentials))
                {
                    $outputArray['status'] = 0;
                    $this->log->error('Invalid credential.');
                    $outputArray['message'] = trans('apimessages.invaild_credential');
                    $statusCode = 200;
                    return response()->json($outputArray, $statusCode);
                }
            } catch (JWTAuthException $e) {
                $outputArray['status'] = 0;
                $this->log->error('Failed to create token', array('error' => $e->getMessage()));
                $outputArray['message'] = trans('apimessages.failed_to_create_token');
                $statusCode = 520;
                return response()->json($outputArray, $statusCode);
            }

            // Add device token if not exist

            $isVendor = Helpers::userIsVendorOrNot($userData->id);

            if(isset($request->device_token) && $request->device_token != 3)
            {
                $this->objUsersDevice->insertUpdate(['user_id' => $request->user()->id, 'device_token' => $request->device_token, 'device_type' => $request->device_type, 'device_id' => $request->device_id]);
            }

            $outputArray['status'] = 1;
            $outputArray['message'] =  trans('apimessages.user_login_successfully');
            $outputArray['data'] = array();
            $outputArray['data'] = $request->user();
            $outputArray['data']['isVendor'] = $isVendor;


            if($userData->agent_approved)
            {
                $outputArray['data']['agent_approved'] = trans('apimessages.approved');
            }
            else
            {
                if(isset($userData->agentRequest))
                {
                    $outputArray['data']['agent_approved'] = trans('apimessages.pending');
                }
                else
                {
                    $outputArray['data']['agent_approved'] = '';
                }
            }
            if($isVendor && isset($userData->singlebusiness))
            {
                $outputArray['data']['business_id'] = $userData->singlebusiness->id;
                $outputArray['data']['business_name'] = $userData->singlebusiness->name;
                $outputArray['data']['business_approved'] = $userData->singlebusiness->approved;
                $outputArray['data']['business_slug'] = $userData->singlebusiness->business_slug;
                if($userData->singlebusiness->membership_type == 1)
                {
                    $outputArray['data']['membership_type'] = 1;
                    $outputArray['data']['membership_type_icon'] = url(Config::get('constant.PREMIUM_ICON_IMAGE'));
                }
                elseif($userData->singlebusiness->membership_type == 2)
                {
                    $outputArray['data']['membership_type'] = 1;
                    $outputArray['data']['membership_type_icon'] = url(Config::get('constant.LIFETIME_PREMIUM_ICON_IMAGE'));

                }
                else
                {
                    $outputArray['data']['membership_type'] = 0;
                    $outputArray['data']['membership_type_icon'] = url(Config::get('constant.BASIC_ICON_IMAGE'));
                }
            }
            else
            {
                $outputArray['data']['business_id'] = '';
                $outputArray['data']['business_name'] = '';
                $outputArray['data']['business_approved'] = 0;
                $outputArray['data']['business_slug'] = '';
                $outputArray['data']['membership_type'] = '';
                $outputArray['data']['membership_type_icon'] = '';
            }
            $imageArray = Helpers::getProfileExtraFields($userData);
            $outputArray['data']['profile_pic_thumbnail'] = $imageArray['profile_pic_thumbnail'];
            $outputArray['data']['profile_pic_original'] = $imageArray['profile_pic_original'];

            $outputArray['data']['first_login'] = ($userData->manual_entry) ? 1 : 0;
            $outputArray['data']['loginToken'] = $token;

            $userInfo = User::find($userData->id);
            $userInfo->manual_entry = 0;
            $userInfo->save();

            return response()->json($outputArray);
        }
    }

    /**
     * To add device token of login user if not exist
     * @param [object] $user
     * @param [string] $deviceToken
     * @return boolean
     */
    public function addDeviceToken($user, $deviceToken, $deviceType, $deviceId)
    {
        try
        {
            $userDeviceToken = UsersDevice::where('user_id', $user->id)->pluck('device_token');
            $userDeviceToken = $userDeviceToken->toArray();
            if (count($userDeviceToken) > 0)
            {
                if(!in_array($deviceToken, $userDeviceToken))
                {
                    $deviceData['user_id'] = $user->id;
                    $deviceData['device_token'] = $deviceToken;
                    $deviceData['device_type'] = $deviceType;
                    $deviceData['device_id'] = $deviceId;
                    UsersDevice::create($deviceData);
                }
            } else {
                $deviceData['user_id'] = $user->id;
                $deviceData['device_token'] = $deviceToken;
                $deviceData['device_type'] = $deviceType;
                $deviceData['device_id'] = $deviceId;
                UsersDevice::create($deviceData);
            }
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }
}
