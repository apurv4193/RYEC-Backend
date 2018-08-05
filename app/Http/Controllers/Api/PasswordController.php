<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
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
use App\User;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class PasswordController extends Controller {

    public function __construct() 
    {
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('password-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
    }

    public function resetPasswordRequest()
    {
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg'), 'data' => []];
        $statusCode = 400;
        $requestData = Input::all();
        try {
            $validator = Validator::make($requestData,
                [
                    'phone' => 'required',
                    'country_code' => 'required'
                ]);
            if ($validator->fails()) {
                $this->log->error('API validation failed while resetPasswordRequest', array('error' => $validator->messages()->all()[0]));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $responseData['data'] = new \stdClass();
                $statusCode = 200;
                return response()->json($outputArray,$statusCode);
            } else {

                $userDetail = User::where('phone', $requestData['phone'])->where('country_code',$requestData['country_code'])->first();
                if(!empty($userDetail)) 
                {
                    $otp = Helpers::genrateOTP();
                    if($requestData['country_code'] == '+91' && $requestData['phone'] != '')
                    {   
                        $code = substr($requestData['country_code'], 1);
                        $phone = $code.$requestData['phone'];
                        $response = Helpers::sendMessage($phone,"RYUVA Club - ".$otp." is the OTP for transaction to reset your account password");
                        if($response['status']) {
                            $userDetail->reset_password_otp = $otp;
                            $userDetail->reset_password_otp_date = date('Y-m-d H:i:s');
                            $userDetail->save();
                            $responseData['status'] = 1;
                            $responseData['message'] = 'OTP has been sent successfully to your mobile '.$requestData['country_code'].$requestData['phone'];
                            $statusCode = 200;
                            $responseData['data'] = new \stdClass();
                        } else {
                            $responseData['message'] = $response['message'];
                            $responseData['data'] = new \stdClass();
                        }
                    } 
                    else if($requestData['country_code'] != '+91' && $requestData['phone'] != '')
                    {

                        // $statusCode = 200;
                        // $this->log->error('API not send otp when number is out of India');
                        // $errorArray = [];
                        // $responseData['message'] = 'To change your password, Please contact support at: +919099937890 or email at: rana@ryuva.club';
                        // $errorArray['error']['errorcode'] = 'foreign_country_code';
                        // $errorArray['error']['contact_number'] = '+919099937890';
                        // $errorArray['error']['email'] = 'rana@ryuva.club';
                        // $responseData['data'] = $errorArray;
                        if($userDetail->email != '')
                        {
                            // start- send mail by helpers function                 
                            $replaceArray = array();
                            $replaceArray['OTP'] = $otp;
                            
                            $et_templatepseudoname = 'reset-password-otp';
                            $emailParametersArray = [
                                                        'toEmail' => $userDetail->email
                                                    ];
                            $toName = $userDetail->name;

                            Helpers::sendMailByTemplate($replaceArray,$et_templatepseudoname,$emailParametersArray,$toName);
                            
                            $userDetail->reset_password_otp = $otp;
                            $userDetail->reset_password_otp_date = date('Y-m-d H:i:s');
                            $userDetail->save();
                            $responseData['status'] = 1;
                            $responseData['message'] = 'OTP has been sent successfully to your email '.$userDetail->email;
                            $statusCode = 200;
                            $responseData['data'] = new \stdClass();
                            // end- send mail by helpers function 
                        }
                        else
                        {
                            $statusCode = 200;
                            $this->log->error('API something went wrong while resetPasswordRequest');
                            $responseData['message'] = 'To change your password, Please contact support at: +919099937890 or email at: rana@ryuva.club';
                            $responseData['data'] = new \stdClass();
                        }
                    }  
                }
                else
                {
                    $statusCode = 200;
                    $this->log->error('API something went wrong while resetPasswordRequest');
                    $responseData['message'] = trans('apimessages.invalid_phone');
                    $responseData['data'] = new \stdClass();
                }

                
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while resetPasswordRequest', array('error' => $e->getMessage()));
            $responseData['message'] = $e->getMessage();
        }

        return response()->json($responseData, $statusCode);
    }

    public function resetPasswordRequestConfirm()
    {
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg'), 'data' => []];
        $statusCode = 400;
        $requestData = Input::all();

        try {
            $validator = Validator::make($requestData,
            [
                'reset_password_otp' => 'required',
                'phone' => 'required',
                'country_code' => 'required'
            ]);
            if ($validator->fails()) {
                $this->log->error('API validation failed while resetPasswordRequestConfirm', array('error' => $validator->messages()->all()[0]));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray,$statusCode);
            } else {
                $userData = User::where('phone', $requestData['phone'])->where('country_code', $requestData['country_code'])->where('reset_password_otp', $requestData['reset_password_otp'])->first();
                if($userData)
                {
                    $otp_sent = Carbon::parse($userData->reset_password_otp_date);
                    $now = Carbon::now();
                    $diff = $otp_sent->diffInMinutes($now);
                    if($diff > 5) {
                        $statusCode = 200;
                        $responseData['status'] = 0;
                        $responseData['message'] = trans('apimessages.otp_expired');
                        $responseData['data'] = new \stdClass();
                    } else {
                        $statusCode = 200;
                        $responseData['status'] = 1;
                        $responseData['message'] = trans('apimessages.otp_verify_successfully');
                        $responseData['data']['reset_password_otp'] = $requestData['reset_password_otp'];
                    }
                }
                else
                {
                    $this->log->error('API something went wrong while resetPasswordRequestConfirm');
                    $statusCode = 200;
                    $responseData = ['status' => 0, 'message' => trans('apimessages.invalid_otp')];
                    $responseData['data'] = new \stdClass();
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while resetPasswordRequestConfirm', array('error' => $e->getMessage()));
            $responseData['message'] = $e->getMessage();
        }

        return response()->json($responseData, $statusCode);
    }

    public function resetPassword()
    {
        $responseData = ['status' => 0, 'message' => trans('apimessages.default_error_msg'), 'data' => []];
        $statusCode = 400;
        $requestData = Input::all();

        try {
            $validator = Validator::make($requestData,
            [
                'reset_password_otp' => 'required',
                'password' => 'required',
                'phone' => 'required',
                'country_code' => 'required'
            ]);
            if ($validator->fails()) {
                $this->log->error('API validation failed while resetPassword', array('error' => $validator->messages()->all()[0]));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray,$statusCode);
            } else {
                $userData = User::where('phone', $requestData['phone'])->where('country_code',$requestData['country_code'])->where('reset_password_otp', $requestData['reset_password_otp'])->first();
                if(!empty($userData))
                {
                    $otp_sent = Carbon::parse($userData->reset_password_otp_date);
                    $now = Carbon::now();
                    $diff = $otp_sent->diffInMinutes($now);
                    if($diff > 5) {
                        $statusCode = 200;
                        $responseData['status'] = 0;
                        $responseData['message'] = trans('apimessages.otp_expired');
                        $responseData['data'] = new \stdClass();
                    } else {
                        $userData->reset_password_otp = '';
                        $userData->reset_password_otp_date = '';
                        $userData->password = bcrypt($requestData['password']);
                        $userData->manual_entry = 0;
                        $userData->save();

                        $statusCode = 200;
                        $responseData['status'] = 1;
                        $responseData['message'] = trans('apimessages.reset_password_msg');
                        $responseData['data'] = new \stdClass();
                    }
                }
                else
                {
                    $statusCode = 200;
                    $responseData = ['status' => 0, 'message' => trans('apimessages.invalid_otp')];
                    $responseData['data'] = new \stdClass();
                }
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while resetPassword', array('error' => $e->getMessage()));
            $responseData['message'] = $e->getMessage();
        }

        return response()->json($responseData, $statusCode);
    }
}
