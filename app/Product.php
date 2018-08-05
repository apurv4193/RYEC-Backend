<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Product extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $table = 'product';
    protected $fillable = ['business_id', 'logo','name', 'description', 'category_id','category_hierarchy', 'metatags', 'cost'];
    protected $dates = ['deleted_at'];
    protected $cascadeDeletes = ['productImages'];

    /**
     * Insert and Update Product
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return Product::where('id', $data['id'])->update($updateData);
        } else {
            return Product::create($data);
        }
    }

    /**
     * get all Product for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $product = Product::orderBy('id', 'DESC');
        if(isset($paginate) && $paginate == true) {
            return $product->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $product->get();
        }
    }

    /**
    * get all Product By businessId for admin
    */
    public function getAllByBusinessId($businessId)
    {
        $product = Product::orderBy('id', 'DESC');
        return $product->where('business_id',$businessId)->get();
    }
   
    public function Product_category()
    {
        return $this->belongsTo('App\Category','category_id');
    }
    
    public function productBusiness()
    {
        return $this->belongsTo('App\Business','business_id');
    }

    public function productImages()
    {
        return $this->hasMany('App\ProductImage');
    }
    
    public function productImage()
    {
        return $this->hasOne('App\ProductImage');
    }

   
}
