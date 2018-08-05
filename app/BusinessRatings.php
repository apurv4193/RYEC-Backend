<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use DB;
use Config;
use Crypt;
use Cache;

class BusinessRatings extends Model
{
    use SoftDeletes;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_ratings';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'business_id', 'rating', 'comment', 'created_at', 'updated_at'];
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $primaryKey = 'id';
    
    public function getUsersData()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
    
    public function getBusinessData()
    {
        return $this->belongsTo('App\Business', 'business_id');
    }
    
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
            return BusinessRatings::where('id', $data['id'])->update($updateData);
        }
        else
        {
            return BusinessRatings::create($data);
        }
    }

    
    public function getAll($filters = array(), $paginate = false)
    {
        $getData = BusinessRatings::whereNull('deleted_at'); //orderBy('id', 'DESC');

        if(isset($filters) && !empty($filters)) 
        {
            if(isset($filters['business_id']) && !empty($filters['business_id']))
            {
                $getData->where('business_id', $filters['business_id']);
            }
            if(isset($filters['offset']) && isset($filters['take']) && !empty($filters['take'])) 
            {
                $getData->skip($filters['offset'])->take($filters['take']);
            }
            if(isset($filters['updated_at']) && !empty($filters['updated_at']) && $filters['updated_at'] == 'updated_at')
            {
                $getData->orderBy('updated_at', 'DESC');
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
