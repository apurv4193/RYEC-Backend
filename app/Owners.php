<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Owners extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'owners';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['business_id','designation' , 'full_name','country_code','mobile', 'email_id', 'photo','father_name', 'native_village', 'maternal_home', 'kul_gotra','facebook_url','twitter_url','linkedin_url','instagram_url','gender','dob','public_access'];
    
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $cascadeDeletes = ['ownerChildren','ownerSocialActivities'];
    
    public function getBusinessData()
    {
        return $this->belongsTo('App\Business', 'business_id');
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
            return Owners::where('id', $data['id'])->update($updateData);
        } else {
             return Owners::create($data);
        }
    }

    /**
     * get all Business Activities for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $owners = Owners::orderBy('id', 'DESC');
        
        if(isset($paginate) && $paginate == true) {
            return $owners->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $owners->get();
        }
    }

    public function ownerChildren()
    {
        return $this->hasMany('App\OwnerChildren','owner_id');
    } 

    public function ownerSocialActivities()
    {
        return $this->hasMany('App\OwnerSocialActivity','owner_id');
    }
   
}
