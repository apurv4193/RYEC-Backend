<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Helpers;
use Config;
use Image;
use File;
use DB;
use Input;
use Redirect;
use App\SubscriptionPlan;
use App\User;
use App\MembershipRequest;
use Crypt;
use Response;
use Carbon\Carbon;
use Mail;
use Session;
use Validator;
use JWTAuth;
use JWTAuthException;
use Cache;
use \stdClass;
use Storage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->objSubscriptionPlan = new SubscriptionPlan();
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('owner-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
        $this->SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH = Config::get('constant.SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH');
        $this->SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH = Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH');
    }
    

    /**
     * Get SubscriptionPlanList
     */
    public function getSubscriptionPlanList(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        $userId = Auth::id();
        try 
        {
            $membershipPlanRequestDetail  =  MembershipRequest::where('user_id',$userId)->get();
            if(count($membershipPlanRequestDetail) > 0)
            {
                $responseData['isPendingRequest'] = "1";
            }
            else
            {
                $responseData['isPendingRequest'] = "0";
            }
            $subscriptionData = $this->objSubscriptionPlan->getAll();

            $mainArray = [];
            if(count($subscriptionData) > 0)
            {
                foreach($subscriptionData as $subscription)
                {

                    $checkPendingRequest  =  MembershipRequest::where('user_id',$userId)->where('subscription_plans_id',$subscription->id)->where('status',0)->get();
                    
                    if($checkPendingRequest && count($checkPendingRequest) > 0)
                        $listArray['pendingStatus'] = 1;
                    else
                        $listArray['pendingStatus'] = 0;

                    $listArray['id'] = $subscription->id;
                    $listArray['name'] = $subscription->name;
                    $listArray['descriptions'] = $subscription->description;
                    $listArray['months'] = $subscription->months;
                    $listArray['price'] = $subscription->price;

                    $planLogoThumbImgPath = ((isset($subscription->logo) && !empty($subscription->logo)) && Storage::size(Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH').$subscription->logo) > 0) ? Storage::url(Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH').$subscription->logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $planLogoOriginalImgPath = ((isset($subscription->logo) && !empty($subscription->logo)) && Storage::size(Config::get('constant.SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH').$subscription->logo) > 0) ? Storage::url(Config::get('constant.SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH').$subscription->logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                    $type = '';
                    if(isset($subscription->months) && $subscription->months >= 12) {
                        $type = $subscription->months/12;

                        if($type == 1)
                        {
                            $type = $type.' Year';
                        }
                        else
                        {
                            $type = $type.' Years';
                        }
                    }
                    else{
                        $type = $subscription->months;
                        if($type == 1)
                        {
                            $type = $type.' Month';
                        }
                        else
                        {
                            $type = $type.' Months';
                        }
                    } 

                    $listArray['type'] = $type;

                    $listArray['logo_thumbnail'] = $planLogoThumbImgPath;
                    $listArray['logo_original'] = $planLogoOriginalImgPath;

                    $mainArray[] = $listArray;
                }    
                
                $this->log->info('API SubscriptionPlan List get successfully', array('login_user_id' => Auth::id()));  
                $responseData['status'] = 1;
                $responseData['message'] =  trans('apimessages.get_subscription_plan');
                $responseData['data'] =  $mainArray;
                $statusCode = 200;
            }
            else
            {
                $this->log->error('API No record found while get SubscriptionPlan', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = trans('apimessages.norecordsfound');
                $responseData['data'] =  $mainArray;
                $statusCode = 200;
            }
            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while get SubscriptionPlan', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode); 
    }

    /**
     * Get getSubscriptionPlanDetail
     */
    public function getSubscriptionPlanDetail(Request $request)
    {

        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        
        try 
        {
            $validator = Validator::make($request->all(), [
                'subscription_id' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while getting SubscriptionPlan detail', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $subscriptionData = $this->objSubscriptionPlan->find($requestData['subscription_id']);

                $listArray = [];
                if(count($subscriptionData) > 0)
                {
                    $listArray['name'] = $subscriptionData->name;
                    $listArray['description'] = $subscriptionData->description;
                    $listArray['months'] = $subscriptionData->months;
                    $listArray['price'] = $subscriptionData->price;
                     
                    $this->log->info('API SubscriptionPlan detail get successfully', array('login_user_id' => Auth::id()));   
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.get_subscription_plan_details');
                    $responseData['data'] =  $listArray;
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API No record found while get SubscriptionPlan detail', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.norecordsfound');
                    $responseData['data'] =  $listArray;
                    $statusCode = 200;
                }
            }
            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while get SubscriptionPlan detail', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode); 
    }

    /**
     * Get getCurrentSubscriptionPlan
     */
    public function getCurrentSubscriptionPlan(Request $request)
    {

        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        $user = JWTAuth::parseToken()->authenticate();
        try 
        {
            $userDetail = User::find($user->id);
            $membeshipPlan = $userDetail->singlebusiness->businessMembershipPlans;
            $this->log->info('API SubscriptionPlan detail get successfully', array('login_user_id' => Auth::id()));  
            $mainArray = [];
            if(count($membeshipPlan) > 0)
            {
                foreach($membeshipPlan as $plan)
                {
                    $listArray = [];
                    $listArray['id'] = $plan->id;
                    $listArray['start_date'] = $plan->start_date;
                    $listArray['end_date'] = $plan->end_date;
                    $listArray['actual_payment'] = $plan->actual_payment;
                    $listArray['agent_commision'] = $plan->agent_commision;
                    $listArray['net_payment'] = $plan->net_payment;
                    $listArray['comments'] = $plan->comments;
                    $listArray['subscription_plan_id'] = $plan->subscription_plan_id;

                    if(isset($plan->subscriptionPlan))
                    {
                        $listArray['name'] = $plan->subscriptionPlan->name;
                        $listArray['descriptions'] = $plan->subscriptionPlan->description;
                        $listArray['months'] = $plan->subscriptionPlan->months;
                        $listArray['price'] = $plan->subscriptionPlan->price;

                        $planLogoThumbImgPath = ((isset($plan->subscriptionPlan->logo) && !empty($plan->subscriptionPlan->logo)) && Storage::size(Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH').$plan->subscriptionPlan->logo) > 0) ? Storage::url(Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH').$plan->subscriptionPlan->logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $planLogoOriginalImgPath = ((isset($plan->subscriptionPlan->logo) && !empty($plan->subscriptionPlan->logo)) && Storage::size(Config::get('constant.SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH').$plan->subscriptionPlan->logo) > 0) ? Storage::url(Config::get('constant.SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH').$plan->subscriptionPlan->logo) : url(Config::get('constant.DEFAULT_IMAGE'));
                        $type = '';
                        if(isset($plan->subscriptionPlan->months) && $plan->subscriptionPlan->months >= 12) {
                            $type = $plan->subscriptionPlan->months/12;

                            if($type == 1)
                            {
                                $type = $type.' Year';
                            }
                            else
                            {
                                $type = $type.' Years';
                            }
                        }
                        else{
                            $type = $plan->subscriptionPlan->months;
                            if($type == 1)
                            {
                                $type = $type.' Month';
                            }
                            else
                            {
                                $type = $type.' Months';
                            }
                        } 
                        
                        $listArray['type'] = $type;
                        $listArray['logo_thumbnail'] = $planLogoThumbImgPath;
                        $listArray['logo_original'] = $planLogoOriginalImgPath; 


                    }
                    

                    $mainArray[] = $listArray;

                }
            } 
            $responseData['status'] = 1;
            $responseData['message'] =  trans('apimessages.get_current_subscription_plan');
            $responseData['data']['currentPlans'] =  $mainArray;
            $statusCode = 200;
            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while get CurrentSubscriptionPlan', array('login_user_id' => $user->id, 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode); 
    }
}
