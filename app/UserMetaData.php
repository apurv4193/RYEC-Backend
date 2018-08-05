<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class UserMetaData extends Model
{
    use SoftDeletes;

    protected $table = 'user_metadata';
    protected $fillable = ['user_id', 'father_name', 'native_village', 'maternal_home','kul_gotra', 'children', 'social_activity', 'business_achievments'];
    protected $dates = ['deleted_at'];

    /**
     * Insert and Update UserMetaData
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
            return UserMetaData::where('id', $data['id'])->update($updateData);
        } else {
            return UserMetaData::create($data);
        }
    }

    /**
    * get all UserMetaData By userId for admin
    */
    public function getAllByuserId($userId)
    {
        $metadata = UserMetaData::orderBy('id', 'DESC');
        return $metadata->where('user_id',$userId)->first();
    }
   

   
}
