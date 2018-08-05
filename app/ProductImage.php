<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class ProductImage extends Model
{
    use SoftDeletes;

    protected $table = 'product_images';
    protected $fillable = ['product_id', 'image_name'];
    protected $dates = ['deleted_at'];

	/**
	* Get Product Images by productId
	*/
    public function getProductImagesByProductId($productId)
    {
       	return ProductImage::where('product_id', $productId)->get();
    }
   
}
