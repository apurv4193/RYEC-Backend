<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class BusinessImage extends Model
{
    use SoftDeletes;

    protected $table = 'business_images';
    protected $fillable = ['business_id', 'image_name'];
    protected $dates = ['deleted_at'];

	/**
	* Get Business Images by businessId
	*/
    public function getBusinessImagesByBusinessId($businessId)
    {
       	return BusinessImage::where('business_id', $businessId)->get();
    }
   
}
