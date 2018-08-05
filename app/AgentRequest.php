<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class AgentRequest extends Model
{
    use SoftDeletes;

    protected $table = 'agent_request';
    protected $fillable = ['user_id', 'comment'];
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
            return AgentRequest::where('id', $data['id'])->update($updateData);
        } else {
            return AgentRequest::create($data);
        }
    }

    /**
     * get all agent request
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $agentRequest = AgentRequest::orderBy('id', 'DESC');
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
