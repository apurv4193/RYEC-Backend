<?php

namespace App;


use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class State extends Model
{
   
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'state';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'latitude','longitude','country_id'];
    
    public function cities()
    {
        return $this->hasMany('App\City', 'state_id');
    }

    public function country()
    {
        return $this->belongsTo('App\Country', 'country_id');
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
            return State::where('id', $data['id'])->update($updateData);
        } else {
             return State::create($data);
        }
    }

    /**
     * get all State for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $state = State::orderBy('name', 'ASC');
        
        if(isset($paginate) && $paginate == true) {
            return $state->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $state->get();
        }
    }

    
}
