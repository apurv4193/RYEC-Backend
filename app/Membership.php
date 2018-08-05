<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Membership extends Model
{
    use  SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_membership_plans';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['subscription_plan_id', 'business_id','start_date' , 'end_date','actual_payment','agent_commision','net_payment','comments'];
    
    //protected $cascadeDeletes = ['states'];
    
    
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return Membership::where('id', $data['id'])->update($updateData);
        } else {
             return Membership::create($data);
        }
    }

    /**
     * get all business membership for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $membership = Membership::orderBy('id', 'DESC');
        
        if(isset($paginate) && $paginate == true) {
            return $membership->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $membership->get();
        }
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo('App\SubscriptionPlan','subscription_plan_id');
    }

    
}
