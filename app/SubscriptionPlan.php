<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $table = 'subscription_plans';
    protected $fillable = ['name', 'logo', 'description', 'months', 'price'];
    protected $dates = ['deleted_at'];

    /**
     * Insert and Update Subscription Plan
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
            return SubscriptionPlan::where('id', $data['id'])->update($updateData);
        } else {
            return SubscriptionPlan::create($data);
        }
    }

    /**
     * get all Subscription Plan for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $subscriptionPlan = SubscriptionPlan::orderBy('id', 'DESC');
        if(isset($paginate) && $paginate == true) {
            return $subscriptionPlan->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $subscriptionPlan->get();
        }
    }

   

   
}
