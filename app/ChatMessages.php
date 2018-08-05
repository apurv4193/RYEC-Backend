<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use DB;
use Config;
use Crypt;
use Cache;

class ChatMessages extends Model 
{
    use SoftDeletes;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'chat_messages';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['chat_id', 'message', 'posted_by', 'read_by', 'created_at', 'updated_at'];
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $primaryKey = 'id';
    
    
    
    public function getChats()
    {
        return $this->belongsTo('App\Chats', 'chat_id');
    }
    
    public function getUser() 
    {
        return $this->belongsTo('App\User', 'posted_by');
    }
    
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) 
        {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return ChatMessages::where('id', $data['id'])->update($updateData);
        } else {
             return ChatMessages::create($data);
        }
    }

    
    public function getAll($filters = array(), $paginate = false)
    {
        $getData = ChatMessages::whereNull('deleted_at');

        if(isset($filters) && !empty($filters)) 
        {
            if(isset($filters['chat_id']) && !empty($filters['chat_id']))
            {
                $getData->where('chat_id', $filters['chat_id']);
            }
            if(isset($filters['read_by_id']) && !empty($filters['read_by_id']))
            {
                $getData->whereRaw("NOT FIND_IN_SET(".$filters['read_by_id'].", read_by)");
            }
            if(isset($filters['skip']) && isset($filters['take']) && !empty($filters['take']))
            {
                $getData->skip($filters['skip'])->take($filters['take']);
            }
            if(isset($filters['updated_at']))
            {
                $getData->orderBy('updated_at', 'DESC');
            }
            else
            {
                $getData->orderBy('updated_at', 'DESC');
            }
        }
        else
        {
            $getData->orderBy('id', 'DESC');
        }
        if(isset($paginate) && $paginate == true) 
        {
            return $response = $getData->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } 
        else 
        {
            return $response = $getData->get();
        }
    }

   
    
}
