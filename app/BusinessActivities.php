<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class BusinessActivities extends Model
{
    use SoftDeletes;

    protected $table = 'business_activities';
    protected $fillable = ['business_id', 'activity_title','activity_date'];
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
            return BusinessActivities::where('id', $data['id'])->update($updateData);
        } else {
             return BusinessActivities::create($data);
        }
    }

    /**
     * get all Business Activities for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $businessActivities = BusinessActivities::orderBy('id', 'DESC');
        
        if(isset($paginate) && $paginate == true) {
            return $businessActivities->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $businessActivities->get();
        }
    }
   
}
