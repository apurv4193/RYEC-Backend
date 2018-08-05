<?php

namespace App;


use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Metatag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'metatags';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tag'];
    
    
    /**
     * get all metatags
     */
    public function getAll($filters = array(), $paginate = false)
    { 
        $tags = Metatag::orderBy('id', 'DESC');
        
        if(isset($filters) && !empty($filters)) 
        { 
            if(isset($filters['searchText']) && $filters['searchText'] != '')
            {
                $tags->where('tag', 'like', '%'.$filters['searchText'].'%');
            }
            
        }
        
        return $tags->get();
        
    }
    
}
