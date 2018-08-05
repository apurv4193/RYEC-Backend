<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class NotificationList extends Model
{
    protected $table = 'notification_list';
    protected $fillable = ['id', 'user_id', 'business_id', 'title', 'message', 'type', 'business_name', 'user_name','activity_user_id','thread_id'];

    /**
     * get all Business for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $notificationList = NotificationList::orderBy('id', 'DESC');
        
        if(isset($filters) && !empty($filters)) 
        {
            if(isset($filters['offset']) && $filters['offset'] >= 0 && isset($filters['take']) && $filters['take'] > 0)
            {
                $notificationList->skip($filters['offset'])->take($filters['take']);
            }

            if(isset($filters['user_id']) && $filters['user_id'] != '')
            {
                $notificationList->where('user_id',$filters['user_id']);
            }
            
        }
        
        if(isset($paginate) && $paginate == true) {
            return $notificationList->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $notificationList->get();
        }
    }

    public function user()
    {
        return $this->belongsTo('App\User','activity_user_id');
    }
    
}
