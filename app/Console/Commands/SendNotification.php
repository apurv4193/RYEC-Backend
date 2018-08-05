<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\NotificationList;
use App\Notification;
use App\Business;
use Helpers;
use Config;
use DB;

class SendNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendNotification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $objNotification = new Notification();
        $objUser = new User();
        $objBusiness = new Business();

        $pendingNotification = $objNotification->where('status',0)->get();
        
        if(!empty($pendingNotification) && count($pendingNotification) > 0)
        {
            foreach($pendingNotification as $notification)
            {
                if($notification->notification_type == 'rajputbusinessregister')
                {
                    $rajputUsers = $objUser->where('isRajput',1)->get();

                    if(!empty($rajputUsers) && count($rajputUsers) > 0)
                    {
                        foreach($rajputUsers as $user)
                        {
                            if(count($user->singlebusiness) == 0)
                            {
                                if($notification->chennel_type == 'push')
                                {
                                    //Send push notification to Rajput User
                                    $notificationData = [];
                                    $notificationData['title'] = 'Rajput Business Register';
                                    $notificationData['message'] = str_replace("[FULL_NAME]",$user->name,$notification->message);
                                    $notificationData['type'] = '6';
                                    Helpers::sendPushNotification($user->id, $notificationData);

                                    // notification list

                                    $notificationListArray = [];
                                    $notificationListArray['user_id'] = $user->id;
                                    $notificationListArray['title'] = 'Rajput Business Register';
                                    $notificationListArray['message'] =  $notificationData['message'];
                                    $notificationListArray['type'] = '6';
                                   

                                    NotificationList::create($notificationListArray);
                                }
                            }
                        }
                    }
                }
                else if($notification->notification_type == 'upgradetopremium')
                {
                    $businesses = $objBusiness->where('approved',1)->where('membership_type',0)->get();

                    if(!empty($businesses) && count($businesses) > 0)
                    {
                        foreach($businesses as $business)
                        {
                            if($notification->chennel_type == 'push')
                            {
                                if(isset($business->user))
                                {
                                    //Send push notification to User who have basic plan
                                    $notificationData = [];
                                    $notificationData['title'] = 'Upgrade To Premium';
                                    $notificationData['message'] = str_replace("[FULL_NAME]",$business->user->name,$notification->message);
                                    $notificationData['type'] = '7';
                                    Helpers::sendPushNotification($business->user_id, $notificationData);

                                    // notification list

                                    $notificationListArray = [];
                                    $notificationListArray['user_id'] = $business->user_id;
                                    $notificationListArray['title'] = 'Upgrade To Premium';
                                    $notificationListArray['message'] =  $notificationData['message'];
                                    $notificationListArray['business_id'] =  $business->id;
                                    $notificationListArray['type'] = '7';
                                   

                                    NotificationList::create($notificationListArray);
                                }
                            }
                        }
                    }
                }
                else if($notification->notification_type == 'membersearchbusiness')
                {
                    $businessArray = [];

                    if($notification->user_id != '')
                    {
                        $businessArray = $objBusiness->whereIn('user_id',explode(',',$notification->user_id))->get();
                    }
                   
                    if(!empty($businessArray) && count($businessArray) > 0)
                    {
                        foreach($businessArray as $business)
                        {
                            if($notification->chennel_type == 'push')
                            {
                                if(isset($business->user))
                                {
                                    $message = str_replace("[FULL_NAME]",$business->user->name,$notification->message);
                                    $message = str_replace("[BUSIENSS_NAME]",$business->name,$message);
                                    //Send push notification to Business owner for search business by customer
                                    $notificationData = [];
                                    $notificationData['title'] = 'Search Business';
                                    $notificationData['message'] = $message;
                                    $notificationData['type'] = '1';
                                    $notificationData['phone'] = $business->user->phone;
                                    $notificationData['country_code'] = $business->user->country_code;
                                    
                                    Helpers::sendPushNotification($business->user_id, $notificationData);

                                    // notification list

                                    $notificationListArray = [];
                                    $notificationListArray['user_id'] = $business->user_id;
                                    $notificationListArray['title'] = 'Search Business';
                                    $notificationListArray['message'] =  $notificationData['message'];
                                    $notificationListArray['business_id'] =  $business->id;
                                    $notificationListArray['user_name'] =  $business->user->name;
                                    $notificationListArray['business_name'] =  $business->name;
                                    $notificationListArray['activity_user_id'] =  $notification->search_by;
                                    $notificationListArray['type'] = '1';
                                   

                                    NotificationList::create($notificationListArray);
                                }
                            }
                        }
                    }
                }
                $notification->status = 1;
                $notification->save();
            }
        }
    }
}
