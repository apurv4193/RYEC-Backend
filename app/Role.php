<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Role extends Model
{
    protected $table = 'roles';
    protected $fillable = ['id', 'slug', 'name', 'created_by', 'updated_by', 'status'];

    public function insertUpdate($role)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return Role::where('id', $data['id'])->update($updateData);
        } else {
            return Role::create($data);
        }
    }

    public function getAllRoles()
    {
        $roles = Role::where('status', '<>', Config::get('constant.DELETED_FLAG'))
                                ->orderBy('id', 'DESC')
                                ->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));

        return $roles;
    }

    
}
