<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Country extends Model
{
    //use  CascadeSoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'country';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'country_code','flag' , 'latitude','longitude'];
    
    //protected $cascadeDeletes = ['states'];
    
    public function states()
    {
        return $this->hasMany('App\State', 'country_id');
    }

    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return Country::where('id', $data['id'])->update($updateData);
        } else {
             return Country::create($data);
        }
    }

    /**
     * get all Country for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $country = Country::orderBy('name', 'ASC');
        
        if(isset($paginate) && $paginate == true) {
            return $country->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $country->get();
        }
    }

    
}
