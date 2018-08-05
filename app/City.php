<?php

namespace App;


use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class City extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'city';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'latitude','longitude','state_id'];
    
    
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return City::where('id', $data['id'])->update($updateData);
        } else {
             return City::create($data);
        }
    }

    /**
     * get all City for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $city = City::orderBy('name', 'ASC');
        
        if(isset($postData['city']) && $postData['city'] != '')
        {
            $city->where('name', $postData['city']);
        }
        if(isset($paginate) && $paginate == true) {
            return $city->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $city->get();
        }
    }

    public function state()
    {
        return $this->belongsTo('App\State', 'state_id');
    }
    
}
