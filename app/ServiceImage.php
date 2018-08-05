<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class ServiceImage extends Model
{
    use SoftDeletes;

    protected $table = 'service_images';
    protected $fillable = ['service_id', 'image_name'];
    protected $dates = ['deleted_at'];

	/**
	* Get Business Images by businessId
	*/
    public function getServiceImagesByServiceId($serviceId)
    {
       	return ServiceImage::where('service_id', $serviceId)->get();
    }
   
}
