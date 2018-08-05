<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class AgentUser extends Model
{
    use SoftDeletes;

    protected $table = 'agent_users';
    protected $fillable = ['user_id', 'city', 'bank_detail', 'status'];
    protected $dates = ['deleted_at'];

    /**
     * Insert and Update Agent User
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
            return AgentUser::where('id', $data['id'])->update($updateData);
        } else {
            return AgentUser::create($data);
        }
    }

    /**
     * get all agent user
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $agentUser = AgentUser::orderBy('id', 'DESC');
        if(isset($paginate) && $paginate == true) {
            return $agentUser->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $agentUser->get();
        }
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }
   
    
}
