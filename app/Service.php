<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Service extends Model
{
    use SoftDeletes;

    protected $table = 'service';
    protected $fillable = ['business_id', 'name', 'logo','description', 'category_id','category_hierarchy', 'metatags', 'cost'];
    protected $dates = ['deleted_at'];
    protected $cascadeDeletes = ['serviceImages'];

    /**
     * Insert and Update Service
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
            return Service::where('id', $data['id'])->update($updateData);
        } else {
            return Service::create($data);
        }
    }

    /**
     * get all Service for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $service = Service::orderBy('id', 'DESC');
        if(isset($paginate) && $paginate == true) {
            return $service->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $service->get();
        }
    }

    public function service_category()
    {
        return $this->belongsTo('App\Category','category_id');
    }
    
    public function serviceBusiness()
    {
        return $this->belongsTo('App\Business','business_id');
    }

    public function serviceImages()
    {
        return $this->hasMany('App\ServiceImage');
    }

   
}
