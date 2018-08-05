<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CategoryRequest;
use Auth;
use Helpers;
use Config;
use Image;
use File;
use DB;
use Input;
use Redirect;
use App\Chats;
use App\ChatMessages;
use App\User;
use App\UsersDevice;
use App\UserRole;
use App\UserMetaData;
use App\NotificationList;
use App\Category;
use App\Business;
use App\BusinessImage;
use Crypt;
use Response;
use Carbon\Carbon;
use Mail;
use Session;
use Cache;
use Validator;
use JWTAuth;
use JWTAuthException;
use \stdClass;
use Storage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ChatsController extends Controller
{
    public function __construct()
    {
        $this->objChats = new Chats();
        $this->objChatMessages = new ChatMessages();
        $this->objCategory = new Category();
        $this->objBusiness = new Business(); 
        $this->objBusinessImage = new BusinessImage();
        $this->objUser = new User();
        $this->objUsersDevice = new UsersDevice();
        $this->objUserMetaData = new UserMetaData();
        $this->objUserRole = new UserRole();
        
        $this->BUSINESS_ORIGINAL_IMAGE_PATH = Config::get('constant.BUSINESS_ORIGINAL_IMAGE_PATH');
        $this->BUSINESS_THUMBNAIL_IMAGE_PATH = Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH');
        
        $this->USER_ORIGINAL_IMAGE_PATH = Config::get('constant.USER_ORIGINAL_IMAGE_PATH');
        $this->USER_THUMBNAIL_IMAGE_PATH = Config::get('constant.USER_THUMBNAIL_IMAGE_PATH');
        $this->USER_PROFILE_PIC_WIDTH = Config::get('constant.USER_PROFILE_PIC_WIDTH');
        $this->USER_PROFILE_PIC_HEIGHT = Config::get('constant.USER_PROFILE_PIC_HEIGHT');
        
        $this->catgoryTempImage = Config::get('constant.CATEGORY_TEMP_PATH');
        
        $this->loggedInUser = Auth::guard();
        $this->log = new Logger('chats-controller');
        $this->log->pushHandler(new StreamHandler(storage_path().'/logs/monolog-'.date('m-d-Y').'.log'));
        
        $this->controller = 'ChatsController';
    }
    
    /**
     * Get New Thread Messages
     */
    public function getNewThreadMessages(Request $request)
    {   
        $headerData = $request->header('Platform');
        $outputArray = [];
        $user = JWTAuth::parseToken()->authenticate();
        $chatId = (isset($request->thread_id) && !empty($request->thread_id)) ? $request->thread_id : 0;
        try 
        {
            $filters = [];
            $filters['chat_id'] = $chatId;
            $filters['updated_at'] = 'updated_at';
            $filters['read_by_id'] = $user->id;
            $getAllChatMessages = $this->objChatMessages->getAll($filters);               
            if($getAllChatMessages && count($getAllChatMessages) > 0)
            {
                foreach($getAllChatMessages as $uKey => $uValue)
                {
                    $updateData['id'] = $uValue->id;
                    $updateData['read_by'] = $uValue->read_by.','.$user->id;
                    $updatevalue = $this->objChatMessages->insertUpdate($updateData);
                }
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.get_new_thread_messages_count_successfully');
                $statusCode = 200;
                $outputArray['data'] = array();
                $outputArray['data']['unread_count'] = count($getAllChatMessages);
                $outputArray['data']['messages'] = array();
                $i = 0;
                foreach($getAllChatMessages as $keyThread => $valueThread)
                {
                    $outputArray['data']['messages'][$i]['id'] = $valueThread->id;
                    $outputArray['data']['messages'][$i]['message'] = $valueThread->message;
                    $outputArray['data']['messages'][$i]['posted_by'] = $valueThread->posted_by;
                    $outputArray['data']['messages'][$i]['timestamp'] = strtotime($valueThread->updated_at)*1000;
                    $i++;  
                }
            }
            else
            {
                $this->log->info('API getNewThreadMessages no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = new stdClass();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getNewThreadMessages', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = 400;
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
        
    }
    
    /**
     * Get Unread Threads Count
     */
    public function getUnreadThreadsCount(Request $request) 
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
	$user = JWTAuth::parseToken()->authenticate();
        try 
        {
            $getAllChatThreads = $this->objChats->getUnreadThreadsCount($user->id);
            if($getAllChatThreads && $getAllChatThreads > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.get_unread_threads_count_successfully');
                $statusCode = 200;
                $outputArray['data'] = array();
                $outputArray['data']['unread_count'] = $getAllChatThreads;
            }
            else
            {
                $this->log->info('API getUnreadThreadsCount no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $statusCode = 200;
                $outputArray['data'] = array();
                $outputArray['data']['unread_count'] = 0;
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getUnreadThreadsCount', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = 400;
            return response()->json($outputArray, $statusCode);
	}
        return response()->json($outputArray, $statusCode);
    }
    
    /**
     * Send Enquiry Message
     */
    public function sendEnquiryMessage(Request $request)
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
	$data = [];
        $user = JWTAuth::parseToken()->authenticate();
	$requestData = array_map('trim', $request->all());
	try
	{
            DB::beginTransaction();            
            $validator = Validator::make($requestData, 
                [
                    'message' => 'required',
                    'thread_id' => 'required'
                ]
            );            
            if ($validator->fails()) 
            {
                DB::rollback(); 
                $this->log->error('API validation failed while sendEnquiryMessage', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 0;
                $outputArray['message'] = $validator->messages()->all()[0];
                $statusCode = 200;
                return response()->json($outputArray,$statusCode);
            }
            else
            {  
                $data['chat_id'] = $requestData['thread_id'];
                $data['message'] = $requestData['message'];
                $data['posted_by'] = $user->id;
                $data['read_by'] = $user->id;
                $messages = ChatMessages::where('chat_id', $requestData['thread_id'])->where('posted_by', $user->id)->get();
                $insertMessages = $this->objChatMessages->insertUpdate($data);
                if($insertMessages)
                {                    
                    if($messages->count() == 0) {
                        $chatThread = Chats::find($requestData['thread_id']);                        
                        $notificationData = [];
                        $notificationData['title'] = 'Enquiry Response';
                        if($chatThread->type == 3)
                        {
                            $notificationData['message'] = 'Dear '.$chatThread->getUserMember->name.',  You got response for your enquiry on business visited from '.$chatThread->getUser->name.'.';
                        }
                        else
                        {
                            $notificationData['message'] = 'Dear '.$chatThread->getUser->name.',  You got response for your enquiry on business '.$chatThread->getBusiness->name.'.  Find out what they said.';
                        }
                        
                        $notificationData['type'] = '5';
                        $notificationData['thread_id'] = $requestData['thread_id'];
                        $notificationData['business_id'] = $chatThread->getBusiness->id;
                        $notificationData['business_name'] = $chatThread->getBusiness->name;
                        if($chatThread->type == 3)
                        {
                            Helpers::sendPushNotification($chatThread->getUserMember->id, $notificationData);
                        }
                        else
                        {
                            Helpers::sendPushNotification($chatThread->getUser->id, $notificationData);
                        }

                        //notification list
                        $notificationListArray = [];
                        $notificationListArray['user_id'] = $chatThread->getUser->id;
                        $notificationListArray['business_id'] = $chatThread->getBusiness->id;
                        $notificationListArray['title'] = 'enquiry Response';
                        if($chatThread->type == 3)
                        {
                            $notificationListArray['message'] =  'Dear '.$chatThread->getUserMember->name.',  You got response for your enquiry on business visited from '.$chatThread->getUser->name.'.';
                        }
                        else
                        {
                            $notificationListArray['message'] =  'Dear '.$chatThread->getUser->name.',  You got response for your enquiry on business '.$chatThread->getBusiness->name.'.  Find out what they said.';
                        }

                        $notificationListArray['type'] = '5';
                        $notificationListArray['business_name'] = $chatThread->getBusiness->name;
                        $notificationListArray['user_name'] = $chatThread->getUser->name;
                        $notificationListArray['thread_id'] = $requestData['thread_id'];
                        $notificationListArray['activity_user_id'] = $user->id;


                        NotificationList::create($notificationListArray);
                    }

                    DB::commit();
                    $insert = [];
                    $insert['id'] = $data['chat_id'];
                    $insert['updated_at'] = $insertMessages->updated_at;
                    $insert['customer_read_flag'] = 1;
                    $insert['member_read_flag'] = 1;

                    $insertData = $this->objChats->insertUpdate($insert);
                    if($insertData)
                    {
                        $outputArray['status'] = 1;
                        $outputArray['message'] = trans('apimessages.send_enquiry_message_added_successfully');
                        $statusCode = 200;
                    }
                    else
                    {
                        $this->log->error('API something went wrong while sendEnquiryMessage', array('login_user_id' => Auth::id()));
                        $outputArray['status'] = 1;
                        $outputArray['message'] = trans('apimessages.default_error_msg');
                        $statusCode = 200;
                        $outputArray['data'] = array();
                    }                    
                }
                else
                {
                    DB::rollback();
                    $this->log->error('API something went wrong while sendEnquiryMessage', array('login_user_id' => Auth::id()));
                    $outputArray['status'] = 0;
                    $outputArray['message'] = trans('apimessages.default_error_msg');
                    $statusCode = 400;
                }
            }
	} catch (Exception $e) {
            DB::rollback();
            $this->log->error('API something went wrong while sendEnquiryMessage', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
            return response()->json($outputArray, $statusCode);
	}
	return response()->json($outputArray, $statusCode);
    }
    
    /**
     * Get Thread Messages
     */
    public function getThreadMessages(Request $request)
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
        $user = JWTAuth::parseToken()->authenticate();
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        $chatId = (isset($request->thread_id) && !empty($request->thread_id)) ? $request->thread_id : 0;
        try 
        {
            $chatsDetails = Chats::find($chatId);
            if($chatsDetails && count($chatsDetails) > 0)
            {
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.get_thread_messages_fetched_successfully');
                $getUnreadThreadsCount = $this->objChats->getUnreadThreadsCount($user->id);
                $outputArray['unread_count'] = $getUnreadThreadsCount;
                $statusCode = 200;
                $outputArray['data'] = array();
                $outputArray['data']['id'] = $chatsDetails->id;
                $outputArray['data']['title'] = $chatsDetails->title;
                $outputArray['data']['business_id'] = $chatsDetails->business_id;
                $outputArray['data']['customer_business_id'] = (isset($chatsDetails->getUser->singlebusiness) && (isset($chatsDetails->getUser->singlebusiness->id))) ? (string)$chatsDetails->getUser->singlebusiness->id : '';
                $outputArray['data']['customer_business_name'] = (isset($chatsDetails->getUser->singlebusiness) && (isset($chatsDetails->getUser->singlebusiness->name))) ? (string)$chatsDetails->getUser->singlebusiness->name : '';
                if($chatsDetails->customer_id == $user->id)
                {
                    $imgThumbUrl = ((isset($chatsDetails->getBusiness) && isset($chatsDetails->getBusiness->businessImagesById) && !empty($chatsDetails->getBusiness->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$chatsDetails->getBusiness->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$chatsDetails->getBusiness->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));

                    $outputArray['data']['image_url'] = $imgThumbUrl;
                    $outputArray['data']['name'] = (isset($chatsDetails->getBusiness) && $chatsDetails->getBusiness->name != '') ? $chatsDetails->getBusiness->name : '';
                    $outputArray['data']['type'] = 'business';
                }
                else
                {
                    $imgUserThumbUrl = ((isset($chatsDetails->getUser) && !empty($chatsDetails->getUser->profile_pic)) && Storage::size(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$chatsDetails->getUser->profile_pic) > 0) ? Storage::url(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$chatsDetails->getUser->profile_pic) : url(Config::get('constant.DEFAULT_IMAGE'));

                    $outputArray['data']['image_url'] = $imgUserThumbUrl;
                    $outputArray['data']['name'] = (isset($chatsDetails->getUser) && !empty($chatsDetails->getUser->name)) ? $chatsDetails->getUser->name : '';
                    $outputArray['data']['type'] = 'customer';
                }
                
                $filters = [];
                $filters['chat_id'] = $chatId;
                $filters['updated_at'] = 'updated_at';
                $filters['read_by_id'] = $user->id;
                $getReadByChatMessages = $this->objChatMessages->getAll($filters);               
                if(count($getReadByChatMessages) > 0)
                {
                    foreach($getReadByChatMessages as $uKey => $uValue)
                    {
                        $updateData['id'] = $uValue->id;
                        $updateData['read_by'] = $uValue->read_by.','.$user->id;
                        $updatevalue = $this->objChatMessages->insertUpdate($updateData);
                    }
                }
                if($headerData == Config::get('constant.WEBSITE_PLATFORM'))
                {
                    $filters = [];
                    $filters['chat_id'] = $chatId;
                    $filters['updated_at'] = 'updated_at';
                    $offset = Helpers::getWebChatOffset($pageNo);
                    $filters['skip'] = $offset;            
                    $filters['take'] = Config::get('constant.WEB_CHAT_RECORD_PER_PAGE');
                }
                else
                {
                    $filters = [];
                    $filters['chat_id'] = $chatId;
                    $filters['updated_at'] = 'updated_at';
                    $offset = Helpers::getMobileChatOffset($pageNo);
                    $filters['skip'] = $offset;            
                    $filters['take'] = Config::get('constant.MOBILE_CHAT_RECORD_PER_PAGE'); 
                }
                $getAllChatMessages = $this->objChatMessages->getAll($filters);
                $outputArray['loadMore'] = 0;
                $outputArray['data']['messages'] = array();
                
                if($getAllChatMessages && count($getAllChatMessages) > 0)
                {
                    if($headerData == Config::get('constant.WEBSITE_PLATFORM'))
                    {
                        if($getAllChatMessages->count() < Config::get('constant.WEB_CHAT_RECORD_PER_PAGE'))
                        {
                            $outputArray['loadMore'] = 0;
                        } else {
                            $offset = Helpers::getWebChatOffset($pageNo+1);
                            $filters = [];
                            $filters['chat_id'] = $chatId;
                            $filters['offset'] = $offset;  
                            $filters['take'] = Config::get('constant.WEB_CHAT_RECORD_PER_PAGE');
                            $chatThreadCount = $this->objChatMessages->getAll($filters);
                            $outputArray['loadMore'] = (count($chatThreadCount) > 0) ? 1 : 0 ;
                        }
                    }
                    else
                    {
                        if($getAllChatMessages->count() < Config::get('constant.MOBILE_CHAT_RECORD_PER_PAGE'))
                        {
                            $outputArray['loadMore'] = 0;
                        } else {
                            $offset = Helpers::getMobileChatOffset($pageNo+1);
                            $filters = [];
                            $filters['chat_id'] = $chatId;
                            $filters['offset'] = $offset;  
                            $filters['take'] = Config::get('constant.MOBILE_CHAT_RECORD_PER_PAGE');
                            $chatThreadCount = $this->objChatMessages->getAll($filters);
                            $outputArray['loadMore'] = (count($chatThreadCount) > 0) ? 1 : 0 ;
                        } 
                    }                    
                    
                    $i = 0;
                    foreach($getAllChatMessages as $keyThread => $valueThread)
                    {
                        $outputArray['data']['messages'][$i]['id'] = $valueThread->id;
                        $outputArray['data']['messages'][$i]['message'] = $valueThread->message;
                        $outputArray['data']['messages'][$i]['posted_by'] = $valueThread->posted_by;
                        $outputArray['data']['messages'][$i]['timestamp'] = strtotime($valueThread->updated_at)*1000;
                        $i++;  
                    }
                }
            } 
            else
            {
                $this->log->info('API getThreadMessages no records found', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.norecordsfound');
                $outputArray['unread_count'] = 0;
                $statusCode = 200;
                $outputArray['loadMore'] = 0;
                $outputArray['data'] = array();
            }
        } catch (Exception $e) {
            $this->log->error('API something went wrong while getThreadMessages', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = 400;
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }
    
    /**
     * Get Thread Listing
     */
    public function getThreadListing(Request $request)
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
        $user = JWTAuth::parseToken()->authenticate();
        $pageNo = (isset($request->page) && !empty($request->page)) ? $request->page : 1;
        try 
        {
            DB::beginTransaction(); 
            if(isset($user->id) && !empty($user->id))
            {
                $filters = [];
                if($headerData == Config::get('constant.WEBSITE_PLATFORM'))
                {
                    $offset = Helpers::getWebOffset($pageNo);
                    $filters['updated_at'] = 'updated_at';
                    $filters['userId'] = $user->id;
                    $filters['skip'] = $offset;            
                    $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                }
                else
                {
                    $filters['userId'] = $user->id;
                    $filters['updated_at'] = 'updated_at';
                    $offset = Helpers::getOffset($pageNo);
                    $filters['skip'] = $offset;            
                    $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE'); 
                }
                $getAllChatThreads = $this->objChats->getAll($filters);

                if($getAllChatThreads && count($getAllChatThreads) > 0)
                {
                    $outputArray['status'] = 1;
                    $outputArray['message'] = trans('apimessages.threads_fetched_successfully');
                    $getUnreadThreadsCount = $this->objChats->getUnreadThreadsCount($user->id);
                    $outputArray['unread_count'] = $getUnreadThreadsCount;
                    $statusCode = 200;

                    if($headerData == Config::get('constant.WEBSITE_PLATFORM'))
                    {
                        if($getAllChatThreads->count() < Config::get('constant.WEBSITE_RECORD_PER_PAGE'))
                        {
                            $outputArray['loadMore'] = 0;
                        } else{
                            $offset = Helpers::getWebOffset($pageNo+1);
                            $filters = [];
                            $filters['userId'] = $user->id;
                            $filters['updated_at'] = 'updated_at';
                            $filters['offset'] = $offset;  
                            $filters['take'] = Config::get('constant.WEBSITE_RECORD_PER_PAGE');
                            $chatThreadCount = $this->objChats->getAll($filters);
                            $outputArray['loadMore'] = (count($chatThreadCount) > 0) ? 1 : 0 ;
                        }
                    }
                    else
                    {
                        if($getAllChatThreads->count() < Config::get('constant.API_RECORD_PER_PAGE'))
                        {
                            $outputArray['loadMore'] = 0;
                        } else{
                            $offset = Helpers::getOffset($pageNo+1);
                            $filters = [];
                            $filters['userId'] = $user->id;
                            $filters['updated_at'] = 'updated_at';
                            $filters['offset'] = $offset;  
                            $filters['take'] = Config::get('constant.API_RECORD_PER_PAGE');
                            $chatThreadCount = $this->objChats->getAll($filters);
                            $outputArray['loadMore'] = (count($chatThreadCount) > 0) ? 1 : 0 ;
                        } 
                    }                
                    $outputArray['data'] = array();
                    $i = 0;
                    foreach($getAllChatThreads as $keyThreads => $valueThreads)
                    {
                        $outputArray['data'][$i]['id'] = $valueThreads->id;
                        $outputArray['data'][$i]['title'] = $valueThreads->title;

                        if($valueThreads->customer_id == $user->id)
                        {
                            // if(isset($valueThreads->getBusiness) && isset($valueThreads->getBusiness->businessImagesById) && !empty($valueThreads->getBusiness->businessImagesById->image_name))
                            // {
                            //     $imgThumbPath = $this->BUSINESS_THUMBNAIL_IMAGE_PATH.$valueThreads->getBusiness->businessImagesById->image_name;                       $imgThumbUrl = (!empty($imgThumbPath)) ? url($imgThumbPath) : url($this->catgoryTempImage);
                            // }
                            $imgThumbUrl = ((isset($valueThreads->getBusiness) && isset($valueThreads->getBusiness->businessImagesById) && !empty($valueThreads->getBusiness->businessImagesById->image_name)) && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$valueThreads->getBusiness->businessImagesById->image_name) > 0) ? Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$valueThreads->getBusiness->businessImagesById->image_name) : url(Config::get('constant.DEFAULT_IMAGE'));

                            $outputArray['data'][$i]['image_url'] = $imgThumbUrl;
                            $outputArray['data'][$i]['name'] = (isset($valueThreads->getBusiness) && !empty($valueThreads->getBusiness->name)) ? $valueThreads->getBusiness->name : '';
                        }
                        else
                        {
                            // if(isset($valueThreads->getUser) && !empty($valueThreads->getUser->profile_pic))
                            // {
                            //     $imgThumbPath = $this->USER_THUMBNAIL_IMAGE_PATH.$valueThreads->getUser->profile_pic;
                            //     $imgUserThumbUrl = (!empty($imgThumbPath)) ? url($imgThumbPath) : url($this->catgoryTempImage);
                            // }
                            $imgUserThumbUrl = ((isset($valueThreads->getUser) && !empty($valueThreads->getUser->profile_pic)) && Storage::size(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$valueThreads->getUser->profile_pic) > 0) ? Storage::url(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$valueThreads->getUser->profile_pic) : url(Config::get('constant.DEFAULT_IMAGE'));
                            
                            $outputArray['data'][$i]['image_url'] = $imgUserThumbUrl;
                            $outputArray['data'][$i]['name'] = (isset($valueThreads->getUser) && !empty($valueThreads->getUser->name)) ? $valueThreads->getUser->name : '';
                        }

                        if(isset($valueThreads->getChatMessages) && count($valueThreads->getChatMessages) > 0)
                        {
                            $unreadCount = $valueThreads->getChatMessages()->whereRaw("NOT FIND_IN_SET(".$user->id.", read_by)")->count();
                        }

                        $outputArray['data'][$i]['unread_count'] = (isset($unreadCount) && $unreadCount > 0) ? $unreadCount : 0;
                        $outputArray['data'][$i]['customer_business_id'] = (isset($valueThreads->getUser->singlebusiness->id) && $valueThreads->getUser->singlebusiness->id != '') ? $valueThreads->getUser->singlebusiness->id : '';
                        $outputArray['data'][$i]['customer_business_name'] = (isset($valueThreads->getUser->singlebusiness->name) && $valueThreads->getUser->singlebusiness->name != '') ? $valueThreads->getUser->singlebusiness->name : '';
                        if(isset($valueThreads->getChatMessages) && count($valueThreads->getChatMessages) > 0)
                        {
                            $lastMessageData = $valueThreads->getChatMessages()->limit(1)->orderBy('updated_at', 'DESC')->first();
                            if($lastMessageData)
                            {
                                $outputArray['data'][$i]['last_message'] = array();
                                $outputArray['data'][$i]['last_message']['id'] = $lastMessageData->id;
                                $outputArray['data'][$i]['last_message']['message'] = $lastMessageData->message;
                                $outputArray['data'][$i]['last_message']['timestamp'] = strtotime($lastMessageData->updated_at)*1000;
                            }
                            else
                            {
                                $outputArray['data'][$i]['last_message'] = new stdClass();
                            }
                        }
                        else
                        {
                            $outputArray['data'][$i]['last_message'] = new stdClass();
                        }
                        $i++;  
                    }
                }
                else
                {
                    DB::rollback(); 
                    $this->log->info('API getThreadListing no records found', array('login_user_id' => Auth::id()));
                    $outputArray['status'] = 1;
                    $outputArray['message'] = trans('apimessages.norecordsfound');
                    $outputArray['unread_count'] = 0;
                    $statusCode = 200;
                    $outputArray['data'] = array();
                }  
            }
            else
            {
                DB::rollback(); 
                $this->log->error('API something went wrong while getThreadListing', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 1;
                $outputArray['message'] = trans('apimessages.invalid_user');
                $statusCode = 200;
                $outputArray['data'] = array();
            } 
                      
        } catch (Exception $e) {
            DB::rollback(); 
            $this->log->error('API something went wrong while getThreadListing', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $outputArray['status'] = 0;
            $outputArray['message'] = $e->getMessage();
            $statusCode = 400;
            return response()->json($outputArray, $statusCode);
        }
        return response()->json($outputArray, $statusCode);
    }
    
     /**
     * Send Enquiry
     */
    public function sendEnquiry(Request $request)
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
	    $data = [];
        $user = JWTAuth::parseToken()->authenticate();        
	    $requestData = array_map('trim', $request->all());
    	try
    	{
                DB::beginTransaction();            
                $validator = Validator::make($requestData, 
                [
                    'title' => 'required',
                    'business_id' => 'required'
                ]
                );            
                if ($validator->fails()) 
                {
                    DB::rollback(); 
                    $this->log->error('API validation failed while sendEnquiry', array('login_user_id' => Auth::id()));
                    $outputArray['status'] = 0;
                    $outputArray['message'] = $validator->messages()->all()[0];
                    $statusCode = 200;
                    return response()->json($outputArray,$statusCode);
                }
                else
                {  
                    $businessId = $requestData['business_id'];
                    $getBusinessData = Business::find($businessId);   
                    if($getBusinessData && count($getBusinessData) > 0)
                    {
                        $data['title'] = $requestData['title'];
                        $data['business_id'] = $businessId;
                        $data['customer_id'] = $user->id;
                        $data['member_id'] = $getBusinessData->user_id;
                        $insertData = $this->objChats->insertUpdate($data);
                        if($insertData)
                        {
                            $insert = [];
                            $insert['chat_id'] = $insertData->id;
                            $insert['message'] = ($requestData['message'] == '') ? 'You have got new enquiry' : $requestData['message'];
                            $insert['posted_by'] = $user->id;
                            $insert['read_by'] = $user->id;
                            $insertMessages = $this->objChatMessages->insertUpdate($insert);
                            if($insertMessages)
                            {
                                //Send push notification to Business User
                                $notificationData = [];
                                $notificationData['title'] = 'New Enquiry Recieved';
                                if(isset($user->singlebusiness->name) && $user->singlebusiness->name != '')
                                {
                                    $notificationData['message'] = 'Dear '.$getBusinessData->user->name.',  You have got an enquiry from '.$user->name.' '.$user->phone.'of '.$user->singlebusiness->name.' on your business.';
                                }
                                else
                                {
                                    $notificationData['message'] = ($user->gender != 2) ? 'Dear '.$getBusinessData->user->name.',  You have got an enquiry from '.$user->name.' '.$user->phone.' on your business.' : 'Dear '.$getBusinessData->user->name.',  You have got an enquiry from '.$user->name.' on your business.';
                                }
                                
                                $notificationData['type'] = '2';
                                $notificationData['thread_id'] = $insertData->id;
                                $notificationData['business_id'] = $getBusinessData->id;
                                $notificationData['business_name'] = $getBusinessData->name;
                                $notificationData['user_business_id'] = (isset($user->singlebusiness->id))? $user->singlebusiness->id : '';
                                $notificationData['user_business_name'] = (isset($user->singlebusiness->name))? $user->singlebusiness->name : '';
                                Helpers::sendPushNotification($getBusinessData->user_id, $notificationData);

                                //notification list
                                $notificationListArray = [];
                                $notificationListArray['user_id'] = $getBusinessData->user_id;
                                $notificationListArray['business_id'] = $getBusinessData->id;
                                $notificationListArray['title'] = 'New Enquiry Recieved';
                                $notificationListArray['message'] = ($user->gender != 2) ? 'Dear '.$getBusinessData->user->name.',  You have got an enquiry from '.$user->name.' '.$user->phone.' on your business.' : 'Dear '.$getBusinessData->user->name.',  You have got an enquiry from '.$user->name.' on your business.';
                                $notificationListArray['type'] = '2';
                                $notificationListArray['business_name'] = $getBusinessData->name;
                                $notificationListArray['user_name'] = $getBusinessData->user->name;
                                $notificationListArray['thread_id'] = $insertData->id;
                                $notificationListArray['activity_user_id'] = $user->id;


                                NotificationList::create($notificationListArray);

                                DB::commit();
                                $outputArray['status'] = 1;
                                $outputArray['message'] = trans('apimessages.send_enquiry_added_successfully');
                                $statusCode = 200;
                            }
                            else
                            {
                                DB::rollback();
                                $this->log->error('API something went wrong while sendEnquiry', array('login_user_id' => Auth::id()));
                                $outputArray['status'] = 0;
                                $outputArray['message'] = trans('apimessages.default_error_msg');
                                $statusCode = 400;
                            }
                        }
                        else
                        {
                            DB::rollback();
                            $this->log->error('API something went wrong while sendEnquiry', array('login_user_id' => Auth::id()));
                            $outputArray['status'] = 0;
                            $outputArray['message'] = trans('apimessages.default_error_msg');
                            $statusCode = 400;
                        }
                    }
                    else
                    {
                        $this->log->info('API sendEnquiry no records found', array('login_user_id' => Auth::id()));
                        $outputArray['status'] = 1;
                        $outputArray['message'] = trans('apimessages.no_business_recordsfound');
                        $statusCode = 200;
                        $outputArray['data'] = array();
                    }
                }
    	} 
        catch (Exception $e) 
        {
                DB::rollback();
                $this->log->error('API something went wrong while sendEnquiry', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 0;
                $outputArray['message'] = $e->getMessage();
                $statusCode = $e->getStatusCode();
                return response()->json($outputArray, $statusCode);
    	}
    	   return response()->json($outputArray, $statusCode);
    }
    
    /**
     * Send Enquiry To Customer
     */
    public function sendEnquiryToCustomer(Request $request)
    {
        $headerData = $request->header('Platform');
        $outputArray = [];
        $data = [];
        $user = JWTAuth::parseToken()->authenticate();       
        $requestData = array_map('trim', $request->all());
        try
        {
                DB::beginTransaction();            
                $validator = Validator::make($requestData, 
                [
                    'customer_id' => 'required',
                    'message' => 'required'
                ]
                );            
                if ($validator->fails()) 
                {
                    DB::rollback(); 
                    $this->log->error('API validation failed while sendEnquiry to customer', array('login_user_id' => Auth::id(),'customer_id' => $request->customer_id));
                    $outputArray['status'] = 0;
                    $outputArray['message'] = $validator->messages()->all()[0];
                    $statusCode = 200;
                    return response()->json($outputArray,$statusCode);
                }
                else
                {  
                    $userDetail = User::find($user->id);

                    if(isset($userDetail->singlebusiness) && count($userDetail->singlebusiness) > 0)
                    {
                        $data['title'] = 'Re: Business Visited';
                        $data['business_id'] = $userDetail->singlebusiness->id;
                        $data['customer_id'] = $request->customer_id;
                        $data['member_id'] = $userDetail->id;
                        $data['type'] = 3;
                        $insertData = $this->objChats->insertUpdate($data);
                        if($insertData)
                        {
                            $insert = [];
                            $insert['chat_id'] = $insertData->id;
                            $insert['message'] = $request->message;
                            $insert['posted_by'] = $user->id;
                            $insert['read_by'] = $user->id;
                            $insertMessages = $this->objChatMessages->insertUpdate($insert);
                            if($insertMessages)
                            {
                                //Send push notification to Business User
                                $notificationData = [];
                                $notificationData['title'] = 'Re: Business Visited';
                                $customerDetail = User::find($request->customer_id);
                                if(isset($customerDetail->singlebusiness->name) && $customerDetail->singlebusiness->name != '')
                                {
                                    $notificationData['message'] = 'Dear '.$customerDetail->name.',  You have got an enquiry from '.$userDetail->name.' '.$userDetail->phone.' of '.$userDetail->singlebusiness->name.'.';
                                }
                                else
                                {
                                    $notificationData['message'] = ($userDetail->gender != 2) ? 'Dear '.$customerDetail->name.',  You have got an enquiry from '.$userDetail->name.' '.$userDetail->phone.' of '.$userDetail->singlebusiness->name.'.' : 'Dear '.$customerDetail->name.',  You have got an enquiry from '.$userDetail->name.' of '.$userDetail->singlebusiness->name.'.';
                                }
                                
                                $notificationData['type'] = '2';
                                $notificationData['thread_id'] = $insertData->id;
                                $notificationData['business_id'] = (isset($customerDetail->singlebusiness)) ? $customerDetail->singlebusiness->id : '';
                                $notificationData['business_name'] = (isset($customerDetail->singlebusiness)) ? $customerDetail->singlebusiness->name : '';
                                $notificationData['user_business_id'] = (isset($userDetail->singlebusiness->id))? $userDetail->singlebusiness->id : '';
                                $notificationData['user_business_name'] = (isset($userDetail->singlebusiness->name))? $userDetail->singlebusiness->name : '';
                                Helpers::sendPushNotification($customerDetail->id, $notificationData);

                                //notification list
                                $notificationListArray = [];
                                $notificationListArray['user_id'] = $customerDetail->id;
                                $notificationListArray['business_id'] = (isset($customerDetail->singlebusiness)) ? $customerDetail->singlebusiness->id : '';
                                $notificationListArray['title'] = 'Re: business visited';
                                $notificationListArray['message'] = ($userDetail->gender != 2) ? 'Dear '.$customerDetail->name.',  You have got an enquiry from '.$userDetail->name.' '.$userDetail->phone.' on your business.' : 'Dear '.$customerDetail->name.',  You have got an enquiry from '.$userDetail->name.' on your business.';
                                $notificationListArray['type'] = '2';
                                $notificationListArray['business_name'] = (isset($userDetail->singlebusiness->name))? $userDetail->singlebusiness->name : '';
                                $notificationListArray['user_name'] = $customerDetail->name;
                                $notificationListArray['thread_id'] = $insertData->id;
                                $notificationListArray['activity_user_id'] = $userDetail->id;


                                NotificationList::create($notificationListArray);

                                DB::commit();
                                $outputArray['status'] = 1;
                                $outputArray['message'] = trans('apimessages.send_enquiry_added_successfully');
                                $statusCode = 200;
                            }
                            else
                            {
                                DB::rollback();
                                $this->log->error('API something went wrong while sendEnquiry', array('login_user_id' => Auth::id(), 'customer_id' => $request->customer_id));
                                $outputArray['status'] = 0;
                                $outputArray['message'] = trans('apimessages.default_error_msg');
                                $statusCode = 400;
                            }
                        }
                        else
                        {
                            DB::rollback();
                            $this->log->error('API something went wrong while sendEnquiry', array('login_user_id' => Auth::id()));
                            $outputArray['status'] = 0;
                            $outputArray['message'] = trans('apimessages.default_error_msg');
                            $statusCode = 400;
                        }
                    }
                    else
                    {
                        $this->log->info('API sendEnquiry no records found', array('login_user_id' => Auth::id()));
                        $outputArray['status'] = 1;
                        $outputArray['message'] = trans('apimessages.no_business_recordsfound');
                        $statusCode = 200;
                        $outputArray['data'] = array();
                    }
                }
        } 
        catch (Exception $e) 
        {
                DB::rollback();
                $this->log->error('API something went wrong while sendEnquiry', array('login_user_id' => Auth::id()));
                $outputArray['status'] = 0;
                $outputArray['message'] = $e->getMessage();
                $statusCode = $e->getStatusCode();
                return response()->json($outputArray, $statusCode);
        }
           return response()->json($outputArray, $statusCode);
    }

   /**
     * Delete Thread
     */
    public function deleteThread(Request $request)
    {
        $responseData = ['status' => 1, 'message' => trans('apimessages.default_error_msg')];
        $statusCode = 400;
        $requestData = Input::all();
        try 
        {
            $validator = Validator::make($request->all(), [
                'thread_id' => 'required'
            ]);
            if ($validator->fails()) 
            {
                $this->log->error('API validation failed while deleteThread', array('login_user_id' => Auth::id()));
                $responseData['status'] = 0;
                $responseData['message'] = $validator->messages()->all()[0];
                $statusCode = 200;               
            }
            else
            {
                $threadDetail = Chats::find($requestData['thread_id']);
                if(count($threadDetail) > 0)
                {
                    $data = [];
                    $deleteFlag = false;
                    $data['id'] = $requestData['thread_id'];
                    if($threadDetail->customer_id == Auth::id())
                    {
                        $data['customer_read_flag'] = 0;
                        if($threadDetail->member_read_flag == 0)
                        {
                            $deleteFlag = true;
                        }
                    }
                    elseif ($threadDetail->member_id == Auth::id()) 
                    {
                        $data['member_read_flag'] = 0; 
                        if($threadDetail->customer_read_flag == 0)
                        {
                            $deleteFlag = true;
                        }  
                    }
                    if($deleteFlag == true)
                    {
                        $threadDelete = $threadDetail->delete();
                    }
                    else
                    {
                        $threadDetail = $this->objChats->insertUpdate($data);
                    }
                    
                    $this->log->info('API Thread deleted successfully', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 1;
                    $responseData['message'] =  trans('apimessages.thread_deleted_success');
                    $statusCode = 200;
                }
                else
                {
                    $this->log->error('API something went wrong while deleteThread', array('login_user_id' => Auth::id()));
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('apimessages.invalid_thread_id');
                    $statusCode = 200;
                }
            }            
        } catch (Exception $e) {
            $this->log->error('API something went wrong while deleteThread', array('login_user_id' => Auth::id(), 'error' => $e->getMessage()));
            $responseData = ['status' => 0, 'message' => $e->getMessage()];
            return response()->json($responseData, $statusCode);
        }
        return response()->json($responseData, $statusCode);
    }

}
