<?php

namespace App\Helpers;

use Auth;
use App\Category;
use App\Business;
use App\BusinessRatings;
use App\EmailTemplates;
use App\AgentRequest;
use App\User;
use App\City;
use App\State;
use App\Country;
use App\Timezone;
use App\UsersDevice;
use Mail;
use Config;
use DB;
use Redirect;
use Crypt;
use Response;
use Carbon\Carbon;
use Session;
use Storage;
use GuzzleHttp\Client;
use Artisan;
use DateTime;

Class Helpers {

    public static function status() {
        $status = array('1' => 'Active', '2' => 'Inactive');
        return $status;
    }

    public static function getCategoryById($id) {
        $objCategory = new Category();
        $categories = $objCategory->find($id);
        return $categories->toArray();
    }

    public static function userIsVendorOrNot($uId) {
        $objBusiness = new Business();
        $userVendorData = $objBusiness->userVendorData($uId)->count();
        $response = ($userVendorData > 0) ? 1 : 0;
        return $response;
    }

    public static function getOffset($page, $perPageRecord = 0) {
        if(isset($perPageRecord) && $perPageRecord > 0) {
            return ($page - 1) * $perPageRecord;
        } else {
            return ($page - 1) * Config::get('constant.API_RECORD_PER_PAGE');
        }
    }

    /*
     * Get Web Offset
     */

    public static function getWebOffset($page) {
        return ($page - 1) * Config::get('constant.WEBSITE_RECORD_PER_PAGE');
    }

    /*
     * Get Mobile Chat Offset
     */

    public static function getMobileChatOffset($page) {
        return ($page - 1) * Config::get('constant.MOBILE_CHAT_RECORD_PER_PAGE');
    }

    /*
     * Get Web Chat Offset
     */

    public static function getWebChatOffset($page) {
        return ($page - 1) * Config::get('constant.WEB_CHAT_RECORD_PER_PAGE');
    }

    /*
     * Get Slug from String
     */

    public static function getSlug($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /*
     * Get same user and business rating Data
     */

    public static function getSameUserBusinessData($userId, $businessId) {
        return $getData = BusinessRatings::where('user_id', $userId)->where('business_id', $businessId)->first();
    }

    /*
     * send mail
     */

    public static function sendMailByTemplate($replaceArray, $et_templatepseudoname, $emailParametersArray, $toName) {

        $objEmailTemplates = new EmailTemplates();
        $emailTemplateContent = $objEmailTemplates->getAll(['pseudoname' => $et_templatepseudoname]);
        if (!empty($emailTemplateContent)) {
            $content = $objEmailTemplates->getEmailContent($emailTemplateContent[0]->body, $replaceArray);
            $data = array();
            $data['subject'] = $emailTemplateContent[0]->subject;
            $data['toEmail'] = $emailParametersArray['toEmail'];

            $data['toName'] = $toName;
            $data['content'] = $content;

            Mail::send(['html' => 'emails.EmailTemplate'], $data, function($message) use ($data) {
                $message->subject($data['subject']);

                $message->to($data['toEmail'], $data['toName']);
            });
        }
    }

    public static function sendMail($templateViewFile, $templateViewData, $subject, $toName, $toEmail, $body, $attachment = '') {
        $data = [];
        $data['subject'] = $subject;
        $data['toEmail'] = $toEmail;
        $data['toName'] = $toName;
//      $data['attachment'] = $attachment;
        if ($templateViewFile == '') {
            $templateViewFile = 'Template';
            $templatedata['content'] = array(
                'body' => $body,
                'templateData' => $templateViewData
            );
        } else {
            $templatedata = array(
                'body' => $body,
                'templateData' => $templateViewData
            );
        }
        Mail::send(['html' => 'emails.' . $templateViewFile], $templatedata, function($message) use ($data) {
            $message->subject($data['subject']);
            $message->to($data['toEmail'], $data['toName']);
//            if(isset($data['attachment']) && $data['attachment'] != '') {
//                $message->attach($data['attachment']);
//            }
        });
    }

    public static function getProfileExtraFields($data) {
        $data['profile_pic_thumbnail'] = ($data['profile_pic'] != '' && Storage::size(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$data['profile_pic']) > 0) ? Storage::url(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$data['profile_pic']) : url(Config::get('constant.DEFAULT_IMAGE'));
        $data['profile_pic_original'] = ($data['profile_pic'] != '' && Storage::size(Config::get('constant.USER_ORIGINAL_IMAGE_PATH').$data['profile_pic']) > 0) ? Storage::url(Config::get('constant.USER_ORIGINAL_IMAGE_PATH').$data['profile_pic']) : url(Config::get('constant.DEFAULT_IMAGE'));

        return $data;
    }

    public static function getPendingAgent() {
        return AgentRequest::get()->count();
    }

    public static function getCategoryReverseHierarchy($cat)
    {
        $catArray = [];
        $mainArray = [];
//      $parentname = Category::find($cat)->name;
        $parentDetails = Category::find($cat);
        if(count($parentDetails) > 0)
        {
            $catArray['id'] = $cat;
            $catArray['name'] = $parentDetails->name;
            $mainArray[] = $catArray;
            while ($cat > 0) {
                $data = Category::find($cat);
                if (isset($data->parentCatData) && count($data->parentCatData) > 0)
                {
                    $catArray['id'] = $data->parentCatData->id;
                    $catArray['name'] = $data->parentCatData->name;
                    $mainArray[] = $catArray;
                    $cat = $data->parentCatData->id;
                } else {
                    $cat = 0;
                }
            }
        }
        return $mainArray;
    }

    public static function getCategorySubHierarchy($cat)
    {
        $data = Category::find($cat);
        $info = $data->childCategroyData;
        $mainArray = [];
        if($info && count($info) > 0)
        {
            foreach($info as $i)
            {
                $mainArray[] = $i['id'];
            }
            $i = 0;
            while(true)
            {
                $data = Category::find($mainArray[$i]);
                $info = $data->childCategroyData;
                if($info && count($info) > 0)
                {
                    foreach($info as $j)
                    {
                        $mainArray[] = $j['id'];
                    }
                }
                $i++;

                if(count($mainArray) == $i)
                {
                    break;
                }
            }
        }
        return $mainArray;
    }

    public static function getWeekDays() {
        return array('0' => 'mon', '1' => 'tue', '2' => 'wed', '3' => 'thu', '4' => 'fri', '5' => 'sat', '6' => 'sun');
    }

    public static function getTime() {
        return array(
            '0' => '1:00',
            '1' => '1:30',
            '2' => '2:00',
            '3' => '2:30',
            '4' => '3:00',
            '5' => '3:30',
            '6' => '4:00',
            '7' => '4:30',
            '8' => '5:00',
            '9' => '5:30',
            '10' => '6:00',
            '11' => '6:30',
            '12' => '7:00',
            '13' => '7:30',
            '14' => '8:00',
            '15' => '8:30',
            '16' => '9:00',
            '17' => '9:30',
            '18' => '10:00',
            '19' => '10:30',
            '20' => '11:00',
            '21' => '11:30',
            '22' => '12:00',
        );
    }

    public static function getCurrentDataTiming($businessWorkingHoursData) {
        $timings = [];
        $day = strtolower(Carbon::now()->format('D'));

       $currentTimeStamp = Carbon::now($businessWorkingHoursData->timezone)->format('H:i');

        $day_start_time = $day . '_start_time';
        $day_end_time = $day . '_end_time';
        $day_open_close = $day . '_open_close';

        $dayStartTime = (!empty($businessWorkingHoursData->$day_start_time)) ? $businessWorkingHoursData->$day_start_time : '';
        $dayEndTime = (!empty($businessWorkingHoursData->$day_end_time)) ? $businessWorkingHoursData->$day_end_time : '';
        $dayOpenClose = (!empty($businessWorkingHoursData->$day_open_close)) ? $businessWorkingHoursData->$day_open_close : '';


        if (!empty($dayStartTime) && !empty($dayEndTime) && $dayOpenClose == 1)
        {
            $stime = $dayStartTime;
            $etime = $dayEndTime;


            $timezone = Timezone::where('name',$businessWorkingHoursData->timezone)->first();
            $timezoneValue = (isset($timezone) && !empty($timezone)) ?$timezone->value: '';
            $getStartTime = new DateTime();
            $getStartTime->setTimestamp($dayStartTime);
            $getStartTime->setTimezone(new \DateTimeZone($businessWorkingHoursData->timezone));
            $dayStartTime = $getStartTime->format("h:i a");
            $getStartTime = $getStartTime->format("H:i");

            $getEndTime = new DateTime();
            $getEndTime->setTimestamp($dayEndTime);
            $getEndTime->setTimezone(new \DateTimeZone($businessWorkingHoursData->timezone));
            $dayEndTime = $getEndTime->format("h:i a");
            $getEndTime = $getEndTime->format("H:i");

            // // $getStartTime = date('H:i', $dayStartTime);
            // // $getEndTime = date('H:i', $dayEndTime);
            // echo $getStartTime. "-" . $getEndTime;
            // echo $currentTimeStamp; exit;
            $day_start_time = $stime;
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$businessWorkingHoursData->timezone);
            $timingStart = $day_time['time']." ".$day_time['am_pm'];

            $day_end_time = $etime;
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$businessWorkingHoursData->timezone);
            $timingEnd = $day_time['time']." ".$day_time['am_pm'];


            if ($getStartTime < $currentTimeStamp && $currentTimeStamp < $getEndTime) {
                $timings['timings'] = $timingStart . ' - ' . $timingEnd." (".$timezoneValue.")";
                $timings['current_open_status'] = trans('labels.opennow');
            } else {
                $timings['timings'] = $timingStart . ' - ' . $timingEnd." (".$timezoneValue.")";
                $timings['current_open_status'] = trans('labels.closednow');
            }
        }
        else
        {
            $timings['current_open_status'] = trans('labels.closedtoday');
            $timings['timings'] = '';
        }
        return $timings;
    }

    public static function getServerDateTimeIntoGivenTimezone($dateTime, $toTimeZone = 'Asia/Kolkata') {

        $date = new \DateTime('01 Jan 2018 '.$dateTime, new \DateTimeZone($toTimeZone));
        return $date->format('U');

    }

    public static function getReverseServerDateTimeIntoGivenTimezone($dateTime, $toTimeZone = 'Asia/Kolkata') {
        // $date = new \DateTime($dateTime);
        // $date = DateTime::createFromFormat('H:i A', $dateTime);
        $date = new DateTime();
        $date->setTimestamp($dateTime);
        $date->setTimezone(new \DateTimeZone($toTimeZone));
        $data['time'] = $date->format('g:i');
        $data['am_pm'] = $date->format('A');
        return $data;
    }

    public static function setWorkingHours($timeArray) {

        $data = [];

        $data['mon_start_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['mon_start_time'] . ' ' . $timeArray['mon_start_time_am_pm'],$timeArray['timezone']);
        $data['mon_end_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['mon_end_time'] . ' ' . $timeArray['mon_end_time_am_pm'],$timeArray['timezone']);
        $data['mon_open_close'] = $timeArray['mon_open_close'];

        $data['tue_start_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['tue_start_time'] . ' ' . $timeArray['tue_start_time_am_pm'],$timeArray['timezone']);
        $data['tue_end_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['tue_end_time'] . ' ' . $timeArray['tue_end_time_am_pm'],$timeArray['timezone']);
        $data['tue_open_close'] = $timeArray['tue_open_close'];

        $data['wed_start_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['wed_start_time'] . ' ' . $timeArray['wed_start_time_am_pm'],$timeArray['timezone']);
        $data['wed_end_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['wed_end_time'] . ' ' . $timeArray['wed_end_time_am_pm'],$timeArray['timezone']);
        $data['wed_open_close'] = $timeArray['wed_open_close'];

        $data['thu_start_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['thu_start_time'] . ' ' . $timeArray['thu_start_time_am_pm'],$timeArray['timezone']);
        $data['thu_end_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['thu_end_time'] . ' ' . $timeArray['thu_end_time_am_pm'],$timeArray['timezone']);

        $data['thu_open_close'] = $timeArray['thu_open_close'];
        $data['fri_start_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['fri_start_time'] . ' ' . $timeArray['fri_start_time_am_pm'],$timeArray['timezone']);
        $data['fri_end_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['fri_end_time'] . ' ' . $timeArray['fri_end_time_am_pm'],$timeArray['timezone']);
        $data['fri_open_close'] = $timeArray['fri_open_close'];

        $data['sat_start_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['sat_start_time'] . ' ' . $timeArray['sat_start_time_am_pm'],$timeArray['timezone']);
        $data['sat_end_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['sat_end_time'] . ' ' . $timeArray['sat_end_time_am_pm'],$timeArray['timezone']);
        $data['sat_open_close'] = $timeArray['sat_open_close'];

        $data['sun_start_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['sun_start_time'] . ' ' . $timeArray['sun_start_time_am_pm'],$timeArray['timezone']);
        $data['sun_end_time'] = self::getServerDateTimeIntoGivenTimezone($timeArray['sun_end_time'] . ' ' . $timeArray['sun_end_time_am_pm'],$timeArray['timezone']);
        $data['sun_open_close'] = $timeArray['sun_open_close'];

        return $data;
    }

    public static function getBusinessWorkingDayHours($businessHoursArray)
    {
        $data = [];
        $hoursArray = $businessHoursArray->toArray();
        unset($hoursArray['id'], $hoursArray['business_id'], $hoursArray['created_at'], $hoursArray['updated_at'], $hoursArray['deleted_at']);

//      Monday
        $data[0]['name'] = trans('labels.mon');
        if ($hoursArray['mon_open_close'] == 1 && !empty($hoursArray['mon_start_time']) && !empty($hoursArray['mon_end_time']))
        {
            $day_start_time = $hoursArray['mon_start_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$hoursArray['timezone']);
            $data[0]['start_time'] = $day_time['time']." ".$day_time['am_pm'];

            $day_end_time = $hoursArray['mon_end_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$hoursArray['timezone']);
            $data[0]['end_time'] = $day_time['time']." ".$day_time['am_pm'];

            $data[0]['open_close'] = trans('labels.openlbl');
        }
        else
        {
            $data[0]['open_close'] = trans('labels.closedlbl');
        }

//      Tuesday
        $data[1]['name'] = trans('labels.tue');
        if ($hoursArray['tue_open_close'] == 1 && !empty($hoursArray['tue_start_time']) && !empty($hoursArray['tue_end_time']))
        {
            $day_start_time = $hoursArray['tue_start_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$hoursArray['timezone']);
            $data[1]['start_time'] = $day_time['time']." ".$day_time['am_pm'];

            $day_end_time = $hoursArray['tue_end_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$hoursArray['timezone']);
            $data[1]['end_time'] = $day_time['time']." ".$day_time['am_pm'];

            $data[1]['open_close'] = trans('labels.openlbl');

        }
        else
        {
            $data[1]['open_close'] = trans('labels.closedlbl');
        }

//      Wednesday
        $data[2]['name'] = trans('labels.wed');
        if ($hoursArray['wed_open_close'] == 1 && !empty($hoursArray['wed_start_time']) && !empty($hoursArray['wed_end_time']))
        {
            $day_start_time = $hoursArray['wed_start_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$hoursArray['timezone']);
            $data[2]['start_time'] = $day_time['time']." ".$day_time['am_pm'];

            $day_end_time = $hoursArray['wed_end_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$hoursArray['timezone']);
            $data[2]['end_time'] = $day_time['time']." ".$day_time['am_pm'];

            $data[2]['open_close'] = trans('labels.openlbl');

        }
        else
        {
            $data[2]['open_close'] = trans('labels.closedlbl');
        }

//      Thursday
        $data[3]['name'] = trans('labels.thu');
        if ($hoursArray['thu_open_close'] == 1 && !empty($hoursArray['thu_start_time']) && !empty($hoursArray['thu_end_time']))
        {
            $day_start_time = $hoursArray['thu_start_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$hoursArray['timezone']);
            $data[3]['start_time'] = $day_time['time']." ".$day_time['am_pm'];

            $day_end_time = $hoursArray['thu_end_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$hoursArray['timezone']);
            $data[3]['end_time'] = $day_time['time']." ".$day_time['am_pm'];

            $data[3]['open_close'] = trans('labels.openlbl');
        }
        else
        {
            $data[3]['open_close'] = trans('labels.closedlbl');
        }

//      Friday
        $data[4]['name'] = trans('labels.fri');
        if ($hoursArray['fri_open_close'] == 1 && !empty($hoursArray['fri_start_time']) && !empty($hoursArray['fri_end_time']))
        {
            $day_start_time = $hoursArray['fri_start_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$hoursArray['timezone']);
            $data[4]['start_time'] = $day_time['time']." ".$day_time['am_pm'];

            $day_end_time = $hoursArray['fri_end_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$hoursArray['timezone']);
            $data[4]['end_time'] = $day_time['time']." ".$day_time['am_pm'];

            $data[4]['open_close'] = trans('labels.openlbl');
        }
        else
        {
            $data[4]['open_close'] = trans('labels.closedlbl');
        }

//      Saturday
        $data[5]['name'] = trans('labels.sat');
        if ($hoursArray['sat_open_close'] == 1 && !empty($hoursArray['sat_start_time']) && !empty($hoursArray['sat_end_time']))
        {
            $day_start_time = $hoursArray['sat_start_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$hoursArray['timezone']);
            $data[5]['start_time'] = $day_time['time']." ".$day_time['am_pm'];

            $day_end_time = $hoursArray['sat_end_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$hoursArray['timezone']);
            $data[5]['end_time'] = $day_time['time']." ".$day_time['am_pm'];

            $data[5]['open_close'] = trans('labels.openlbl');
        }
        else
        {
            $data[5]['open_close'] = trans('labels.closedlbl');
        }

//      Sunday
        $data[6]['name'] = trans('labels.sun');
        if ($hoursArray['sun_open_close'] == 1 && !empty($hoursArray['sun_start_time']) && !empty($hoursArray['sun_end_time']))
        {
            $day_start_time = $hoursArray['sun_start_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$hoursArray['timezone']);
            $data[6]['start_time'] = $day_time['time']." ".$day_time['am_pm'];

            $day_end_time = $hoursArray['sun_end_time'];
            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$hoursArray['timezone']);
            $data[6]['end_time'] = $day_time['time']." ".$day_time['am_pm'];

            $data[6]['open_close'] = trans('labels.openlbl');
        }
        else
        {
            $data[6]['open_close'] = trans('labels.closedlbl');
        }

        return $data;

    }

    public static function addFileToStorage($fileName, $folderName = "", $file, $storageName = "")
	{
		$url = "";
		if($file && $fileName != "")
		{
			$folderName = ($folderName != "") ? $folderName : "/";

			if($storageName != "" && strtolower($storageName) == "s3")
			{
				if(Storage::disk('s3')->put($folderName.$fileName, file_get_contents($file), 'public'))
				{
					//$url = Storage::disk('s3')->url($folderName.$fileName);
					$url = $fileName;
				}
			}
			else
			{
				if(Storage::put($folderName.$fileName, file_get_contents($file), 'public'))
				{
					//$url = url(Storage::url($folderName.$fileName));
					$url = $fileName;
				}
			}
		}
		return $url;
	}

	public static function deleteFileToStorage($fileName, $folderName = "", $storageName = "")
	{
		$return = false;
		if($fileName != "")
		{
			$folderName = ($folderName != "") ? $folderName : "/";

			if($storageName != "" && strtolower($storageName) == "s3")
			{
				if(Storage::disk('s3')->exists($folderName.$fileName))
				{
					if(Storage::disk('s3')->delete($folderName.$fileName))
					{
						$return = true;
					}
				}
			}
			else
			{
				if(Storage::exists($folderName.$fileName))
				{
					if(Storage::delete($folderName.$fileName))
					{
						$return = true;
					}
				}
			}
		}
		return $return;
	}

    public static function getAddressAttributes($latitude, $longitude)
    {
        $geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng=' . $latitude . "," . $longitude . '&sensor=false&libraries=places');
        $sample_array = ["premise", "street_number", "route", "neighborhood", "sublocality_level_3", "sublocality_level_2", "sublocality_level_1", "locality", "administrative_area_level_2", "administrative_area_level_1", "country", "postal_code"];
        $output = json_decode($geocode);
        $premise = $sublocality_level_1 = $locality = $administrative_area_level_1 = $route = $street_number = $sublocality_level_2 = $sublocality_level_3 = $administrative_area_level_2 = $country = $postal_code = $neighborhood = $address = '';
        $business_address_attributes = [];
        if (!empty($output->results)) {
            for ($j = 0; $j < count($output->results[0]->address_components); $j++) {
                for ($i = 0; $i < count($sample_array); $i++) {
                    if ($sample_array[$i] == $output->results[0]->address_components[$j]->types[0]) {
                        $set = $sample_array[$i];
                        //Getting value from associative variable premise, country etc all attribute using $$set
                        $$set = $output->results[0]->address_components[$j]->long_name;
                        $business_address_attributes[$set] = $output->results[0]->address_components[$j]->long_name;
                    }
                }

            }
        }

        return $business_address_attributes;
    }

    public static function genrateOTP()
    {
        return rand(100000, 999999);
    }

    public static function getCategoryHierarchy($catregoryCommaSeparated)
    {
        if($catregoryCommaSeparated != '') {
            $explodeCategories = explode(',',$catregoryCommaSeparated);
            $categoryHierarchyArray = [];
            foreach($explodeCategories as $category)
            {
                $listArray = [];
                $hierarchydata = array_reverse(Helpers::getCategoryReverseHierarchy($category));
                foreach($hierarchydata as $key=>$value)
                {
                    $listArray[] = $value['id'];
                }
                $categoryHierarchyArray[] = implode(',',$listArray);

            }

            return $category_hierarchy = implode('|',$categoryHierarchyArray);
        } else {
            return '';
        }
    }

    /**
     * Get Unread Threads Count
     */
    public static function getUnreadThreadsCount($userId)
    {
        $threads = Chats::where(function($query) use ($userId){
                        $query->where('customer_id', $userId)
                            ->orWhere('member_id', $userId);
                    });
        $threads->whereHas('getChatMessages',function ($query) use ($userId) {
                    $query->whereRaw("NOT FIND_IN_SET(".$userId.", read_by)");
                });

        return $threads->count();
    }

    public static function sendMessage($mobilenumber, $message)
    {
        $client = new Client();

        //$res = $client->get('http://sms.thebulksms.in/api/mt/SendSMS?APIKey='.Config::get('constant.SMS_API_KEY').'&senderid=PRTHNA&channel=Trans&DCS=0&flashsms=0&number='.$mobilenumber.'&text='.$message);
        // $res = $client->get('http://sms.thebulksms.in/api/mt/SendSMS?user=rtuvaa&Password=rtuvaa&senderid=RYUVAA&channel=Trans&DCS=0&flashsms=0&number='.$mobilenumber.'&text='.$message);

        /* start new SMS API */
        // $res = $client->get('http://sms.hspsms.com/sendSMS?username=ryuvaa&message='.$message.'&sendername=RYUVAA&smstype=TRANS&numbers='.$mobilenumber.'&apikey=10306e72-102c-435e-8083-9f278a0c4f0a');
        // $respose = json_decode($res->getBody());
        // if(!empty($respose) && (isset($respose[1]->msgid) && $respose[1]->msgid == '')) {
        //    return ['status' => 0, 'message' => 'Fail'];
        // } else {
        //     return ['status' => 1, 'message' => 'Success'];
        // }
        /* stop new SMS API */

        try
        {
            /* old SMS API */
            $res = $client->get('http://sms.thebulksms.in/api/mt/SendSMS?user=rtuvaa&Password=rtuvaa&senderid=RYUVAA&channel=Trans&DCS=0&flashsms=0&number='.$mobilenumber.'&text='.$message);
            $respose = json_decode($res->getBody());
            $statusCode = $res->getStatusCode();
            if($statusCode == 200)
            {
                if(!empty($respose))
                {
                    if($respose->ErrorCode == '000') {
                        \Log::info('OTP successfully send');
                        return ['status' => 1, 'message' => $respose->ErrorMessage];
                    } else {
                        \Log::error('Error occurred while otp send');
                        return ['status' => 0, 'message' => $respose->ErrorMessage];
                    }
                } else {
                    \Log::error('Something is really going wrong.');
                    return ['status' => 0, 'message' => trans('apimessages.default_error_msg')];
                }
            }
            else
            {
                $res = $client->get('http://sms.hspsms.com/sendSMS?username=ryuvaa&message='.$message.'&sendername=RYUVAA&smstype=TRANS&numbers='.$mobilenumber.'&apikey=10306e72-102c-435e-8083-9f278a0c4f0a');
                $respose = json_decode($res->getBody());
                if(!empty($respose) && (isset($respose[1]->msgid) && $respose[1]->msgid == '')) {
                    \Log::error('Error occurred while otp send through HSP_SMS');
                    return ['status' => 0, 'message' => 'Fail'];
                } else {
                    \Log::info('OTP successfully send');
                    return ['status' => 1, 'message' => 'Success'];
                }
            }
        }
        catch (\Exception $e)
        {
            try
            {
                $res = $client->get('http://sms.hspsms.com/sendSMS?username=ryuvaa&message='.$message.'&sendername=RYUVAA&smstype=TRANS&numbers='.$mobilenumber.'&apikey=10306e72-102c-435e-8083-9f278a0c4f0a');
                $respose = json_decode($res->getBody());
                if(!empty($respose) && (isset($respose[1]->msgid) && $respose[1]->msgid == '')) {
                    \Log::error('Error occurred while otp send through HSP_SMS');
                   return ['status' => 0, 'message' => 'Fail'];
                } else {
                    \Log::info('OTP successfully send');
                    return ['status' => 1, 'message' => 'Success'];
                }
            }
            catch (\Exception $e)
            {
                $email = Config::get('constant.RYUVA_CLUB_EMAIL_ID');
                // Send Password reset mail
                $data = [
                    'mobilenumber' => $mobilenumber,
                    'message' => $message
                ];
                Mail::send('emails.SmsTemplate', ['data' => $data], function($message) use($email) {
                    $message->to($email)->subject('SMS delivery failed');
                });
            }
        }



       /* new SMS API */
    }
    public static function getTimezone()
    {
        $timezone = Timezone::get();
        return $timezone;
    }

    // public static function getTimezone()
    // {
    //     $timezone = array('1' => 'Asia/Aden',
    //                     '2' => 'Asia/Aqtau',
    //                     '3' => 'Asia/Baghdad',
    //                     '4'=> 'Asia/Barnaul',
    //                     '5'=> 'Asia/Chita',
    //                     '6'=> 'Asia/Dhaka',
    //                     '7'=>'Asia/Famagusta',
    //                     '8'=>'Asia/Hong_Kong',
    //                     '9'=>'Asia/Jayapura',
    //                     '10'=>'Asia/Karachi',
    //                     '11'=>'Asia/Krasnoyarsk',
    //                     '12'=>'Asia/Macau',
    //                     '13'=>'Asia/Muscat',
    //                     '14'=>'Asia/Omsk',
    //                     '15'=>'Asia/Pyongyang',
    //                     '16'=>'Asia/Sakhalin',
    //                     '17'=>'Asia/Singapore',
    //                     '18'=>'Asia/Tbilisi',
    //                     '19'=>'Asia/Tomsk',
    //                     '20'=>'Asia/Vientiane',
    //                     '21' => 'Asia/Almaty',
    //                     '22' => 'Asia/Aqtobe',
    //                     '23' => 'Asia/Bahrain',
    //                     '24' => 'Asia/Beirut',
    //                     '25' => 'Asia/Choibalsan',
    //                     '26' => 'Asia/Dili',
    //                     '27' => 'Asia/Gaza',
    //                     '28' => 'Asia/Hovd',
    //                     '29' => 'Asia/Jerusalem',
    //                     '30' => 'Asia/Kathmandu',
    //                     '31' => 'Asia/Kuala_Lumpur',
    //                     '32' => 'Asia/Magadan',
    //                     '33' => 'Asia/Nicosia',
    //                     '34' => 'Asia/Oral',
    //                     '35' => 'Asia/Qatar',
    //                     '36' => 'Asia/Samarkand',
    //                     '37' => 'Asia/Srednekolymsk',
    //                     '38' => 'Asia/Tehran',
    //                     '39' => 'Asia/Ulaanbaatar',
    //                     '40' => 'Asia/Vladivostok',
    //                     '41' => 'Asia/Yerevan',
    //                     '42' => 'Asia/Amman',
    //                     '43' => 'Asia/Ashgabat',
    //                     '44' => 'Asia/Baku',
    //                     '45' => 'Asia/Bishkek',
    //                     '46' => 'Asia/Colombo',
    //                     '47' => 'Asia/Dubai',
    //                     '48' => 'Asia/Hebron',
    //                     '49' => 'Asia/Irkutsk',
    //                     '50' => 'Asia/Kabul',
    //                     '51' => 'Asia/Khandyga',
    //                     '52' => 'Asia/Kuching',
    //                     '53' => 'Asia/Makassar',
    //                     '54' => 'Asia/Novokuznetsk',
    //                     '55' => 'Asia/Phnom_Penh',
    //                     '56' => 'Asia/Qyzylorda',
    //                     '57' => 'Asia/Seoul',
    //                     '58' => 'Asia/Taipei',
    //                     '59' => 'Asia/Thimphu',
    //                     '60' => 'Asia/Urumqi',
    //                     '61' => 'Asia/Yakutsk',
    //                     '62' => 'Asia/Anadyr',
    //                     '63' => 'Asia/Atyrau',
    //                     '64' => 'Asia/Bangkok',
    //                     '65' => 'Asia/Brunei',
    //                     '66' => 'Asia/Damascus',
    //                     '67' => 'Asia/Dushanbe',
    //                     '68' => 'Asia/Ho_Chi_Minh',
    //                     '69' => 'Asia/Jakarta',
    //                     '70' => 'Asia/Kamchatka',
    //                     '71' => 'Asia/Kolkata',
    //                     '72' => 'Asia/Kuwait',
    //                     '73' => 'Asia/Manila',
    //                     '74' => 'Asia/Novosibirsk',
    //                     '75' => 'Asia/Pontianak',
    //                     '76' => 'Asia/Riyadh',
    //                     '77' => 'Asia/Shanghai',
    //                     '78' => 'Asia/Tashkent',
    //                     '79' => 'Asia/Tokyo',
    //                     '80' => 'Asia/Ust-Nera',
    //                     '81' => 'Asia/Yangon'

    //                 );
    //     return $timezone;
    // }

    public static function getCities()
    {
        $objCities = new City();
        $data = $objCities->getall();
        return $data;
    }

    public static function getStates()
    {
        $objStates = new State();
        $data = $objStates->getall();
        return $data;
    }

    public static function getCountries()
    {
        $objCountries = new Country();
        $data = $objCountries->getall();
        return $data;
    }

    public static function sendPushNotification($userId, $data)
    {
        $user = User::find($userId);
        if($user && $user->notification)
        {
            $devices = UsersDevice::where('user_id', $userId)->get();
            if($devices) {
                foreach ($devices as $device) {
                    if($device->device_token != '') {
                        if($device->device_type == 1) {
                            Helpers::pushNotificationForAndroid($device->device_token,$data);
                        }

                        if($device->device_type == 2) {
                            Helpers::pushNotificationForiPhone($device->device_token,$data);
                        }
                    }
                }
            }
        }
        return true;
    }

    public static function pushNotificationForiPhone($token, $message)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        $msg = [
            'body' => $message['message'],
            'title' => '',
            'data' => $message,
            'icon' => 'myicon',
            'sound' => 'mySound'
        ];
        $fields = [
            'to' => $token, // expecting a single ID
            'notification' => $msg
        ];
        $headers = [
            'Authorization: key ='.Config::get('constant.FCM_KEY'),
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        curl_close($ch);
        return $result;
    }

    public static function pushNotificationForAndroid($token,$data)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        $arrayToSend = array('to'  => $token, 'data' => $data);

        $fields = array(
                 'to' => $token,
                 'body' => 'hey'
                );

        $headers = array(
            'Authorization: key='.Config::get('constant.FCM_KEY'),
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayToSend));
        $result = curl_exec($ch);
        if ($result === FALSE) {
           die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    public static function topicPushNotification($token, $message)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        $msg = [
            'body' => $message['message'],
            'title' => $message['title'],
            'data' => $message,
            'icon' => 'myicon',
            'sound' => 'mySound'
        ];
        $fields = [
            'to' => $token, // expecting a single ID
            'notification' => $msg,
            'data' => $message
        ];
        $fields['android']['notification']['click_action'] = 'OPEN_MAIN_ACTIVITY';
        $headers = [
            'Authorization: key ='.Config::get('constant.FCM_KEY'),
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        curl_close($ch);
        return $result;
    }

    public static function getParentCategoryIds($catregoryCommaSeparated)
    {

        $mainArray = [];
//      $parentname = Category::find($cat)->name;

        if($catregoryCommaSeparated != '') {
            $explodeCategories = explode(',',$catregoryCommaSeparated);
            $categoryHierarchyArray = [];
            foreach($explodeCategories as $cat)
            {
                $catArray = $cat;
                $parentDetails = Category::find($cat);
                if(count($parentDetails) > 0)
                {
                    while ($cat > 0) {
                        $data = Category::find($cat);
                        if (isset($data->parentCatData) && count($data->parentCatData) > 0)
                        {
                            $catArray = $data->parentCatData->id;
                            $cat = $data->parentCatData->id;
                        } else {
                            $cat = 0;
                        }
                    }
                    $mainArray[] = $catArray;
                }
            }
        }

        if(!empty($mainArray))
        {
            return implode(',',array_unique($mainArray));
        }
        else
        {
            return '';
        }
    }

    public static function categoryHasBusinesses($categoryId)
    {
        $getData = Business::where(function($query) use ($categoryId){
                        $query->whereRaw("FIND_IN_SET(".$categoryId.", category_id)")
                            ->orWhere('parent_category', $categoryId);
                    });
        return $getData->get();
    }
}

?>
