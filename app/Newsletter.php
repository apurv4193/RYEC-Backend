<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use DB;
use Config;
use Crypt;

class Newsletter extends Model
{
    use SoftDeletes;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'newsletters';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'body', 'publish_status', 'notify_subscribers', 'author'];
    
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    protected $primaryKey = 'id';

    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) 
        {
            $updateData = [];
            foreach ($this->fillable as $field) 
            {
                if (array_key_exists($field, $data)) 
                {
                    $updateData[$field] = $data[$field];
                }
            }
            return Newsletter::where('id', $data['id'])->update($updateData);
        } else {
            return Newsletter::create($data);
        }
    }
    
     /**
     * get All Newsletter data
     */    
    public function getAll($filters = array(), $paginate = false)
    {
        $getData = Newsletter::orderBy('id', 'DESC');

        if(isset($filters) && !empty($filters)) 
        {   
            if(isset($filters['notify_subscribers'])) 
            {
                $getData->where('notify_subscribers', $filters['notify_subscribers']);
            }
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
