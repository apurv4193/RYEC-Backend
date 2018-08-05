<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class UserAgentOTP extends Model
{
    // use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_agent_otp';
     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['agent_id', 'phone', 'otp', 'created_at', 'updated_at'];
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Insert and Update Agent request
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
            return UserAgentOTP::where('id', $data['id'])->update($updateData);
        } else {
            return UserAgentOTP::create($data);
        }
    }

    /**
     * get all agent request
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $agentRequest = UserAgentOTP::orderBy('id', 'DESC');
        if(isset($paginate) && $paginate == true) {
            return $agentRequest->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $agentRequest->get();
        }
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }

}
