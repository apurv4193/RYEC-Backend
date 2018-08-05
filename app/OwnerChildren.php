<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class OwnerChildren extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'owner_children';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['owner_id', 'children_name'];
    
    /**
     * The attributes that are dates
     *
     * @var array
     */
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
            return OwnerChildren::where('id', $data['id'])->update($updateData);
        } else {
             return OwnerChildren::create($data);
        }
    }

    /**
     * get all Owner children for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $ownerChildren = OwnerChildren::orderBy('id', 'DESC');
        
        if(isset($paginate) && $paginate == true) {
            return $ownerChildren->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $ownerChildren->get();
        }
    }
   
}