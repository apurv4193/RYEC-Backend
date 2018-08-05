<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class OwnerSocialActivity extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'owner_social_activities';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['owner_id', 'activity_title'];
    
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return OwnerSocialActivity::where('id', $data['id'])->update($updateData);
        } else {
             return OwnerSocialActivity::create($data);
        }
    }

    /**
     * get all Owner Social Activities for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $ownerSocialActivity = OwnerSocialActivity::orderBy('id', 'DESC');
        
        if(isset($paginate) && $paginate == true) {
            return $ownerSocialActivity->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $ownerSocialActivity->get();
        }
    }
   
}
