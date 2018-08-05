<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    
    protected $fillable = [
    	'notification_type',
    	'user_id',
    	'chennel_type',
    	'message',
    	'search_by',
    	'search_text',
    	'city',
    	'status',
    ];
}
